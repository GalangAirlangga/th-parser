<?php

namespace TerminalHero\Iso8583\Enums;

/**
 * Defines the format used to represent the bitmap in the raw ISO8583 string.
 *
 * The bitmap encodes which fields (bits) are present in a message.
 * Different vendors/banks use different representations:
 *
 * 1. HEXADECIMAL (most common for ASCII-based connections):
 *    - The 64-bit primary bitmap is represented as a 16-character HEX string.
 *    - Example: "3220000000000000" (16 chars / 16 bytes on wire)
 *
 * 2. RAW_BINARY (common for legacy/EBCDIC or binary TCP connections):
 *    - The 64-bit primary bitmap is represented as 8 raw bytes.
 *    - This is more compact (half the size) but requires binary-safe handling.
 *    - Example: 0x32, 0x20, 0x00 ... (8 bytes on wire)
 *
 * @see \TerminalHero\Iso8583\Specs\BaseSpec::setBitmapFormat()
 */
enum BitmapFormat: string
{
    /**
     * Bitmap is encoded as a hexadecimal string.
     * Each byte of the bitmap is represented by 2 hex characters.
     * A 64-bit bitmap occupies 16 characters in the message string.
     *
     * This is the DEFAULT format and is safe for standard ASCII connections.
     */
    case HEXADECIMAL = 'hex';

    /**
     * Bitmap is encoded as a raw binary string.
     * Each byte of the bitmap occupies exactly 1 byte on the wire.
     * A 64-bit bitmap occupies 8 characters (bytes) in the message string.
     *
     * Use this for legacy or binary-mode connections where bandwidth matters.
     * WARNING: The resulting string is NOT printable ASCII.
     */
    case RAW_BINARY = 'binary';
}
