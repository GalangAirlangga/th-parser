<?php

namespace TerminalHero\Iso8583\Specs;

use TerminalHero\Iso8583\Enums\Field;
use TerminalHero\Iso8583\Enums\Format;
use TerminalHero\Iso8583\Enums\LengthType;

class Standard1987Spec extends BaseSpec
{
    public function define(): void
    {
        $this->set(Field::PRIMARY_ACCOUNT_NUMBER, Format::NUMERIC, 19, LengthType::LLVAR);
        $this->set(Field::PROCESSING_CODE, Format::NUMERIC, 6, LengthType::FIXED);
        $this->set(Field::AMOUNT_TRANSACTION, Format::NUMERIC, 12, LengthType::FIXED);
        $this->set(Field::AMOUNT_SETTLEMENT, Format::NUMERIC, 12, LengthType::FIXED);
        $this->set(Field::AMOUNT_CARDHOLDER_BILLING, Format::NUMERIC, 12, LengthType::FIXED);
        $this->set(Field::TRANSMISSION_DATE_TIME, Format::NUMERIC, 10, LengthType::FIXED);
        $this->set(Field::AMOUNT_CARDHOLDER_BILLING_FEE, Format::NUMERIC, 8, LengthType::FIXED);
        $this->set(Field::CONVERSION_RATE_SETTLEMENT, Format::NUMERIC, 8, LengthType::FIXED);
        $this->set(Field::CONVERSION_RATE_CARDHOLDER_BILLING, Format::NUMERIC, 8, LengthType::FIXED);
        $this->set(Field::SYSTEM_TRACE_AUDIT_NUMBER, Format::NUMERIC, 6, LengthType::FIXED);
        $this->set(Field::TIME_LOCAL_TRANSACTION, Format::NUMERIC, 6, LengthType::FIXED);
        $this->set(Field::DATE_LOCAL_TRANSACTION, Format::NUMERIC, 4, LengthType::FIXED);
        $this->set(Field::DATE_EXPIRATION, Format::NUMERIC, 4, LengthType::FIXED);
        $this->set(Field::DATE_SETTLEMENT, Format::NUMERIC, 4, LengthType::FIXED);
        $this->set(Field::DATE_CONVERSION, Format::NUMERIC, 4, LengthType::FIXED);
        $this->set(Field::DATE_CAPTURE, Format::NUMERIC, 4, LengthType::FIXED);
        $this->set(Field::MERCHANT_TYPE, Format::NUMERIC, 4, LengthType::FIXED);
        $this->set(Field::ACQUIRING_INSTITUTION_COUNTRY_CODE, Format::NUMERIC, 3, LengthType::FIXED);
        $this->set(Field::PAN_EXTENDED_COUNTRY_CODE, Format::NUMERIC, 3, LengthType::FIXED);
        $this->set(Field::FORWARDING_INSTITUTION_COUNTRY_CODE, Format::NUMERIC, 3, LengthType::FIXED);
        $this->set(Field::POINT_OF_SERVICE_ENTRY_MODE, Format::NUMERIC, 3, LengthType::FIXED);
        $this->set(Field::CARD_SEQUENCE_NUMBER, Format::NUMERIC, 3, LengthType::FIXED);
        $this->set(Field::NETWORK_INTERNATIONAL_IDENTIFIER, Format::NUMERIC, 3, LengthType::FIXED);
        $this->set(Field::POINT_OF_SERVICE_CONDITION_CODE, Format::NUMERIC, 2, LengthType::FIXED);
        $this->set(Field::POINT_OF_SERVICE_CAPTURE_CODE, Format::NUMERIC, 2, LengthType::FIXED);
        $this->set(Field::AUTHORIZING_IDENTIFICATION_RESPONSE_LENGTH, Format::NUMERIC, 1, LengthType::FIXED);
        $this->set(Field::AMOUNT_TRANSACTION_FEE, Format::SPECIAL, 8, LengthType::FIXED);
        $this->set(Field::AMOUNT_SETTLEMENT_FEE, Format::SPECIAL, 8, LengthType::FIXED);
        $this->set(Field::AMOUNT_TRANSACTION_PROCESSING_FEE, Format::SPECIAL, 8, LengthType::FIXED);
        $this->set(Field::AMOUNT_SETTLEMENT_PROCESSING_FEE, Format::SPECIAL, 8, LengthType::FIXED);
        $this->set(Field::ACQUIRING_INSTITUTION_IDENTIFICATION_CODE, Format::NUMERIC, 11, LengthType::LLVAR);
        $this->set(Field::FORWARDING_INSTITUTION_IDENTIFICATION_CODE, Format::NUMERIC, 11, LengthType::LLVAR);
        $this->set(Field::PRIMARY_ACCOUNT_NUMBER_EXTENDED, Format::NUMERIC, 28, LengthType::LLVAR);
        $this->set(Field::TRACK_2_DATA, Format::TRACK_DATA, 37, LengthType::LLVAR);
        $this->set(Field::TRACK_3_DATA, Format::NUMERIC, 104, LengthType::LLLVAR);
        $this->set(Field::RETRIEVAL_REFERENCE_NUMBER, Format::ALPHA_NUMERIC, 12, LengthType::FIXED);
        $this->set(Field::AUTHORIZATION_IDENTIFICATION_RESPONSE, Format::ALPHA_NUMERIC, 6, LengthType::FIXED);
        $this->set(Field::RESPONSE_CODE, Format::ALPHA_NUMERIC, 2, LengthType::FIXED);
        $this->set(Field::SERVICE_RESTRICTION_CODE, Format::ALPHA_NUMERIC, 3, LengthType::FIXED);
        $this->set(Field::CARD_ACCEPTOR_TERMINAL_IDENTIFICATION, Format::ALPHA_NUMERIC_SPECIAL, 8, LengthType::FIXED);
        $this->set(Field::CARD_ACCEPTOR_IDENTIFICATION_CODE, Format::ALPHA_NUMERIC_SPECIAL, 15, LengthType::FIXED);
        $this->set(Field::CARD_ACCEPTOR_NAME_LOCATION, Format::ALPHA_NUMERIC_SPECIAL, 40, LengthType::FIXED);
        $this->set(Field::ADDITIONAL_RESPONSE_DATA, Format::ALPHA_NUMERIC, 25, LengthType::LLVAR);
        $this->set(Field::TRACK_1_DATA, Format::ALPHA_NUMERIC, 76, LengthType::LLVAR);
        $this->set(Field::ADDITIONAL_DATA_ISO, Format::ALPHA_NUMERIC, 999, LengthType::LLLVAR);
        $this->set(Field::ADDITIONAL_DATA_NATIONAL, Format::ALPHA_NUMERIC, 999, LengthType::LLLVAR);
        $this->set(Field::ADDITIONAL_DATA_PRIVATE, Format::ALPHA_NUMERIC, 999, LengthType::LLLVAR);
        $this->set(Field::CURRENCY_CODE_TRANSACTION, Format::NUMERIC, 3, LengthType::FIXED);
        $this->set(Field::CURRENCY_CODE_SETTLEMENT, Format::NUMERIC, 3, LengthType::FIXED);
        $this->set(Field::CURRENCY_CODE_CARDHOLDER_BILLING, Format::NUMERIC, 3, LengthType::FIXED);
        $this->set(Field::PERSONAL_IDENTIFICATION_NUMBER_DATA, Format::BINARY, 16, LengthType::FIXED);
        $this->set(Field::SECURITY_RELATED_CONTROL_INFORMATION, Format::NUMERIC, 16, LengthType::FIXED);
        $this->set(Field::ADDITIONAL_AMOUNTS, Format::ALPHA_NUMERIC, 120, LengthType::LLLVAR);
        $this->set(Field::RESERVED_ISO_55, Format::BINARY, 999, LengthType::LLLVAR);
        $this->set(Field::RESERVED_ISO_56, Format::ALPHA_NUMERIC, 999, LengthType::LLLVAR);
        $this->set(Field::RESERVED_NATIONAL_57, Format::ALPHA_NUMERIC, 999, LengthType::LLLVAR);
        $this->set(Field::RESERVED_NATIONAL_58, Format::ALPHA_NUMERIC, 999, LengthType::LLLVAR);
        $this->set(Field::RESERVED_NATIONAL_59, Format::ALPHA_NUMERIC, 999, LengthType::LLLVAR);
        $this->set(Field::RESERVED_PRIVATE_60, Format::ALPHA_NUMERIC, 999, LengthType::LLLVAR);
        $this->set(Field::RESERVED_PRIVATE_61, Format::ALPHA_NUMERIC, 999, LengthType::LLLVAR);
        $this->set(Field::RESERVED_PRIVATE_62, Format::ALPHA_NUMERIC, 999, LengthType::LLLVAR);
        $this->set(Field::RESERVED_PRIVATE_63, Format::ALPHA_NUMERIC, 999, LengthType::LLLVAR);
        $this->set(Field::MESSAGE_AUTHENTICATION_CODE, Format::BINARY, 16, LengthType::FIXED);
        $this->set(Field::SECONDARY_BITMAP, Format::BINARY, 16, LengthType::FIXED);
        $this->set(Field::ORIGINAL_DATA_ELEMENTS, Format::NUMERIC, 42, LengthType::FIXED);
        $this->set(Field::RECEIVING_INSTITUTION_IDENTIFICATION_CODE, Format::NUMERIC, 11, LengthType::LLVAR);
        $this->set(Field::ACCOUNT_IDENTIFICATION_1, Format::ALPHA_NUMERIC_SPECIAL, 28, LengthType::LLVAR);
        $this->set(Field::ACCOUNT_IDENTIFICATION_2, Format::ALPHA_NUMERIC_SPECIAL, 28, LengthType::LLVAR);
        $this->set(Field::TRANSACTION_DESCRIPTION, Format::ALPHA_NUMERIC_SPECIAL, 999, LengthType::LLLVAR);
        $this->set(Field::MESSAGE_AUTHENTICATION_CODE_2, Format::BINARY, 16, LengthType::FIXED);
    }
}
