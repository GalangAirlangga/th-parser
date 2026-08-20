<?php

namespace TerminalHero\Iso8583\Specs;

use TerminalHero\Iso8583\Enums\Field;
use TerminalHero\Iso8583\Enums\Format;
use TerminalHero\Iso8583\Enums\LengthType;

/**
 * Standard ISO 8583:1993 Specification definition.
 *
 * Inherits the 1987 baseline specification and applies the 1993 revision changes.
 */
class Standard1993Spec extends Standard1987Spec
{
    public function define(): void
    {
        parent::define();

        // 1993 revision updates
        $this->set(Field::POINT_OF_SERVICE_ENTRY_MODE, Format::NUMERIC, 4, LengthType::FIXED);
        $this->set(Field::CARD_SEQUENCE_NUMBER, Format::NUMERIC, 3, LengthType::FIXED);
        $this->set(Field::AMOUNT_TRANSACTION_FEE, Format::ALPHA_NUMERIC_SPECIAL, 9, LengthType::FIXED);
        $this->set(Field::AMOUNT_SETTLEMENT_FEE, Format::ALPHA_NUMERIC_SPECIAL, 9, LengthType::FIXED);
        $this->set(Field::ADDITIONAL_AMOUNTS, Format::ALPHA_NUMERIC_SPECIAL, 999, LengthType::LLLVAR);
    }
}
