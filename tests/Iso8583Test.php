<?php

namespace TerminalHero\Iso8583\Tests;

use TerminalHero\Iso8583\Facades\Iso8583;
use TerminalHero\Iso8583\Iso8583 as CoreIso8583;
use TerminalHero\Iso8583\Enums\Field;
use TerminalHero\Iso8583\Enums\Format;
use TerminalHero\Iso8583\Enums\LengthType;
use TerminalHero\Iso8583\Specs\Standard1987Spec;
use TerminalHero\Iso8583\Transformers\PositionalTransformer;
use TerminalHero\Iso8583\Transformers\TlvTransformer;
use TerminalHero\Iso8583\Transformers\TokenTransformer;

class CustomSpec extends Standard1987Spec
{
    public function define(): void
    {
        parent::define();

        // Custom override for testing transformers
        $this->set(Field::PROCESSING_CODE, Format::NUMERIC, 6, LengthType::FIXED)
             ->setTransformer(new PositionalTransformer([
                 'trx_type' => 2,
                 'from_acc' => 2,
                 'to_acc'   => 2
             ]));
             
        $this->set(Field::RESERVED_ISO_55, Format::BINARY, 999, LengthType::LLLVAR)
             ->setTransformer(new TlvTransformer());

        $this->set(Field::RESERVED_PRIVATE_62, Format::ALPHA_NUMERIC, 999, LengthType::LLLVAR)
             ->setTransformer(new TokenTransformer('!', 2, 4));
    }
}

class Iso8583Test extends TestCase
{
    public function test_can_build_and_parse_simple_message()
    {
        $mti = '0200';
        $message = Iso8583::makeMessage($mti);
        $message->setField(Field::PROCESSING_CODE, '000000');
        $message->setField(Field::AMOUNT_TRANSACTION, '000000010000');
        
        $raw = Iso8583::build($message);
        
        $parsed = Iso8583::parse($raw);
        $this->assertEquals('0200', $parsed->getMti());
        $this->assertEquals('000000', $parsed->getField(Field::PROCESSING_CODE));
        $this->assertEquals('000000010000', $parsed->getField(Field::AMOUNT_TRANSACTION));
    }
    
    public function test_can_handle_transformers()
    {
        // Use the custom spec
        $iso = new CoreIso8583(new CustomSpec());
        
        $message = $iso->makeMessage('0200');
        
        // Positional
        $message->setField(Field::PROCESSING_CODE, [
            'trx_type' => '01',
            'from_acc' => '10',
            'to_acc'   => '20'
        ]);
        
        // TLV
        $message->setField(Field::RESERVED_ISO_55, [
            '9F02' => '000000000100',
            '9F03' => '000000000000',
        ]);
        
        // Token
        $message->setField(Field::RESERVED_PRIVATE_62, [
            'B2' => 'DATA_TOKEN_B2',
            'C0' => 'DATA'
        ]);
        
        $raw = $iso->build($message);
        
        $parsed = $iso->parse($raw);
        
        $procCode = $parsed->getField(Field::PROCESSING_CODE);
        $this->assertIsArray($procCode);
        $this->assertEquals('01', $procCode['trx_type']);
        $this->assertEquals('10', $procCode['from_acc']);
        $this->assertEquals('20', $procCode['to_acc']);
        
        $emv = $parsed->getField(Field::RESERVED_ISO_55);
        $this->assertIsArray($emv);
        $this->assertEquals('000000000100', $emv['9F02']);
        $this->assertEquals('000000000000', $emv['9F03']);
        
        $token = $parsed->getField(Field::RESERVED_PRIVATE_62);
        $this->assertIsArray($token);
        $this->assertEquals('DATA_TOKEN_B2', $token['B2']);
        $this->assertEquals('DATA', $token['C0']);
    }
}
