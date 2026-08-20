<?php

namespace TerminalHero\Iso8583\Specs;

use TerminalHero\Iso8583\Enums\Field;
use TerminalHero\Iso8583\Enums\Format;
use TerminalHero\Iso8583\Enums\LengthType;

/**
 * Standard ISO 8583:2003 Specification definition.
 *
 * Inherits the 1993 specification and applies the 2003 revision changes.
 */
class Standard2003Spec extends Standard1993Spec
{
    public function define(): void
    {
        parent::define();

        // 2003 revision updates
        $this->set(Field::PRIMARY_ACCOUNT_NUMBER, Format::NUMERIC, 19, LengthType::LLVAR);
        $this->set(Field::RETRIEVAL_REFERENCE_NUMBER, Format::ALPHA_NUMERIC, 12, LengthType::FIXED);
        $this->set(Field::TRANSACTION_DESCRIPTION, Format::ALPHA_NUMERIC_SPECIAL, 999, LengthType::LLLVAR);
    }
}
