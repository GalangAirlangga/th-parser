<?php

namespace TerminalHero\Iso8583\Enums;

/**
 * Defines the encoding format for the length indicator of variable-length fields
 * (LLVAR and LLLVAR).
 *
 * When a field is variable-length, its actual length must be written before
 * the data itself. This indicator can be encoded in different ways:
 *
 * 1. ASCII (most common):
 *    - The length digits are written as ASCII character digits.
 *    - An LLVAR field with length 19 is written as two ASCII chars: "1" and "9" (2 bytes).
 *    - Example for length 19: "19" → 0x31, 0x39
 *
 * 2. BCD (Binary Coded Decimal):
 *    - Each digit is packed into a nibble (4 bits). Two digits = 1 byte.
 *    - An LLVAR field with length 19 is written as one BCD byte: 0x19 (1 byte).
 *    - This effectively halves the size of the length indicator.
 *    - Common in legacy banking protocols and EDC machines.
 *
 * @see \TerminalHero\Iso8583\Specs\BaseSpec::setLengthIndicatorFormat()
 */
enum LengthFormat: string
{
    /**
     * Length indicator is encoded as ASCII digit characters.
     *
     * LLVAR uses 2 bytes, LLLVAR uses 3 bytes.
     * This is the DEFAULT format.
     */
    case ASCII = 'ascii';

    /**
     * Length indicator is encoded as Binary Coded Decimal (BCD).
     *
     * LLVAR uses 1 byte, LLLVAR uses 2 bytes.
     * Use this for legacy BCD-mode connections.
     */
    case BCD = 'bcd';
}
