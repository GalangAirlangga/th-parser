<?php

namespace TerminalHero\Iso8583\Enums;

/**
 * Defines the padding strategy applied to a FIXED-length field when
 * the provided value is shorter than the configured maximum length.
 *
 * Typical use cases:
 * - Numeric fields are usually padded with zeroes on the LEFT.
 * - Alpha fields are usually padded with spaces on the RIGHT.
 *
 * Some vendor specifications may deviate from the standard, which is why
 * this enum exists — to give the developer explicit control per-field.
 *
 * @see \TerminalHero\Iso8583\Specs\BaseSpec::setPadding()
 */
enum Padding: string
{
    /**
     * Pad with '0' on the left side.
     *
     * Example: value "100", length 6 → "000100"
     * Commonly used for NUMERIC fields.
     */
    case LEFT_ZERO = 'left_zero';

    /**
     * Pad with ' ' (space) on the left side.
     *
     * Example: value "ABC", length 6 → "   ABC"
     */
    case LEFT_SPACE = 'left_space';

    /**
     * Pad with '0' on the right side.
     *
     * Example: value "100", length 6 → "100000"
     */
    case RIGHT_ZERO = 'right_zero';

    /**
     * Pad with ' ' (space) on the right side.
     *
     * Example: value "ABC", length 6 → "ABC   "
     * Commonly used for ALPHA or ALPHA_NUMERIC fields.
     */
    case RIGHT_SPACE = 'right_space';
}
