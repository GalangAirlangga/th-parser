<?php

namespace TerminalHero\Iso8583\Exceptions;

/**
 * Thrown when a field's value exceeds its configured maximum length.
 *
 * For FIXED-length fields, this means the value is longer than the fixed size.
 * For LLVAR fields, the value length exceeds the 2-digit or BCD limit.
 * For LLLVAR fields, the value length exceeds the 3-digit or BCD limit.
 */
class InvalidFieldLengthException extends Iso8583Exception
{
    public function __construct(int $fieldNumber, int $maxLength, int $actualLength)
    {
        parent::__construct(
            "Field {$fieldNumber} value length ({$actualLength}) exceeds the configured maximum of {$maxLength}"
        );
    }
}
