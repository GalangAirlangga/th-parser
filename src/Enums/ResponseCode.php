<?php

namespace TerminalHero\Iso8583\Enums;

/**
 * Standard ISO 8583 Response Codes (Bit 39).
 *
 * Developers can use these Enum cases directly or pass raw strings if a vendor uses custom codes.
 * Example:
 * ```php
 * $message->setResponseCode(ResponseCode::APPROVED);
 * $message->setResponseCode('00'); // Also valid!
 * ```
 */
enum ResponseCode: string
{
    case APPROVED = '00';
    case REFER_TO_CARD_ISSUER = '01';
    case REFER_TO_CARD_ISSUER_SPECIAL = '02';
    case INVALID_MERCHANT = '03';
    case PICK_UP_CARD = '04';
    case DO_NOT_HONOR = '05';
    case ERROR = '06';
    case PICK_UP_CARD_SPECIAL = '07';
    case HONOR_WITH_IDENTIFICATION = '08';
    case REQUEST_IN_PROGRESS = '09';
    case APPROVED_PARTIAL = '10';
    case VIP_APPROVAL = '11';
    case INVALID_TRANSACTION = '12';
    case INVALID_AMOUNT = '13';
    case INVALID_CARD_NUMBER = '14';
    case NO_SUCH_ISSUER = '15';
    case APPROVED_UPDATE_TRACK3 = '16';
    case CUSTOMER_CANCELLATION = '17';
    case RE_ENTER_TRANSACTION = '19';
    case INVALID_RESPONSE = '20';
    case NO_ACTION_TAKEN = '21';
    case SUSPECTED_MALFUNCTION = '22';
    case UNACCEPTABLE_TRANSACTION_FEE = '23';
    case UNABLE_TO_LOCATE_RECORD = '25';
    case DUPLICATE_RECORD = '26';
    case FIELD_EDIT_ERROR = '30';
    case BANK_NOT_SUPPORTED = '31';
    case COMPLETED_PARTIALLY = '32';
    case EXPIRED_CARD_PICK_UP = '33';
    case SUSPECTED_FRAUD = '34';
    case CARD_ACCEPTOR_CONTACT_ACQUIRER = '36';
    case RESTRICTED_CARD = '37';
    case ALLOWABLE_PIN_TRIES_EXCEEDED = '38';
    case NO_CREDIT_ACCOUNT = '39';
    case REQUESTED_FUNCTION_NOT_SUPPORTED = '40';
    case LOST_CARD = '41';
    case NO_UNIVERSAL_ACCOUNT = '42';
    case STOLEN_CARD = '43';
    case NO_INVESTMENT_ACCOUNT = '44';
    case INSUFFICIENT_FUNDS = '51';
    case NO_CHECKING_ACCOUNT = '52';
    case NO_SAVINGS_ACCOUNT = '53';
    case EXPIRED_CARD = '54';
    case INCORRECT_PIN = '55';
    case NO_CARD_RECORD = '56';
    case TRANSACTION_NOT_ALLOWED_TO_CARDHOLDER = '57';
    case TRANSACTION_NOT_ALLOWED_TO_TERMINAL = '58';
    case SUSPECTED_FRAUD_59 = '59';
    case EXCEEDS_WITHDRAWAL_LIMIT = '61';
    case RESTRICTED_CARD_62 = '62';
    case SECURITY_VIOLATION = '63';
    case EXCEEDS_FREQUENCY_LIMIT = '65';
    case HARD_CAPTURE = '67';
    case RESPONSE_RECEIVED_TOO_LATE = '68';
    case ALLOWABLE_NUMBER_OF_PIN_TRIES_EXCEEDED = '75';
    case INVALID_DESTINATION = '91';
    case SYSTEM_MALFUNCTION = '96';

    /**
     * Get a human-readable description for the response code.
     */
    public function description(): string
    {
        return match ($this) {
            self::APPROVED => 'Approved or completed successfully',
            self::REFER_TO_CARD_ISSUER => 'Refer to card issuer',
            self::INVALID_MERCHANT => 'Invalid merchant',
            self::PICK_UP_CARD => 'Pick up card',
            self::DO_NOT_HONOR => 'Do not honor',
            self::INVALID_TRANSACTION => 'Invalid transaction',
            self::INVALID_AMOUNT => 'Invalid amount',
            self::INVALID_CARD_NUMBER => 'Invalid card number (no such number)',
            self::EXPIRED_CARD_PICK_UP, self::EXPIRED_CARD => 'Expired card',
            self::INSUFFICIENT_FUNDS => 'Insufficient funds',
            self::INCORRECT_PIN => 'Incorrect PIN',
            self::TRANSACTION_NOT_ALLOWED_TO_CARDHOLDER => 'Transaction not allowed to cardholder',
            self::SECURITY_VIOLATION => 'Security violation',
            self::EXCEEDS_WITHDRAWAL_LIMIT => 'Exceeds withdrawal amount limit',
            self::EXCEEDS_FREQUENCY_LIMIT => 'Exceeds withdrawal frequency limit',
            self::SYSTEM_MALFUNCTION => 'System malfunction',
            default => 'Response code ' . $this->value,
        };
    }
}
