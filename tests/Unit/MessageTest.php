<?php

namespace TerminalHero\Iso8583\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TerminalHero\Iso8583\Enums\Direction;
use TerminalHero\Iso8583\Enums\Field;
use TerminalHero\Iso8583\Enums\ResponseCode;
use TerminalHero\Iso8583\Message;

/**
 * Unit tests for the Message class.
 *
 * Covers all getters, setters, shortcuts, jPOS cloning, response creation, masking, and JSON serialization.
 */
class MessageTest extends TestCase
{
    private Message $message;

    protected function setUp(): void
    {
        $this->message = new Message();
    }

    // =========================================================================
    // MTI & Direction Tests
    // =========================================================================

    public function test_mti_is_empty_by_default(): void
    {
        $this->assertSame('', $this->message->getMti());
    }

    public function test_set_and_get_mti(): void
    {
        $this->message->setMti('0200');
        $this->assertSame('0200', $this->message->getMti());
    }

    public function test_direction_defaults_to_outgoing(): void
    {
        $this->assertSame(Direction::OUTGOING, $this->message->getDirection());
    }

    public function test_set_and_get_direction(): void
    {
        $this->message->setDirection(Direction::INCOMING);
        $this->assertSame(Direction::INCOMING, $this->message->getDirection());
    }

    // =========================================================================
    // Shortcut Getters & Setters
    // =========================================================================

    public function test_shortcut_pan(): void
    {
        $this->message->setPan('4111111111111111');
        $this->assertSame('4111111111111111', $this->message->getPan());
        $this->assertSame('4111111111111111', $this->message->getField(Field::PRIMARY_ACCOUNT_NUMBER));
    }

    public function test_shortcut_amount(): void
    {
        $this->message->setAmount('000000010000');
        $this->assertSame('000000010000', $this->message->getAmount());
        $this->assertSame('000000010000', $this->message->getField(Field::AMOUNT_TRANSACTION));
    }

    public function test_shortcut_stan(): void
    {
        $this->message->setStan('123456');
        $this->assertSame('123456', $this->message->getStan());
        $this->assertSame('123456', $this->message->getField(Field::SYSTEM_TRACE_AUDIT_NUMBER));
    }

    public function test_shortcut_processing_code(): void
    {
        $this->message->setProcessingCode('000000');
        $this->assertSame('000000', $this->message->getProcessingCode());
    }

    public function test_shortcut_date_time(): void
    {
        $this->message->setDateTime('0820193000');
        $this->assertSame('0820193000', $this->message->getDateTime());
    }

    public function test_shortcut_rrn(): void
    {
        $this->message->setRrn('123456789012');
        $this->assertSame('123456789012', $this->message->getRrn());
    }

    public function test_shortcut_response_code_string_and_enum(): void
    {
        $this->message->setResponseCode('00');
        $this->assertSame('00', $this->message->getResponseCode());

        $this->message->setResponseCode(ResponseCode::INSUFFICIENT_FUNDS);
        $this->assertSame('51', $this->message->getResponseCode());
    }

    public function test_shortcut_terminal_and_merchant_id(): void
    {
        $this->message->setTerminalId('TERM0001');
        $this->message->setMerchantId('MERCHANT123');

        $this->assertSame('TERM0001', $this->message->getTerminalId());
        $this->assertSame('MERCHANT123', $this->message->getMerchantId());
    }

    // =========================================================================
    // Classification Helpers
    // =========================================================================

    public function test_is_approved(): void
    {
        $this->message->setResponseCode('00');
        $this->assertTrue($this->message->isApproved());

        $this->message->setResponseCode('51');
        $this->assertFalse($this->message->isApproved());
    }

    public function test_is_request_and_is_response(): void
    {
        $req = new Message('0200');
        $this->assertTrue($req->isRequest());
        $this->assertFalse($req->isResponse());

        $res = new Message('0210');
        $this->assertFalse($res->isRequest());
        $this->assertTrue($res->isResponse());

        $netReq = new Message('0800');
        $this->assertTrue($netReq->isRequest());
        $this->assertFalse($netReq->isResponse());

        $netRes = new Message('0810');
        $this->assertFalse($netRes->isRequest());
        $this->assertTrue($netRes->isResponse());
    }

    public function test_is_reversal(): void
    {
        $rev = new Message('0400');
        $this->assertTrue($rev->isReversal());

        $revRepeat = new Message('0420');
        $this->assertTrue($revRepeat->isReversal());

        $req = new Message('0200');
        $this->assertFalse($req->isReversal());
    }

    // =========================================================================
    // jPOS Field Utilities (hasFields, removeFields)
    // =========================================================================

    public function test_has_fields_returns_true_only_if_all_present(): void
    {
        $this->message->setStan('123456');
        $this->message->setAmount('100');

        $this->assertTrue($this->message->hasFields([Field::SYSTEM_TRACE_AUDIT_NUMBER, Field::AMOUNT_TRANSACTION]));
        $this->assertFalse($this->message->hasFields([Field::SYSTEM_TRACE_AUDIT_NUMBER, Field::PRIMARY_ACCOUNT_NUMBER]));
    }

    public function test_remove_fields_bulk(): void
    {
        $this->message->setStan('123456');
        $this->message->setAmount('100');
        $this->message->setPan('4111111111111111');

        $this->message->removeFields([Field::SYSTEM_TRACE_AUDIT_NUMBER, Field::AMOUNT_TRANSACTION]);

        $this->assertFalse($this->message->hasField(Field::SYSTEM_TRACE_AUDIT_NUMBER));
        $this->assertFalse($this->message->hasField(Field::AMOUNT_TRANSACTION));
        $this->assertTrue($this->message->hasField(Field::PRIMARY_ACCOUNT_NUMBER));
    }

    // =========================================================================
    // jPOS Cloning & createResponse
    // =========================================================================

    public function test_clone_copies_all_fields_when_null(): void
    {
        $this->message->setMti('0200');
        $this->message->setHeader('ISO123');
        $this->message->setStan('123456');
        $this->message->setAmount('1000');

        $cloned = $this->message->clone();

        $this->assertSame('0200', $cloned->getMti());
        $this->assertSame('ISO123', $cloned->getHeader());
        $this->assertSame('123456', $cloned->getStan());
        $this->assertSame('1000', $cloned->getAmount());
    }

    public function test_clone_copies_only_specified_fields(): void
    {
        $this->message->setMti('0200');
        $this->message->setStan('123456');
        $this->message->setAmount('1000');
        $this->message->setPan('4111111111111111');

        $cloned = $this->message->clone([Field::SYSTEM_TRACE_AUDIT_NUMBER]);

        $this->assertSame('123456', $cloned->getStan());
        $this->assertFalse($cloned->hasField(Field::AMOUNT_TRANSACTION));
        $this->assertFalse($cloned->hasField(Field::PRIMARY_ACCOUNT_NUMBER));
    }

    public function test_create_response_auto_calculates_mti_and_copies_trace_fields(): void
    {
        $request = (new Message('0200'))
            ->setHeader('ISO0150000')
            ->setPan('4111111111111111')
            ->setProcessingCode('000000')
            ->setAmount('000000010000')
            ->setStan('123456')
            ->setRrn('987654321012')
            ->setTerminalId('TERM0001')
            ->setMerchantId('MERCHANT123');

        $response = $request->createResponse(ResponseCode::APPROVED);

        $this->assertSame('0210', $response->getMti());
        $this->assertSame('ISO0150000', $response->getHeader());
        $this->assertSame('00', $response->getResponseCode());
        $this->assertTrue($response->isApproved());
        $this->assertSame('4111111111111111', $response->getPan());
        $this->assertSame('123456', $response->getStan());
        $this->assertSame('987654321012', $response->getRrn());
        $this->assertSame('TERM0001', $response->getTerminalId());
    }

    public function test_create_response_handles_network_and_reversal_mtis(): void
    {
        $netReq = new Message('0800');
        $netRes = $netReq->createResponse();
        $this->assertSame('0810', $netRes->getMti());

        $revReq = new Message('0420');
        $revRes = $revReq->createResponse();
        $this->assertSame('0430', $revRes->getMti());
    }

    public function test_create_response_with_copy_all_fields_clones_entire_request(): void
    {
        $request = (new Message('0200'))
            ->setPan('4111111111111111')
            ->setField(Field::RESERVED_PRIVATE_62, 'CUSTOM_DATA');

        $response = $request->createResponse(ResponseCode::APPROVED, null, true);

        $this->assertSame('0210', $response->getMti());
        $this->assertSame('CUSTOM_DATA', $response->getField(Field::RESERVED_PRIVATE_62));
    }

    // =========================================================================
    // PCI-DSS Masking Tests
    // =========================================================================

    public function test_to_masked_array_masks_pan_and_track2_and_pin(): void
    {
        $msg = (new Message('0200'))
            ->setPan('4111111111111111')
            ->setField(Field::TRACK_2_DATA, '4111111111111111=25121010000000000000')
            ->setField(Field::PERSONAL_IDENTIFICATION_NUMBER_DATA, '1234567890ABCDEF');

        $masked = $msg->toMaskedArray();

        $this->assertSame('411111******1111', $masked['fields'][Field::PRIMARY_ACCOUNT_NUMBER]);
        $this->assertSame('411111******1111=25121010000000000000', $masked['fields'][Field::TRACK_2_DATA]);
        $this->assertSame('***MASKED***', $masked['fields'][Field::PERSONAL_IDENTIFICATION_NUMBER_DATA]);
    }

    public function test_custom_mask_char_and_unmasked_lengths(): void
    {
        $msg = (new Message('0200'))->setPan('4111111111111111');

        $masked = $msg->toMaskedArray('X', 4, 4);

        $this->assertSame('4111XXXXXXXX1111', $masked['fields'][Field::PRIMARY_ACCOUNT_NUMBER]);
    }

    // =========================================================================
    // JSON Serialization & Deserialization
    // =========================================================================

    public function test_json_encode_and_decode_recreates_message(): void
    {
        $msg = (new Message('0200'))
            ->setHeader('ISO0150000')
            ->setPan('4111111111111111')
            ->setStan('123456');

        $json = $msg->toJson();

        $this->assertJson($json);

        $reconstructed = Message::fromJson($json);

        $this->assertSame('0200', $reconstructed->getMti());
        $this->assertSame('ISO0150000', $reconstructed->getHeader());
        $this->assertSame('4111111111111111', $reconstructed->getPan());
        $this->assertSame('123456', $reconstructed->getStan());
    }

    // =========================================================================
    // Field Aliases Tests
    // =========================================================================

    public function test_aliases_set_get_has_remove(): void
    {
        $spec = new class extends \TerminalHero\Iso8583\Specs\Standard1987Spec {
            public function define(): void {
                parent::define();
                $this->alias('card_number', Field::PRIMARY_ACCOUNT_NUMBER);
                $this->alias('amount', Field::AMOUNT_TRANSACTION);
            }
        };

        $msg = new Message('0200');

        $msg->setByAlias('card_number', '4111111111111111', $spec);
        $msg->setByAlias('amount', '000000010000', $spec);

        $this->assertTrue($msg->hasAlias('card_number', $spec));
        $this->assertSame('4111111111111111', $msg->getByAlias('card_number', $spec));
        $this->assertSame('4111111111111111', $msg->getPan());

        $msg->removeByAlias('card_number', $spec);
        $this->assertFalse($msg->hasAlias('card_number', $spec));
    }

    // =========================================================================
    // Message Diff Tests
    // =========================================================================

    public function test_message_diff_identifies_added_removed_modified_unchanged(): void
    {
        $msg1 = (new Message('0200'))
            ->setStan('123456')
            ->setAmount('000000010000')
            ->setField(Field::CARD_ACCEPTOR_TERMINAL_IDENTIFICATION, 'TERM1');

        $msg2 = (new Message('0210'))
            ->setStan('123456')
            ->setAmount('000000020000') // modified
            ->setResponseCode('00'); // added (Field 41 was removed)

        $diff = $msg1->diff($msg2);

        $this->assertArrayHasKey(Field::RESPONSE_CODE, $diff['added']);
        $this->assertArrayHasKey(Field::CARD_ACCEPTOR_TERMINAL_IDENTIFICATION, $diff['removed']);
        $this->assertArrayHasKey(Field::AMOUNT_TRANSACTION, $diff['modified']);
        $this->assertArrayHasKey(Field::SYSTEM_TRACE_AUDIT_NUMBER, $diff['unchanged']);

        $this->assertSame('00', $diff['added'][Field::RESPONSE_CODE]);
        $this->assertSame('TERM1', $diff['removed'][Field::CARD_ACCEPTOR_TERMINAL_IDENTIFICATION]);
        $this->assertSame('000000010000', $diff['modified'][Field::AMOUNT_TRANSACTION]['from']);
        $this->assertSame('000000020000', $diff['modified'][Field::AMOUNT_TRANSACTION]['to']);
    }
}
