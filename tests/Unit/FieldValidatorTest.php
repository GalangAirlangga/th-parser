<?php

namespace TerminalHero\Iso8583\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TerminalHero\Iso8583\Enums\Format;
use TerminalHero\Iso8583\Exceptions\InvalidFieldFormatException;
use TerminalHero\Iso8583\Validators\FieldValidator;

/**
 * Unit tests for the FieldValidator class.
 *
 * Covers all Format types for both valid and invalid inputs.
 */
class FieldValidatorTest extends TestCase
{
    private FieldValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new FieldValidator();
    }

    // =========================================================================
    // NUMERIC
    // =========================================================================

    public function test_numeric_accepts_digits(): void
    {
        $this->validator->validate('1234567890', Format::NUMERIC, 4);
        $this->assertTrue(true); // No exception = pass
    }

    public function test_numeric_rejects_letters(): void
    {
        $this->expectException(InvalidFieldFormatException::class);
        $this->validator->validate('12A4', Format::NUMERIC, 4);
    }

    public function test_numeric_rejects_special_chars(): void
    {
        $this->expectException(InvalidFieldFormatException::class);
        $this->validator->validate('12.4', Format::NUMERIC, 4);
    }

    // =========================================================================
    // ALPHA
    // =========================================================================

    public function test_alpha_accepts_letters_and_spaces(): void
    {
        $this->validator->validate('Hello World', Format::ALPHA, 11);
        $this->assertTrue(true);
    }

    public function test_alpha_rejects_digits(): void
    {
        $this->expectException(InvalidFieldFormatException::class);
        $this->validator->validate('Hello1', Format::ALPHA, 6);
    }

    // =========================================================================
    // ALPHA_NUMERIC
    // =========================================================================

    public function test_alpha_numeric_accepts_letters_and_digits(): void
    {
        $this->validator->validate('ABC123', Format::ALPHA_NUMERIC, 37);
        $this->assertTrue(true);
    }

    public function test_alpha_numeric_rejects_special_chars(): void
    {
        $this->expectException(InvalidFieldFormatException::class);
        $this->validator->validate('ABC-123', Format::ALPHA_NUMERIC, 38);
    }

    // =========================================================================
    // ALPHA_NUMERIC_SPECIAL
    // =========================================================================

    public function test_alpha_numeric_special_accepts_printable_chars(): void
    {
        $this->validator->validate('ABC 123 !@#', Format::ALPHA_NUMERIC_SPECIAL, 41);
        $this->assertTrue(true);
    }

    // =========================================================================
    // BINARY
    // =========================================================================

    public function test_binary_accepts_valid_hex_string(): void
    {
        $this->validator->validate('9F0206000000000100', Format::BINARY, 52);
        $this->assertTrue(true);
    }

    public function test_binary_rejects_non_hex_chars(): void
    {
        $this->expectException(InvalidFieldFormatException::class);
        $this->validator->validate('9F02ZZ', Format::BINARY, 55);
    }

    // =========================================================================
    // TRACK_DATA
    // =========================================================================

    public function test_track_data_accepts_track_format(): void
    {
        $this->validator->validate('4111111111111111=25121010000000000000', Format::TRACK_DATA, 35);
        $this->assertTrue(true);
    }

    public function test_track_data_rejects_lowercase(): void
    {
        $this->expectException(InvalidFieldFormatException::class);
        $this->validator->validate('abcdef', Format::TRACK_DATA, 35);
    }

    // =========================================================================
    // Empty String Bypass
    // =========================================================================

    public function test_empty_string_always_passes_validation(): void
    {
        // Empty string should not throw even for NUMERIC format
        $this->validator->validate('', Format::NUMERIC, 4);
        $this->assertTrue(true);
    }

    // =========================================================================
    // Exception Message Content
    // =========================================================================

    public function test_exception_message_contains_field_number(): void
    {
        try {
            $this->validator->validate('NOTDIGIT', Format::NUMERIC, 42);
            $this->fail('Expected exception was not thrown');
        } catch (InvalidFieldFormatException $e) {
            $this->assertStringContainsString('42', $e->getMessage());
            $this->assertStringContainsString('NOTDIGIT', $e->getMessage());
        }
    }
}
