<?php

namespace TerminalHero\Iso8583;

use TerminalHero\Iso8583\Contracts\FieldTransformerInterface;
use TerminalHero\Iso8583\Enums\BitmapFormat;
use TerminalHero\Iso8583\Enums\Format;
use TerminalHero\Iso8583\Enums\LengthFormat;
use TerminalHero\Iso8583\Enums\Padding;
use TerminalHero\Iso8583\Exceptions\InvalidFieldLengthException;
use TerminalHero\Iso8583\Exceptions\InvalidHeaderException;
use TerminalHero\Iso8583\Exceptions\InvalidMtiException;
use TerminalHero\Iso8583\Exceptions\ParseException;
use TerminalHero\Iso8583\Specs\BaseSpec;
use TerminalHero\Iso8583\Validators\FieldValidator;

/**
 * Builds a raw ISO8583 message string from a structured `Message` object.
 *
 * The builder processes the Message in order:
 *   1. Validates the MTI.
 *   2. Iterates over all set fields to generate the bitmap and data string.
 *   3. Assembles the final string: [Header] + MTI + Bitmap + DataElements.
 *
 * -----------------------------------------------------------------------
 * HOW THE BUILDER WORKS:
 * -----------------------------------------------------------------------
 * 1. It reads all set fields from the Message and sorts them by field number.
 * 2. For each field, it sets the corresponding bit in the 128-bit bitmap.
 * 3. Each field's value is processed through its transformer (if any) and
 *    then encoded as a string according to its length type and padding rules.
 * 4. The bitmap is trimmed to 64 or 128 bits depending on whether any
 *    secondary (65-128) fields are present.
 * 5. The bitmap is encoded in the format specified by the Spec (HEX or Binary).
 * 6. The final string is assembled and the Header (if any) is prepended.
 *
 * -----------------------------------------------------------------------
 * HOW TO EXTEND THE BUILDER:
 * -----------------------------------------------------------------------
 * - New bitmap formats: extend `buildBitmapSegment()`.
 * - New length formats: extend `buildVariableLengthIndicator()`.
 * - New padding strategies: add to `applyPadding()` and the `Padding` enum.
 */
class Builder
{
    protected BaseSpec    $spec;
    protected FieldValidator $validator;

    public function __construct(BaseSpec $spec)
    {
        $this->spec      = $spec;
        $this->validator = new FieldValidator();
    }

    /**
     * Build a raw ISO8583 string from a Message object.
     *
     * @param Message $message The message to build.
     *
     * @return string The raw ISO8583 string ready to be transmitted.
     *
     * @throws InvalidMtiException          If the MTI is invalid.
     * @throws InvalidHeaderException       If a header is set but has the wrong length.
     * @throws InvalidFieldLengthException  If a field value is too long.
     * @throws ParseException               If a field number is not in the Spec.
     */
    public function build(Message $message): string
    {
        // Validate MTI
        $mti = $message->getMti();
        if (strlen($mti) !== 4) {
            throw new InvalidMtiException("MTI must be exactly 4 characters, got: '{$mti}'");
        }

        $fields = $message->getFields();
        ksort($fields); // Fields must be processed in ascending bit-number order

        // Determine if we need a secondary bitmap (any field > 64)
        $maxField           = empty($fields) ? 0 : max(array_keys($fields));
        $hasSecondaryBitmap = ($maxField > 64);

        // Initialize 128-bit bitmap as a string of '0' characters
        $bitmapBin = str_repeat('0', 128);

        // If secondary fields exist, bit 1 must be set to '1'
        if ($hasSecondaryBitmap) {
            $bitmapBin[0] = '1';
        }

        $fieldsConfig = $this->spec->getConfig()['fields'] ?? [];
        $dataString   = '';

        foreach ($fields as $fieldNumber => $value) {
            // Guard: field numbers must be in range 2-128
            if ($fieldNumber < 2 || $fieldNumber > 128) {
                throw new ParseException(
                    "Invalid field number {$fieldNumber}. Must be between 2 and 128."
                );
            }

            if (!isset($fieldsConfig[$fieldNumber])) {
                throw new ParseException(
                    "Field {$fieldNumber} is not defined in the Spec. Add it in your Spec's define() method."
                );
            }

            // Mark this bit as active in the bitmap
            $bitmapBin[$fieldNumber - 1] = '1';

            $fieldConfig = $fieldsConfig[$fieldNumber];
            $dataString .= $this->buildField($value, $fieldConfig, $fieldNumber);
        }

        // Trim bitmap to 64 or 128 bits
        $bitmapBitLength = $hasSecondaryBitmap ? 128 : 64;
        $bitmapBin       = substr($bitmapBin, 0, $bitmapBitLength);

        // Encode bitmap according to the Spec's BitmapFormat
        $bitmapEncoded = $this->buildBitmapSegment($bitmapBin);

        // Store the hex representation on the Message for inspection/debugging
        $message->setBitmap($this->binaryToHex($bitmapBin));

        // Assemble the core message
        $result = $mti . $bitmapEncoded . $dataString;

        // Prepend header if present
        $header = $message->getHeader();
        if ($header !== null) {
            $expectedLength = $this->spec->getHeaderLength();
            if ($expectedLength > 0 && strlen($header) !== $expectedLength) {
                throw new InvalidHeaderException(
                    "Header length mismatch: expected {$expectedLength} characters, " .
                    "got " . strlen($header) . " for header '{$header}'."
                );
            }
            $result = $header . $result;
        }

        // Prepend TCP Packet Length Framing header if configured
        $packetFormat = $this->spec->getPacketLengthFormat();
        if ($packetFormat !== \TerminalHero\Iso8583\Enums\PacketLengthFormat::NONE) {
            $packetLength = strlen($result);

            if ($packetFormat === \TerminalHero\Iso8583\Enums\PacketLengthFormat::BINARY_2_BYTE) {
                $prefix = pack('n', $packetLength);
            } elseif ($packetFormat === \TerminalHero\Iso8583\Enums\PacketLengthFormat::ASCII_4_BYTE) {
                $prefix = str_pad((string)$packetLength, 4, '0', STR_PAD_LEFT);
            } elseif ($packetFormat === \TerminalHero\Iso8583\Enums\PacketLengthFormat::BCD_2_BYTE) {
                $prefix = $this->intToBcd(str_pad((string)$packetLength, 4, '0', STR_PAD_LEFT), 2);
            } else {
                $prefix = '';
            }

            $result = $prefix . $result;
        }

        return $result;
    }

    // =========================================================================
    // FIELD BUILDING
    // =========================================================================

    /**
     * Encode a single field value into its raw ISO8583 string representation.
     *
     * Process order:
     * 1. Apply Transformer (if any) to convert array/object → string.
     * 2. Cast to string.
     * 3. Validate format.
     * 4. Validate/check length.
     * 5. Apply padding (for FIXED fields) or prepend length indicator (for VAR fields).
     *
     * @param mixed $value       The field value (string or array if a Transformer is attached).
     * @param array $config      The field's configuration from the Spec.
     * @param int   $fieldNumber For error messages.
     *
     * @return string The encoded field segment to append to the data string.
     */
    protected function buildField(mixed $value, array $config, int $fieldNumber): string
    {
        // Step 1: Apply Transformer (converts array → string before encoding)
        $transformer = $config['transformer'] ?? null;
        if ($transformer instanceof FieldTransformerInterface) {
            $value = $transformer->build($value);
        }

        // Step 2: Ensure the value is a string
        $value = is_string($value) ? $value : (string) $value;

        // Step 3: Validate format (e.g., NUMERIC must only contain digits)
        // Skip validation if a transformer is attached — the transformer
        // handles converting the user's structured input into a valid string.
        /** @var Format $format */
        $format         = $config['format'];
        $hasTransformer = ($transformer !== null);
        $this->validator->validate($value, $format, $fieldNumber, $hasTransformer);

        // Step 4: Check length and encode based on length type
        $lengthType = $config['length_type'];
        $maxLength  = $config['length'];

        if ($lengthType === 'fixed') {
            return $this->buildFixedField($value, $config, $fieldNumber, $maxLength);
        }

        if ($lengthType === 'llvar') {
            return $this->buildVariableField($value, $fieldNumber, $maxLength, 2);
        }

        if ($lengthType === 'lllvar') {
            return $this->buildVariableField($value, $fieldNumber, $maxLength, 3);
        }

        return $value;
    }

    /**
     * Build a FIXED-length field, applying the correct padding if necessary.
     *
     * @throws InvalidFieldLengthException If the value is longer than the max length.
     */
    protected function buildFixedField(string $value, array $config, int $fieldNumber, int $maxLength): string
    {
        $actualLength = strlen($value);

        if ($actualLength > $maxLength) {
            throw new InvalidFieldLengthException($fieldNumber, $maxLength, $actualLength);
        }

        if ($actualLength < $maxLength) {
            /** @var Padding $padding */
            $padding = $config['padding'] ?? Padding::LEFT_ZERO;
            $value   = $this->applyPadding($value, $maxLength, $padding);
        }

        return $value;
    }

    /**
     * Build a variable-length field (LLVAR or LLLVAR), prepending the length indicator.
     *
     * @param string $value       The field value.
     * @param int    $fieldNumber For error messages.
     * @param int    $maxLength   The maximum allowed length.
     * @param int    $digits      Number of ASCII digits in the length indicator (2 or 3).
     *
     * @throws InvalidFieldLengthException
     */
    protected function buildVariableField(string $value, int $fieldNumber, int $maxLength, int $digits): string
    {
        $actualLength = strlen($value);

        if ($actualLength > $maxLength) {
            throw new InvalidFieldLengthException($fieldNumber, $maxLength, $actualLength);
        }

        $indicator = $this->buildVariableLengthIndicator($actualLength, $digits);

        return $indicator . $value;
    }

    /**
     * Encode a length value as a length indicator string (ASCII or BCD).
     *
     * - ASCII mode: '019' for LLLVAR length 19 (3 characters).
     * - BCD mode:   chr(0x19) for LLVAR length 19 (1 byte).
     *               chr(0x00) . chr(0x19) for LLLVAR length 19 (2 bytes).
     *
     * @param int $length The actual length of the field value.
     * @param int $digits The expected number of ASCII digits (2 for LL, 3 for LLL).
     *
     * @return string The encoded length indicator.
     */
    protected function buildVariableLengthIndicator(int $length, int $digits): string
    {
        $lengthFormat = $this->spec->getLengthIndicatorFormat();

        if ($lengthFormat === LengthFormat::BCD) {
            // BCD packs 2 digits per byte: LL = 1 byte, LLL = 2 bytes
            $numBytes = (int) ceil($digits / 2);

            // Convert to a zero-padded decimal string, then pack as BCD
            $paddedDigits = str_pad((string) $length, $digits, '0', STR_PAD_LEFT);

            return $this->intToBcd($paddedDigits, $numBytes);
        }

        // Default: ASCII — zero-pad the length to the required number of digits
        return str_pad((string) $length, $digits, '0', STR_PAD_LEFT);
    }

    // =========================================================================
    // BITMAP ENCODING
    // =========================================================================

    /**
     * Encode a binary bit-string into a bitmap segment for the message.
     *
     * Supports HEXADECIMAL (16 chars per 64 bits) and RAW_BINARY (8 bytes per 64 bits).
     */
    protected function buildBitmapSegment(string $bitmapBin): string
    {
        $format = $this->spec->getBitmapFormat();

        if ($format === BitmapFormat::RAW_BINARY) {
            return $this->binToRawBinary($bitmapBin);
        }

        return $this->binaryToHex($bitmapBin);
    }

    // =========================================================================
    // PADDING
    // =========================================================================

    /**
     * Apply a padding strategy to a value to reach the target length.
     *
     * -----------------------------------------------------------------------
     * HOW TO ADD A NEW PADDING STRATEGY:
     * -----------------------------------------------------------------------
     * 1. Add a new case to the `Padding` enum in `src/Enums/Padding.php`.
     * 2. Add a corresponding `case` here with the correct `str_pad` parameters.
     * 3. Update `FieldValidatorTest` and `PaddingTest` in the test suite.
     * -----------------------------------------------------------------------
     */
    protected function applyPadding(string $value, int $targetLength, Padding $padding): string
    {
        return match ($padding) {
            Padding::LEFT_ZERO  => str_pad($value, $targetLength, '0', STR_PAD_LEFT),
            Padding::LEFT_SPACE => str_pad($value, $targetLength, ' ', STR_PAD_LEFT),
            Padding::RIGHT_ZERO => str_pad($value, $targetLength, '0', STR_PAD_RIGHT),
            Padding::RIGHT_SPACE=> str_pad($value, $targetLength, ' ', STR_PAD_RIGHT),
        };
    }

    // =========================================================================
    // UTILITY METHODS
    // =========================================================================

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
     * Convert a binary bit-string to a raw binary byte string.
     * Example: "10100000" → chr(0xA0)
     */
    protected function binToRawBinary(string $bin): string
    {
        $bytes = '';
        for ($i = 0; $i < strlen($bin); $i += 8) {
            $bytes .= chr((int) base_convert(substr($bin, $i, 8), 2, 10));
        }
        return $bytes;
    }

    /**
     * Convert a zero-padded decimal digit string to BCD-encoded bytes.
     *
     * Example: "19" (2 digits, 1 byte) → chr(0x19)
     * Example: "019" (3 digits, 2 bytes) → chr(0x00) . chr(0x19)
     *          (the leading nibble "0" is a padding nibble for odd-length BCD)
     *
     * @param string $paddedDigits  The decimal string with leading zeros (e.g., "019").
     * @param int    $numBytes      The number of bytes to produce.
     *
     * @return string BCD-encoded byte string.
     */
    protected function intToBcd(string $paddedDigits, int $numBytes): string
    {
        // Ensure even length for nibble pairing (prepend '0' if odd)
        if (strlen($paddedDigits) % 2 !== 0) {
            $paddedDigits = '0' . $paddedDigits;
        }

        $bytes = '';
        for ($i = 0; $i < strlen($paddedDigits); $i += 2) {
            $highNibble = (int) $paddedDigits[$i];
            $lowNibble  = (int) $paddedDigits[$i + 1];
            $bytes     .= chr(($highNibble << 4) | $lowNibble);
        }

        return $bytes;
    }
}
