<?php

namespace TerminalHero\Iso8583\Validators;

use TerminalHero\Iso8583\Enums\Format;
use TerminalHero\Iso8583\Exceptions\InvalidFieldFormatException;

/**
 * Validates the value of a field against its declared Format type.
 *
 * This validator is invoked by the Builder before writing field data to
 * ensure type integrity is maintained in the outgoing message.
 *
 * -----------------------------------------------------------------------
 * HOW TO CONTRIBUTE:
 * -----------------------------------------------------------------------
 * To add support for a new format type:
 *
 * 1. Add the new case to the `Format` enum in `src/Enums/Format.php`.
 * 2. Add a regex pattern for the new format in the `PATTERNS` constant below.
 * 3. Write a test case in `tests/Validators/FieldValidatorTest.php`.
 *
 * The `validate()` method is designed to be easily extensible:
 * simply adding a pattern to PATTERNS is all that is needed.
 * -----------------------------------------------------------------------
 */
class FieldValidator
{
    /**
     * Regular expression patterns used to validate each Format type.
     *
     * The key is the `Format` enum case name (e.g., 'NUMERIC').
     * The value is the regex pattern that the field's string value must match.
     *
     * Pattern explanations:
     * - NUMERIC (n):       Only digits 0-9.
     * - ALPHA (a):         Only letters A-Z, a-z, and space.
     * - ALPHA_NUMERIC (an): Digits and letters only (no special chars).
     * - ALPHA_NUMERIC_SPECIAL (ans): Digits, letters, and common special characters.
     * - BINARY (b):        A valid hexadecimal string (pairs of hex chars).
     * - TRACK_DATA (z):    Digits, capital letters, and the separators '=' and '?'.
     * - SPECIAL (x):       Starts with 'C' or 'D' followed by 8 digits (amount field format).
     *
     * @var array<string, string>
     */
    private const PATTERNS = [
        'NUMERIC'               => '/^\d+$/',
        'ALPHA'                 => '/^[a-zA-Z ]+$/',
        'ALPHA_NUMERIC'         => '/^[a-zA-Z0-9]+$/',
        'ALPHA_NUMERIC_SPECIAL' => '/^[a-zA-Z0-9 !@#$%^&*()_+\-=\[\]{};\':\"\\\\|,.<>\/?`~]*$/',
        'BINARY'                => '/^[a-fA-F0-9]*$/',
        'TRACK_DATA'            => '/^[0-9A-Z=?]+$/',
        'SPECIAL'               => '/^[CD]\d{8}$/',
    ];

    /**
     * Validate the string value against its declared Format.
     *
     * This method only validates non-empty string values. If the field has
     * a Transformer attached, the value will already have been converted to
     * a string by the Transformer's `build()` method before validation.
     *
     * @param string $value       The string value to validate.
     * @param Format $format      The expected format from the field spec.
     * @param int    $fieldNumber Used for meaningful error messages.
     *
     * @throws InvalidFieldFormatException if the value does not match the format.
     */
    /**
     * Validate the string value against its declared Format.
     *
     * This method only validates non-empty string values. If the field has
     * a Transformer attached, the value will already have been converted to
     * a string by the Transformer's `build()` method before validation.
     *
     * NOTE: Fields with transformers skip format validation because the
     * transformer is responsible for producing a valid raw string from a
     * structured input (e.g., array). Validating the intermediate string
     * produced by the transformer would be incorrect.
     *
     * @param string $value            The string value to validate.
     * @param Format $format           The expected format from the field spec.
     * @param int    $fieldNumber      Used for meaningful error messages.
     * @param bool   $hasTransformer   If true, skip validation entirely.
     *
     * @throws InvalidFieldFormatException if the value does not match the format.
     */
    public function validate(string $value, Format $format, int $fieldNumber, bool $hasTransformer = false): void
    {
        // Skip validation for empty values or transformer-produced values
        if ($value === '' || $hasTransformer) {
            return;
        }

        $patternKey = $format->name;

        // If no pattern is defined for this format, we skip validation.
        // This allows future Format types to be added without breaking the validator.
        if (!isset(self::PATTERNS[$patternKey])) {
            return;
        }

        if (!preg_match(self::PATTERNS[$patternKey], $value)) {
            throw new InvalidFieldFormatException($fieldNumber, $format->value, $value);
        }
    }
}
