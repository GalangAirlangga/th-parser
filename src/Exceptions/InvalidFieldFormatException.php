<?php

namespace TerminalHero\Iso8583\Exceptions;

/**
 * Thrown when a field's value does not match the expected format/type.
 *
 * For example, if a field is declared as NUMERIC but receives a value
 * containing alphabetic characters, this exception will be thrown.
 *
 * The exception message includes the field number, the expected format,
 * and the actual value to help with debugging.
 *
 * @see \TerminalHero\Iso8583\Validators\FieldValidator
 */
class InvalidFieldFormatException extends Iso8583Exception
{
    public function __construct(int $fieldNumber, string $expectedFormat, string $actualValue)
    {
        parent::__construct(
            "Field {$fieldNumber} expects format '{$expectedFormat}' but received value: '{$actualValue}'"
        );
    }
}
