<?php

namespace TerminalHero\Iso8583\Tests\Integration;

use TerminalHero\Iso8583\Enums\BitmapFormat;
use TerminalHero\Iso8583\Enums\Field;
use TerminalHero\Iso8583\Enums\Format;
use TerminalHero\Iso8583\Enums\LengthFormat;
use TerminalHero\Iso8583\Enums\LengthType;
use TerminalHero\Iso8583\Enums\PacketLengthFormat;
use TerminalHero\Iso8583\Enums\Padding;
use TerminalHero\Iso8583\Enums\ResponseCode;
use TerminalHero\Iso8583\Exceptions\InvalidFieldFormatException;
use TerminalHero\Iso8583\Exceptions\InvalidFieldLengthException;
use TerminalHero\Iso8583\Exceptions\InvalidHeaderException;
use TerminalHero\Iso8583\Exceptions\InvalidMtiException;
use TerminalHero\Iso8583\Exceptions\ParseException;
use TerminalHero\Iso8583\Iso8583;
use TerminalHero\Iso8583\Specs\Standard1987Spec;
use TerminalHero\Iso8583\Specs\Standard1993Spec;
use TerminalHero\Iso8583\Specs\Standard2003Spec;
use TerminalHero\Iso8583\Transformers\PositionalTransformer;
use TerminalHero\Iso8583\Transformers\TlvTransformer;
use TerminalHero\Iso8583\Transformers\TokenTransformer;
use TerminalHero\Iso8583\Tests\TestCase;

// =========================================================================
// Custom Specs for testing
// =========================================================================

class StandardTestSpec extends Standard1987Spec
{
    public function define(): void
    {
        parent::define();
    }
}

class HeaderTestSpec extends Standard1987Spec
{
    public function define(): void
    {
        parent::define();
        $this->setHeaderLength(10);
    }
}

class BinaryBitmapSpec extends Standard1987Spec
{
    public function define(): void
    {
        parent::define();
        $this->setBitmapFormat(BitmapFormat::RAW_BINARY);
    }
}

class BcdLengthSpec extends Standard1987Spec
{
    public function define(): void
    {
        parent::define();
        $this->setLengthIndicatorFormat(LengthFormat::BCD);
    }
}

class BinaryPacketLengthSpec extends Standard1987Spec
{
    public function define(): void
    {
        parent::define();
        $this->setPacketLengthFormat(PacketLengthFormat::BINARY_2_BYTE);
    }
}

class AsciiPacketLengthSpec extends Standard1987Spec
{
    public function define(): void
    {
        parent::define();
        $this->setPacketLengthFormat(PacketLengthFormat::ASCII_4_BYTE);
    }
}

class BcdPacketLengthSpec extends Standard1987Spec
{
    public function define(): void
    {
        parent::define();
        $this->setPacketLengthFormat(PacketLengthFormat::BCD_2_BYTE);
    }
}

class RightZeroPaddingSpec extends Standard1987Spec
{
    public function define(): void
    {
        parent::define();
        $this->set(Field::CARD_ACCEPTOR_TERMINAL_IDENTIFICATION, Format::ALPHA_NUMERIC_SPECIAL, 8, LengthType::FIXED)
             ->setPadding(Padding::RIGHT_ZERO);
    }
}

class TransformerTestSpec extends Standard1987Spec
{
    public function define(): void
    {
        parent::define();

        $this->set(Field::PROCESSING_CODE, Format::NUMERIC, 6, LengthType::FIXED)
             ->setTransformer(new PositionalTransformer([
                 'trx_type' => 2,
                 'from_acc' => 2,
                 'to_acc'   => 2,
             ]));

        $this->set(Field::RESERVED_ISO_55, Format::BINARY, 999, LengthType::LLLVAR)
             ->setTransformer(new TlvTransformer());

        $this->set(Field::RESERVED_PRIVATE_62, Format::ALPHA_NUMERIC, 999, LengthType::LLLVAR)
             ->setTransformer(new TokenTransformer('!', 2, 4));
    }
}


/**
 * Integration tests for the full ISO8583 library.
 */
class Iso8583Test extends TestCase
{
    // =========================================================================
    // 1. BASIC BUILD & PARSE (Happy Path)
    // =========================================================================

    public function test_build_and_parse_simple_fixed_fields(): void
    {
        $iso     = new Iso8583(new StandardTestSpec());
        $message = $iso->makeMessage('0200');

        $message->setField(Field::PROCESSING_CODE, '000000');
        $message->setField(Field::AMOUNT_TRANSACTION, '000000010000');
        $message->setField(Field::TRANSMISSION_DATE_TIME, '0805132000');
        $message->setField(Field::SYSTEM_TRACE_AUDIT_NUMBER, '123456');

        $raw    = $iso->build($message);
        $parsed = $iso->parse($raw);

        $this->assertSame('0200', $parsed->getMti());
        $this->assertSame('000000', $parsed->getField(Field::PROCESSING_CODE));
        $this->assertSame('000000010000', $parsed->getField(Field::AMOUNT_TRANSACTION));
        $this->assertSame('0805132000', $parsed->getField(Field::TRANSMISSION_DATE_TIME));
        $this->assertSame('123456', $parsed->getField(Field::SYSTEM_TRACE_AUDIT_NUMBER));
    }

    public function test_build_and_parse_llvar_field(): void
    {
        $iso     = new Iso8583(new StandardTestSpec());
        $message = $iso->makeMessage('0200');

        $message->setField(Field::PRIMARY_ACCOUNT_NUMBER, '4111111111111111');

        $raw    = $iso->build($message);
        $parsed = $iso->parse($raw);

        $this->assertSame('4111111111111111', $parsed->getField(Field::PRIMARY_ACCOUNT_NUMBER));
    }

    public function test_build_and_parse_lllvar_field(): void
    {
        $iso     = new Iso8583(new StandardTestSpec());
        $message = $iso->makeMessage('0200');

        $data = str_repeat('A', 150);
        $message->setField(Field::ADDITIONAL_DATA_PRIVATE, $data);

        $raw    = $iso->build($message);
        $parsed = $iso->parse($raw);

        $this->assertSame($data, $parsed->getField(Field::ADDITIONAL_DATA_PRIVATE));
    }

    public function test_secondary_bitmap_is_activated_for_high_fields(): void
    {
        $iso     = new Iso8583(new StandardTestSpec());
        $message = $iso->makeMessage('0200');

        $message->setField(Field::ORIGINAL_DATA_ELEMENTS, str_repeat('1', 42));

        $raw    = $iso->build($message);
        $parsed = $iso->parse($raw);

        $this->assertSame(32, strlen($parsed->getBitmap()));
        $this->assertSame(str_repeat('1', 42), $parsed->getField(Field::ORIGINAL_DATA_ELEMENTS));
    }

    public function test_message_without_any_fields(): void
    {
        $iso     = new Iso8583(new StandardTestSpec());
        $message = $iso->makeMessage('0800');

        $raw    = $iso->build($message);
        $parsed = $iso->parse($raw);

        $this->assertSame('0800', $parsed->getMti());
        $this->assertSame([], $parsed->getActiveBits());
    }

    // =========================================================================
    // 2. HEADER TESTS
    // =========================================================================

    public function test_build_and_parse_with_header(): void
    {
        $iso     = new Iso8583(new HeaderTestSpec());
        $message = $iso->makeMessage('0200');
        $message->setHeader('ISO0150000');
        $message->setField(Field::SYSTEM_TRACE_AUDIT_NUMBER, '999999');

        $raw    = $iso->build($message);
        $parsed = $iso->parse($raw);

        $this->assertSame('ISO0150000', $parsed->getHeader());
        $this->assertSame('0200', $parsed->getMti());
        $this->assertSame('999999', $parsed->getField(Field::SYSTEM_TRACE_AUDIT_NUMBER));
    }

    public function test_parse_fails_when_raw_too_short_for_header(): void
    {
        $this->expectException(InvalidHeaderException::class);
        $iso = new Iso8583(new HeaderTestSpec());
        $iso->parse('SHORT');
    }

    public function test_build_fails_when_header_length_mismatches_spec(): void
    {
        $this->expectException(InvalidHeaderException::class);
        $iso     = new Iso8583(new HeaderTestSpec());
        $message = $iso->makeMessage('0200');
        $message->setHeader('SHORT');
        $iso->build($message);
    }

    // =========================================================================
    // 3. TCP PACKET FRAMING TESTS
    // =========================================================================

    public function test_build_and_parse_with_binary_packet_length_prefix(): void
    {
        $iso     = new Iso8583(new BinaryPacketLengthSpec());
        $message = $iso->makeMessage('0200');
        $message->setField(Field::SYSTEM_TRACE_AUDIT_NUMBER, '123456');

        $raw    = $iso->build($message);
        $parsed = $iso->parse($raw);

        $this->assertSame('0200', $parsed->getMti());
        $this->assertSame('123456', $parsed->getStan());

        // Verify the 2-byte binary prefix length matches remaining string
        $packetLen = unpack('n', substr($raw, 0, 2))[1];
        $this->assertSame(strlen($raw) - 2, $packetLen);
    }

    public function test_build_and_parse_with_ascii_packet_length_prefix(): void
    {
        $iso     = new Iso8583(new AsciiPacketLengthSpec());
        $message = $iso->makeMessage('0800');
        $message->setStan('654321');

        $raw    = $iso->build($message);
        $parsed = $iso->parse($raw);

        $this->assertSame('0800', $parsed->getMti());
        $this->assertSame('654321', $parsed->getStan());

        $asciiLen = (int) substr($raw, 0, 4);
        $this->assertSame(strlen($raw) - 4, $asciiLen);
    }

    public function test_build_and_parse_with_bcd_packet_length_prefix(): void
    {
        $iso     = new Iso8583(new BcdPacketLengthSpec());
        $message = $iso->makeMessage('0200');
        $message->setStan('123456');

        $raw    = $iso->build($message);
        $parsed = $iso->parse($raw);

        $this->assertSame('0200', $parsed->getMti());
        $this->assertSame('123456', $parsed->getStan());
    }

    // =========================================================================
    // 4. BITMAP FORMAT TESTS
    // =========================================================================

    public function test_build_and_parse_with_raw_binary_bitmap(): void
    {
        $iso     = new Iso8583(new BinaryBitmapSpec());
        $message = $iso->makeMessage('0200');
        $message->setField(Field::PROCESSING_CODE, '000000');
        $message->setField(Field::AMOUNT_TRANSACTION, '000000050000');

        $raw    = $iso->build($message);
        $parsed = $iso->parse($raw);

        $this->assertSame('000000', $parsed->getField(Field::PROCESSING_CODE));
        $this->assertSame('000000050000', $parsed->getField(Field::AMOUNT_TRANSACTION));
    }

    public function test_raw_binary_bitmap_is_half_size_of_hex(): void
    {
        $isoHex    = new Iso8583(new StandardTestSpec());
        $isoBinary = new Iso8583(new BinaryBitmapSpec());

        $msgHex    = $isoHex->makeMessage('0200')->setAmount('000000010000');
        $msgBinary = $isoBinary->makeMessage('0200')->setAmount('000000010000');

        $rawHex    = $isoHex->build($msgHex);
        $rawBinary = $isoBinary->build($msgBinary);

        $this->assertSame(strlen($rawHex) - 8, strlen($rawBinary));
    }

    // =========================================================================
    // 5. LENGTH INDICATOR FORMAT TESTS (ASCII vs BCD)
    // =========================================================================

    public function test_build_and_parse_with_bcd_length_indicators(): void
    {
        $iso     = new Iso8583(new BcdLengthSpec());
        $message = $iso->makeMessage('0200');
        $message->setField(Field::PRIMARY_ACCOUNT_NUMBER, '4111111111111111');

        $raw    = $iso->build($message);
        $parsed = $iso->parse($raw);

        $this->assertSame('4111111111111111', $parsed->getField(Field::PRIMARY_ACCOUNT_NUMBER));
    }

    // =========================================================================
    // 6. STANDARD REVISION SPECS (1993 & 2003)
    // =========================================================================

    public function test_standard_1993_spec_build_and_parse(): void
    {
        $iso     = new Iso8583(new Standard1993Spec());
        $message = $iso->makeMessage('0200')
            ->setPan('4111111111111111')
            ->setStan('123456');

        $raw    = $iso->build($message);
        $parsed = $iso->parse($raw);

        $this->assertSame('4111111111111111', $parsed->getPan());
        $this->assertSame('123456', $parsed->getStan());
    }

    public function test_standard_2003_spec_build_and_parse(): void
    {
        $iso     = new Iso8583(new Standard2003Spec());
        $message = $iso->makeMessage('0200')
            ->setPan('4111111111111111')
            ->setStan('123456');

        $raw    = $iso->build($message);
        $parsed = $iso->parse($raw);

        $this->assertSame('4111111111111111', $parsed->getPan());
        $this->assertSame('123456', $parsed->getStan());
    }

    // =========================================================================
    // 7. PADDING TESTS
    // =========================================================================

    public function test_numeric_field_is_left_zero_padded_by_default(): void
    {
        $iso     = new Iso8583(new StandardTestSpec());
        $message = $iso->makeMessage('0200');
        $message->setField(Field::SYSTEM_TRACE_AUDIT_NUMBER, '123');

        $raw    = $iso->build($message);
        $parsed = $iso->parse($raw);

        $this->assertSame('000123', $parsed->getField(Field::SYSTEM_TRACE_AUDIT_NUMBER));
    }

    public function test_alpha_field_is_right_space_padded_by_default(): void
    {
        $iso     = new Iso8583(new StandardTestSpec());
        $message = $iso->makeMessage('0200');
        $message->setField(Field::CARD_ACCEPTOR_TERMINAL_IDENTIFICATION, 'TERM');

        $raw    = $iso->build($message);
        $parsed = $iso->parse($raw);

        $this->assertSame('TERM    ', $parsed->getField(Field::CARD_ACCEPTOR_TERMINAL_IDENTIFICATION));
    }

    public function test_custom_right_zero_padding(): void
    {
        $iso     = new Iso8583(new RightZeroPaddingSpec());
        $message = $iso->makeMessage('0200');
        $message->setField(Field::CARD_ACCEPTOR_TERMINAL_IDENTIFICATION, 'TERM');

        $raw    = $iso->build($message);
        $parsed = $iso->parse($raw);

        $this->assertSame('TERM0000', $parsed->getField(Field::CARD_ACCEPTOR_TERMINAL_IDENTIFICATION));
    }

    // =========================================================================
    // 8. VALIDATION TESTS
    // =========================================================================

    public function test_build_throws_for_non_numeric_value_on_numeric_field(): void
    {
        $this->expectException(InvalidFieldFormatException::class);

        $iso     = new Iso8583(new StandardTestSpec());
        $message = $iso->makeMessage('0200');
        $message->setField(Field::AMOUNT_TRANSACTION, 'NOT_NUMERIC');
        $iso->build($message);
    }

    public function test_build_throws_for_value_exceeding_max_length_fixed(): void
    {
        $this->expectException(InvalidFieldLengthException::class);

        $iso     = new Iso8583(new StandardTestSpec());
        $message = $iso->makeMessage('0200');
        $message->setField(Field::SYSTEM_TRACE_AUDIT_NUMBER, '1234567890');
        $iso->build($message);
    }

    public function test_build_throws_for_value_exceeding_max_length_llvar(): void
    {
        $this->expectException(InvalidFieldLengthException::class);

        $iso     = new Iso8583(new StandardTestSpec());
        $message = $iso->makeMessage('0200');
        $message->setField(Field::PRIMARY_ACCOUNT_NUMBER, str_repeat('1', 20));
        $iso->build($message);
    }

    // =========================================================================
    // 9. PARSER ERRORS (Negative Cases)
    // =========================================================================

    public function test_parse_throws_for_empty_string(): void
    {
        $this->expectException(InvalidMtiException::class);
        $iso = new Iso8583(new StandardTestSpec());
        $iso->parse('');
    }

    public function test_parse_throws_for_string_too_short_for_bitmap(): void
    {
        $this->expectException(ParseException::class);
        $iso = new Iso8583(new StandardTestSpec());
        $iso->parse('0200SHORT');
    }

    public function test_parse_throws_for_truncated_field_data(): void
    {
        $this->expectException(ParseException::class);

        $iso     = new Iso8583(new StandardTestSpec());
        $message = $iso->makeMessage('0200');
        $message->setField(Field::AMOUNT_TRANSACTION, '000000010000');
        $raw = $iso->build($message);

        $iso->parse(substr($raw, 0, -5));
    }

    // =========================================================================
    // 10. TRANSFORMER TESTS
    // =========================================================================

    public function test_positional_transformer_parse_and_build(): void
    {
        $iso     = new Iso8583(new TransformerTestSpec());
        $message = $iso->makeMessage('0200');

        $message->setField(Field::PROCESSING_CODE, [
            'trx_type' => '01',
            'from_acc' => '10',
            'to_acc'   => '20',
        ]);

        $raw    = $iso->build($message);
        $parsed = $iso->parse($raw);

        $value = $parsed->getField(Field::PROCESSING_CODE);
        $this->assertIsArray($value);
        $this->assertSame('01', $value['trx_type']);
        $this->assertSame('10', $value['from_acc']);
        $this->assertSame('20', $value['to_acc']);
    }

    public function test_tlv_transformer_parse_and_build(): void
    {
        $iso     = new Iso8583(new TransformerTestSpec());
        $message = $iso->makeMessage('0200');

        $message->setField(Field::RESERVED_ISO_55, [
            '9F02' => '000000000100',
            '9F03' => '000000000000',
        ]);

        $raw    = $iso->build($message);
        $parsed = $iso->parse($raw);

        $emv = $parsed->getField(Field::RESERVED_ISO_55);
        $this->assertIsArray($emv);
        $this->assertSame('000000000100', $emv['9F02']);
        $this->assertSame('000000000000', $emv['9F03']);
    }

    public function test_token_transformer_parse_and_build(): void
    {
        $iso     = new Iso8583(new TransformerTestSpec());
        $message = $iso->makeMessage('0200');

        $message->setField(Field::RESERVED_PRIVATE_62, [
            'B2' => 'DATA_TOKEN_B2',
            'C0' => 'DATA',
        ]);

        $raw    = $iso->build($message);
        $parsed = $iso->parse($raw);

        $token = $parsed->getField(Field::RESERVED_PRIVATE_62);
        $this->assertIsArray($token);
        $this->assertSame('DATA_TOKEN_B2', $token['B2']);
        $this->assertSame('DATA', $token['C0']);
    }
}
