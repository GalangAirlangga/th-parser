<?php

namespace TerminalHero\Iso8583;

use TerminalHero\Iso8583\Contracts\FieldTransformerInterface;
use TerminalHero\Iso8583\Enums\BitmapFormat;
use TerminalHero\Iso8583\Enums\LengthFormat;
use TerminalHero\Iso8583\Exceptions\InvalidHeaderException;
use TerminalHero\Iso8583\Exceptions\InvalidMtiException;
use TerminalHero\Iso8583\Exceptions\ParseException;
use TerminalHero\Iso8583\Specs\BaseSpec;

/**
 * Parses a raw ISO8583 message string into a structured `Message` object.
 *
 * The parser processes the string in order:
 *   1. [Optional] Header  (fixed-length prefix defined in the Spec)
 *   2. MTI               (4 character/bytes)
 *   3. Bitmap            (64-bit Primary + optional 64-bit Secondary)
 *   4. Data Elements     (each field is read in the order its bit appears in the bitmap)
 *
 * -----------------------------------------------------------------------
 * HOW THE BITMAP WORKS:
 * -----------------------------------------------------------------------
 * The bitmap is a 64-bit (or 128-bit) binary sequence where each bit
 * corresponds to a field number. If the bit is 1, that field is present.
 *
 * - Bit 1 being ON signals the presence of the Secondary Bitmap (bits 65-128).
 * - A Hex bitmap "3200..." maps to binary "00110010 00000000 ..."
 * - Fields are processed in ascending order (Bit 2 first, then Bit 3, etc.)
 *
 * -----------------------------------------------------------------------
 * HOW TO EXTEND THE PARSER:
 * -----------------------------------------------------------------------
 * - To support a new bitmap format, extend the `parseBitmap()` method.
 * - To support a new length indicator format, extend `parseVariableLength()`.
 * - All new data formats should be driven by the Spec configuration, not by
 *   hardcoded values in this class.
 */
class Parser
{
    protected BaseSpec $spec;

    public function __construct(BaseSpec $spec)
    {
        $this->spec = $spec;
    }

    /**
     * Parse a raw ISO8583 string into a structured Message object.
     *
     * @param string $raw The raw ISO8583 message string.
     *
     * @return Message A populated Message object with all parsed fields.
     *
     * @throws InvalidHeaderException  If the header is missing or too short.
     * @throws InvalidMtiException     If the MTI is missing or malformed.
     * @throws ParseException          If the bitmap or data elements are corrupt.
     */
    public function parse(string $raw): Message
    {
        $message = new Message();
        $offset  = 0;

        // Step 0: Parse optional TCP Packet Length framing header
        $offset = $this->parsePacketLengthHeader($raw, $offset);

        // Step 1: Parse the optional ISO Header
        $offset = $this->parseHeader($raw, $offset, $message);

        // Step 2: Parse the MTI (always 4 characters)
        $offset = $this->parseMti($raw, $offset, $message);

        // Step 3: Parse the Bitmap and get back the binary representation
        [$bitmapBin, $offset] = $this->parseBitmap($raw, $offset, $message);

        // Step 4: Iterate through the bitmap and parse each active field
        $this->parseDataElements($raw, $offset, $bitmapBin, $message);

        return $message;
    }

    /**
     * Step 0: Extract and skip the TCP packet length prefix if configured.
     */
    protected function parsePacketLengthHeader(string $raw, int $offset): int
    {
        $format = $this->spec->getPacketLengthFormat();

        if ($format === \TerminalHero\Iso8583\Enums\PacketLengthFormat::NONE) {
            return $offset;
        }

        if ($format === \TerminalHero\Iso8583\Enums\PacketLengthFormat::BINARY_2_BYTE) {
            if (strlen($raw) < $offset + 2) {
                throw new ParseException("Message is too short to contain 2-byte binary packet length header.");
            }
            return $offset + 2;
        }

        if ($format === \TerminalHero\Iso8583\Enums\PacketLengthFormat::ASCII_4_BYTE) {
            if (strlen($raw) < $offset + 4) {
                throw new ParseException("Message is too short to contain 4-byte ASCII packet length header.");
            }
            return $offset + 4;
        }

        if ($format === \TerminalHero\Iso8583\Enums\PacketLengthFormat::BCD_2_BYTE) {
            if (strlen($raw) < $offset + 2) {
                throw new ParseException("Message is too short to contain 2-byte BCD packet length header.");
            }
            return $offset + 2;
        }

        return $offset;
    }

    // =========================================================================
    // PARSING STEPS (Protected methods for testability and extensibility)
    // =========================================================================

    /**
     * Step 1: Extract the ISO header from the beginning of the raw string.
     * If no header length is configured, this method is a no-op.
     *
     * @return int The new offset after reading the header.
     *
     * @throws InvalidHeaderException
     */
    protected function parseHeader(string $raw, int $offset, Message $message): int
    {
        $headerLength = $this->spec->getHeaderLength();

        if ($headerLength <= 0) {
            return $offset; // No header configured, skip.
        }

        if (strlen($raw) < $offset + $headerLength) {
            throw new InvalidHeaderException(
                "Message is too short to contain the configured header of {$headerLength} characters."
            );
        }

        $message->setHeader(substr($raw, $offset, $headerLength));

        return $offset + $headerLength;
    }

    /**
     * Step 2: Extract the 4-character MTI from the message.
     *
     * @return int The new offset after reading the MTI.
     *
     * @throws InvalidMtiException
     */
    protected function parseMti(string $raw, int $offset, Message $message): int
    {
        if (strlen($raw) < $offset + 4) {
            throw new InvalidMtiException(
                "Message is too short to contain an MTI (requires 4 characters after any header)."
            );
        }

        $message->setMti(substr($raw, $offset, 4));

        return $offset + 4;
    }

    /**
     * Step 3: Read and decode the Primary (and optional Secondary) Bitmap.
     *
     * The bitmap width depends on BitmapFormat:
     * - HEXADECIMAL: 16 chars per 64 bits
     * - RAW_BINARY:  8 bytes per 64 bits
     *
     * @return array{0: string, 1: int} [bitmapBinaryString, newOffset]
     *
     * @throws ParseException
     */
    protected function parseBitmap(string $raw, int $offset, Message $message): array
    {
        $format = $this->spec->getBitmapFormat();

        // Width of one 64-bit bitmap segment in characters (varies by format).
        $segmentWidth = ($format === BitmapFormat::RAW_BINARY) ? 8 : 16;

        if (strlen($raw) < $offset + $segmentWidth) {
            throw new ParseException(
                "Message is too short to contain the Primary Bitmap (requires {$segmentWidth} characters)."
            );
        }

        // Read the primary bitmap segment
        $primarySegment = substr($raw, $offset, $segmentWidth);
        $offset        += $segmentWidth;

        // Convert to binary string for bit inspection
        $primaryBitmapBin = $this->segmentToBinary($primarySegment, $format);

        $bitmapBin       = $primaryBitmapBin;
        $bitmapHex       = $this->binaryToHex($primaryBitmapBin);

        // Bit 1 (index 0 in binary string) being '1' means a secondary bitmap follows
        if ($primaryBitmapBin[0] === '1') {
            if (strlen($raw) < $offset + $segmentWidth) {
                throw new ParseException(
                    "Message is too short to contain the Secondary Bitmap (requires {$segmentWidth} more characters)."
                );
            }

            $secondarySegment = substr($raw, $offset, $segmentWidth);
            $offset          += $segmentWidth;

            $bitmapBin .= $this->segmentToBinary($secondarySegment, $format);
            $bitmapHex .= $this->binaryToHex($this->segmentToBinary($secondarySegment, $format));
        }

        // Store the bitmap in HEX format on the Message for consistent representation
        $message->setBitmap($bitmapHex);

        return [$bitmapBin, $offset];
    }

    /**
     * Step 4: Iterate over each active bit in the bitmap and parse its field value.
     *
     * @throws ParseException
     */
    protected function parseDataElements(string $raw, int $offset, string $bitmapBin, Message $message): void
    {
        $fieldsConfig = $this->spec->getConfig()['fields'] ?? [];

        for ($i = 1; $i < strlen($bitmapBin); $i++) {
            if ($bitmapBin[$i] !== '1') {
                continue; // Bit is not set, skip this field
            }

            $fieldNumber = $i + 1; // Bitmap index is 0-based; bit 2 = index 1

            if (!isset($fieldsConfig[$fieldNumber])) {
                throw new ParseException(
                    "Field {$fieldNumber} is active in the bitmap but has no configuration in the Spec. " .
                    "Add it to your Spec's define() method."
                );
            }

            $fieldConfig = $fieldsConfig[$fieldNumber];

            // Parse the raw field value and advance the offset
            [$value, $offset] = $this->parseField($raw, $offset, $fieldConfig, $fieldNumber);

            $message->setField($fieldNumber, $value);
        }
    }

    /**
     * Parse a single field's value from the raw string at the given offset.
     *
     * Handles FIXED, LLVAR, and LLLVAR length types. After reading the raw
     * string value, it passes it through the field's Transformer (if any).
     *
     * @return array{0: mixed, 1: int} [parsedValue, newOffset]
     *
     * @throws ParseException
     */
    protected function parseField(string $raw, int $offset, array $config, int $fieldNumber): array
    {
        $lengthType  = $config['length_type'] ?? 'fixed';
        $fieldLength = 0;

        if ($lengthType === 'fixed') {
            $fieldLength = $config['length'];
        } elseif ($lengthType === 'llvar') {
            [$fieldLength, $offset] = $this->parseVariableLength($raw, $offset, 2, $fieldNumber);
        } elseif ($lengthType === 'lllvar') {
            [$fieldLength, $offset] = $this->parseVariableLength($raw, $offset, 3, $fieldNumber);
        }

        // Guard: ensure the raw string is long enough to contain the field data
        if (strlen($raw) < $offset + $fieldLength) {
            throw new ParseException(
                "Message is too short to read value for field {$fieldNumber}. " .
                "Expected {$fieldLength} characters at offset {$offset} but only " .
                (strlen($raw) - $offset) . " remain."
            );
        }

        $value  = substr($raw, $offset, $fieldLength);
        $offset += $fieldLength;

        // Apply transformer: convert the raw string into a structured format (e.g., array)
        $transformer = $config['transformer'] ?? null;
        if ($transformer instanceof FieldTransformerInterface) {
            $value = $transformer->parse($value);
        }

        return [$value, $offset];
    }

    /**
     * Read a variable-length indicator from the raw string.
     *
     * The indicator encoding depends on the Spec's `LengthFormat`:
     * - ASCII: reads $digits characters as a decimal string (e.g., "19" → 19).
     * - BCD:   reads $digits/2 bytes as packed BCD (e.g., 0x19 → 19).
     *
     * For BCD:
     * - LLVAR (2 digits) reads 1 byte.
     * - LLLVAR (3 digits) reads 2 bytes (second byte's low nibble is used).
     *
     * @param string $raw         The full raw message string.
     * @param int    $offset      Current parsing offset.
     * @param int    $digits      Number of ASCII digits (2 for LL, 3 for LLL).
     * @param int    $fieldNumber For error messages.
     *
     * @return array{0: int, 1: int} [fieldLength, newOffset]
     *
     * @throws ParseException
     */
    protected function parseVariableLength(string $raw, int $offset, int $digits, int $fieldNumber): array
    {
        $lengthFormat = $this->spec->getLengthIndicatorFormat();

        if ($lengthFormat === LengthFormat::BCD) {
            // BCD packs 2 digits into 1 byte, so LL=1 byte, LLL=2 bytes
            $bytesToRead = (int) ceil($digits / 2);

            if (strlen($raw) < $offset + $bytesToRead) {
                throw new ParseException(
                    "Message is too short to read BCD length indicator for field {$fieldNumber}."
                );
            }

            // Read raw bytes and unpack from BCD
            $bcdBytes   = substr($raw, $offset, $bytesToRead);
            $fieldLength = $this->bcdToInt($bcdBytes, $digits);

            return [$fieldLength, $offset + $bytesToRead];
        }

        // Default: ASCII encoding
        if (strlen($raw) < $offset + $digits) {
            throw new ParseException(
                "Message is too short to read ASCII length indicator for field {$fieldNumber}."
            );
        }

        $fieldLength = (int) substr($raw, $offset, $digits);

        return [$fieldLength, $offset + $digits];
    }

    // =========================================================================
    // UTILITY METHODS
    // =========================================================================

    /**
     * Convert a raw bitmap segment (hex string or binary bytes) to a 64-char binary string.
     *
     * A "binary string" here means a string of '0' and '1' characters representing bits.
     * Example: hexToBin("F0") → "11110000"
     */
    protected function segmentToBinary(string $segment, BitmapFormat $format): string
    {
        if ($format === BitmapFormat::RAW_BINARY) {
            return $this->rawBinaryToBin($segment);
        }

        return $this->hexToBin($segment);
    }

    /**
     * Convert a hexadecimal string to a binary bit-string.
     * Example: "A0" → "10100000"
     */
    protected function hexToBin(string $hex): string
    {
        $bin = '';
        for ($i = 0; $i < strlen($hex); $i++) {
            $bin .= str_pad(base_convert($hex[$i], 16, 2), 4, '0', STR_PAD_LEFT);
        }
        return $bin;
    }

    /**
     * Convert a raw binary string (actual bytes) to a binary bit-string.
     * Example: chr(0xA0) → "10100000"
     */
    protected function rawBinaryToBin(string $bytes): string
    {
        $bin = '';
        for ($i = 0; $i < strlen($bytes); $i++) {
            $bin .= str_pad(decbin(ord($bytes[$i])), 8, '0', STR_PAD_LEFT);
        }
        return $bin;
    }

    /**
     * Convert a binary bit-string to a hexadecimal string.
     * Example: "10100000" → "A0"
     */
    protected function binaryToHex(string $bin): string
    {
        $hex = '';
        for ($i = 0; $i < strlen($bin); $i += 4) {
            $hex .= strtoupper(base_convert(substr($bin, $i, 4), 2, 16));
        }
        return $hex;
    }

    /**
     * Decode a BCD-encoded byte string to an integer.
     *
     * BCD (Binary Coded Decimal) stores each decimal digit in one nibble (4 bits).
     * Example: 0x19 in BCD = 1 * 10 + 9 = 19 (decimal)
     *
     * For a 3-digit (LLLVAR) BCD indicator, it reads 2 bytes:
     * 0x01, 0x23 → "0123" → take last 3 digits → 123
     *
     * @param string $bcdBytes  Raw BCD-encoded bytes.
     * @param int    $numDigits Expected number of decimal digits (2 or 3).
     *
     * @return int The decoded length value.
     */
    protected function bcdToInt(string $bcdBytes, int $numDigits): int
    {
        // Convert each byte to two decimal digits (e.g., 0x19 → "19")
        $digits = '';
        for ($i = 0; $i < strlen($bcdBytes); $i++) {
            $byte    = ord($bcdBytes[$i]);
            $digits .= sprintf('%02d', ($byte >> 4)) . ($byte & 0x0F);
        }

        // Take only the rightmost $numDigits characters (handles padding nibbles)
        return (int) substr($digits, -$numDigits);
    }
}
