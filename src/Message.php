<?php

namespace TerminalHero\Iso8583;

use TerminalHero\Iso8583\Enums\Direction;
use TerminalHero\Iso8583\Enums\Field;
use TerminalHero\Iso8583\Enums\ResponseCode;
use JsonSerializable;
use InvalidArgumentException;

/**
 * Represents a single ISO8583 financial message.
 *
 * This is the central data object of the library. It holds all the components
 * of a parsed message (or a message being assembled for sending), and provides
 * a clean, type-safe API for reading and writing each component.
 *
 * Inspired by jPOS ISOMsg with modern PHP & Laravel DX enhancements.
 *
 * Components of an ISO8583 message:
 * - Header:         Optional prefix (e.g., TPDU) before the MTI.
 * - MTI:            Message Type Indicator (e.g., "0200" for a purchase request).
 * - Bitmap:         A 64 or 128-bit map indicating which data fields are present.
 * - Data Elements:  The actual field values, keyed by bit/field number (2–128).
 * - Direction:     Incoming or Outgoing message tracking.
 *
 * -----------------------------------------------------------------------
 * USAGE EXAMPLE:
 * -----------------------------------------------------------------------
 * ```php
 * // Building a request message
 * $message = (new Message('0200'))
 *     ->setPan('4111111111111111')
 *     ->setAmount('000000010000')
 *     ->setStan('123456');
 *
 * // Generating a response message for a gateway/simulator (jPOS style)
 * $response = $message->createResponse(ResponseCode::APPROVED);
 * echo $response->getMti(); // "0210"
 * echo $response->isApproved(); // true
 *
 * // Masked Array for PCI-DSS compliant logging
 * $logData = $message->toMaskedArray(); // PAN masked as 411111******1111
 * ```
 */
class Message implements JsonSerializable
{
    /**
     * Optional ISO header (e.g., TPDU header like "6000160000").
     */
    protected ?string $header = null;

    /**
     * Message Type Indicator (MTI).
     * A 4-character numeric string indicating the message type.
     */
    protected string $mti = '';

    /**
     * Bitmap stored as a Hexadecimal string.
     */
    protected string $bitmap = '';

    /**
     * Data elements keyed by field/bit number (int) → value (string|array|mixed).
     *
     * @var array<int, mixed>
     */
    protected array $dataElements = [];

    /**
     * Message direction (Incoming or Outgoing).
     */
    protected Direction $direction = Direction::OUTGOING;

    /**
     * @param string $mti Optional MTI to set at construction time.
     */
    public function __construct(string $mti = '')
    {
        $this->mti = $mti;
    }

    // =========================================================================
    // HEADER & DIRECTION
    // =========================================================================

    public function getHeader(): ?string
    {
        return $this->header;
    }

    public function setHeader(?string $header): static
    {
        $this->header = $header;
        return $this;
    }

    public function getDirection(): Direction
    {
        return $this->direction;
    }

    public function setDirection(Direction $direction): static
    {
        $this->direction = $direction;
        return $this;
    }

    // =========================================================================
    // MTI
    // =========================================================================

    public function getMti(): string
    {
        return $this->mti;
    }

    public function setMti(string $mti): static
    {
        $this->mti = $mti;
        return $this;
    }

    // =========================================================================
    // BITMAP
    // =========================================================================

    public function getBitmap(): string
    {
        return $this->bitmap;
    }

    public function setBitmap(string $bitmap): static
    {
        $this->bitmap = $bitmap;
        return $this;
    }

    // =========================================================================
    // DATA ELEMENTS API
    // =========================================================================

    public function getFields(): array
    {
        return $this->dataElements;
    }

    public function getField(int $fieldNumber): mixed
    {
        return $this->dataElements[$fieldNumber] ?? null;
    }

    public function setField(int $fieldNumber, mixed $value): static
    {
        $this->dataElements[$fieldNumber] = $value;
        return $this;
    }

    public function setFields(array $fields): static
    {
        foreach ($fields as $key => $value) {
            $this->setField($key, $value);
        }
        return $this;
    }

    public function removeField(int $fieldNumber): static
    {
        unset($this->dataElements[$fieldNumber]);
        return $this;
    }

    /**
     * Remove multiple fields at once (jPOS inspired).
     *
     * @param int[] $fieldNumbers
     */
    public function removeFields(array $fieldNumbers): static
    {
        foreach ($fieldNumbers as $fieldNumber) {
            $this->removeField($fieldNumber);
        }
        return $this;
    }

    public function hasField(int $fieldNumber): bool
    {
        return isset($this->dataElements[$fieldNumber]);
    }

    /**
     * Check if ALL specified fields are present in the message (jPOS inspired).
     *
     * @param int[] $fieldNumbers
     */
    public function hasFields(array $fieldNumbers): bool
    {
        foreach ($fieldNumbers as $fieldNumber) {
            if (!$this->hasField($fieldNumber)) {
                return false;
            }
        }
        return true;
    }

    // =========================================================================
    // SHORTCUT GETTERS & SETTERS
    // =========================================================================

    /** Bit 2: Primary Account Number (PAN) */
    public function getPan(): ?string
    {
        return $this->getField(Field::PRIMARY_ACCOUNT_NUMBER);
    }

    public function setPan(string $pan): static
    {
        return $this->setField(Field::PRIMARY_ACCOUNT_NUMBER, $pan);
    }

    /** Bit 3: Processing Code */
    public function getProcessingCode(): mixed
    {
        return $this->getField(Field::PROCESSING_CODE);
    }

    public function setProcessingCode(mixed $processingCode): static
    {
        return $this->setField(Field::PROCESSING_CODE, $processingCode);
    }

    /** Bit 4: Transaction Amount */
    public function getAmount(): ?string
    {
        return $this->getField(Field::AMOUNT_TRANSACTION);
    }

    public function setAmount(string $amount): static
    {
        return $this->setField(Field::AMOUNT_TRANSACTION, $amount);
    }

    /** Bit 7: Transmission Date & Time */
    public function getDateTime(): ?string
    {
        return $this->getField(Field::TRANSMISSION_DATE_TIME);
    }

    public function setDateTime(string $dateTime): static
    {
        return $this->setField(Field::TRANSMISSION_DATE_TIME, $dateTime);
    }

    /** Bit 11: System Trace Audit Number (STAN) */
    public function getStan(): ?string
    {
        return $this->getField(Field::SYSTEM_TRACE_AUDIT_NUMBER);
    }

    public function setStan(string $stan): static
    {
        return $this->setField(Field::SYSTEM_TRACE_AUDIT_NUMBER, $stan);
    }

    /** Bit 37: Retrieval Reference Number (RRN) */
    public function getRrn(): ?string
    {
        return $this->getField(Field::RETRIEVAL_REFERENCE_NUMBER);
    }

    public function setRrn(string $rrn): static
    {
        return $this->setField(Field::RETRIEVAL_REFERENCE_NUMBER, $rrn);
    }

    /** Bit 39: Response Code */
    public function getResponseCode(): ?string
    {
        return $this->getField(Field::RESPONSE_CODE);
    }

    public function setResponseCode(string|ResponseCode $responseCode): static
    {
        $value = $responseCode instanceof ResponseCode ? $responseCode->value : $responseCode;
        return $this->setField(Field::RESPONSE_CODE, $value);
    }

    /** Bit 41: Card Acceptor Terminal ID */
    public function getTerminalId(): ?string
    {
        return $this->getField(Field::CARD_ACCEPTOR_TERMINAL_IDENTIFICATION);
    }

    public function setTerminalId(string $terminalId): static
    {
        return $this->setField(Field::CARD_ACCEPTOR_TERMINAL_IDENTIFICATION, $terminalId);
    }

    /** Bit 42: Card Acceptor Identification Code (Merchant ID) */
    public function getMerchantId(): ?string
    {
        return $this->getField(Field::CARD_ACCEPTOR_IDENTIFICATION_CODE);
    }

    public function setMerchantId(string $merchantId): static
    {
        return $this->setField(Field::CARD_ACCEPTOR_IDENTIFICATION_CODE, $merchantId);
    }

    // =========================================================================
    // INSPECTION & CLASSIFICATION HELPERS
    // =========================================================================

    /**
     * Check if the message is an approved response (Bit 39 == '00').
     */
    public function isApproved(): bool
    {
        return $this->getResponseCode() === ResponseCode::APPROVED->value;
    }

    /**
     * Check if this message is a Request (3rd digit of MTI is even: 0, 2, 4).
     */
    public function isRequest(): bool
    {
        if (strlen($this->mti) !== 4) return false;
        $functionDigit = (int) $this->mti[2];
        return ($functionDigit % 2 === 0);
    }

    /**
     * Check if this message is a Response (3rd digit of MTI is odd: 1, 3, 5).
     */
    public function isResponse(): bool
    {
        if (strlen($this->mti) !== 4) return false;
        $functionDigit = (int) $this->mti[2];
        return ($functionDigit % 2 !== 0);
    }

    /**
     * Check if this message is a Reversal message (MTI 2nd digit is 4, e.g. 0400 / 0420).
     */
    public function isReversal(): bool
    {
        if (strlen($this->mti) !== 4) return false;
        return $this->mti[1] === '4';
    }

    // =========================================================================
    // CLONING & RESPONSE CREATION (jPOS inspired)
    // =========================================================================

    /**
     * Clone the message.
     *
     * If $fieldNumbers is provided, ONLY the specified fields will be copied
     * into the new cloned message. If null, ALL fields are copied.
     *
     * @param int[]|null $fieldNumbers
     */
    public function clone(?array $fieldNumbers = null): self
    {
        $clone = new self($this->mti);
        $clone->setHeader($this->header);
        $clone->setDirection($this->direction);

        if ($fieldNumbers === null) {
            $clone->setFields($this->dataElements);
        } else {
            foreach ($fieldNumbers as $fieldNumber) {
                if ($this->hasField($fieldNumber)) {
                    $clone->setField($fieldNumber, $this->getField($fieldNumber));
                }
            }
        }

        return $clone;
    }

    /**
     * Create a Response message based on this Request message (jPOS style).
     *
     * Automatically calculates the response MTI (e.g. 0200 -> 0210, 0800 -> 0810)
     * and sets Response Code (Bit 39).
     *
     * By default ($copyAllFields = false), it copies standard transaction matching fields
     * (PAN, Amount, ProcCode, STAN, RRN, Terminal ID, Merchant ID, Currency) while excluding
     * request-only/sensitive fields like PIN Block (Bit 52) or Track Data (Bit 35).
     *
     * Set $copyAllFields = true if you wish to clone ALL request fields into the response.
     *
     * @param string|ResponseCode $responseCode Default '00' (Approved).
     * @param string|null         $mti          Explicit response MTI, or null to auto-increment.
     * @param bool                $copyAllFields If true, copies ALL fields from request into response.
     */
    public function createResponse(string|ResponseCode $responseCode = '00', ?string $mti = null, bool $copyAllFields = false): self
    {
        if ($mti === null) {
            $mti = $this->calculateResponseMti($this->mti);
        }

        $response = new self($mti);
        $response->setHeader($this->header);
        $response->setDirection(Direction::OUTGOING);

        if ($copyAllFields) {
            $response->setFields($this->dataElements);
        } else {
            // Copy key routing & trace fields from request to response
            $fieldsToCopy = [
                Field::PRIMARY_ACCOUNT_NUMBER,
                Field::PROCESSING_CODE,
                Field::AMOUNT_TRANSACTION,
                Field::TRANSMISSION_DATE_TIME,
                Field::SYSTEM_TRACE_AUDIT_NUMBER,
                Field::TIME_LOCAL_TRANSACTION,
                Field::DATE_LOCAL_TRANSACTION,
                Field::RETRIEVAL_REFERENCE_NUMBER,
                Field::CARD_ACCEPTOR_TERMINAL_IDENTIFICATION,
                Field::CARD_ACCEPTOR_IDENTIFICATION_CODE,
                Field::CURRENCY_CODE_TRANSACTION,
            ];

            foreach ($fieldsToCopy as $fieldNumber) {
                if ($this->hasField($fieldNumber)) {
                    $response->setField($fieldNumber, $this->getField($fieldNumber));
                }
            }
        }

        $response->setResponseCode($responseCode);

        return $response;
    }

    /**
     * Calculate the response MTI from a request MTI.
     * Example: "0200" -> "0210", "0800" -> "0810", "0400" -> "0410", "0420" -> "0430"
     */
    protected function calculateResponseMti(string $requestMti): string
    {
        if (strlen($requestMti) !== 4) {
            return '0210';
        }

        $responseMti = $requestMti;
        $thirdDigit  = (int) $requestMti[2];

        // If 3rd digit (function) is even (0, 2, 4), add 1 to make it a response (1, 3, 5)
        if ($thirdDigit % 2 === 0) {
            $responseMti[2] = (string) ($thirdDigit + 1);
        }

        return $responseMti;
    }

    // =========================================================================
    // MASKING & PCI-DSS SANITIZATION
    // =========================================================================

    /**
     * Get an array representation of the message with sensitive PCI-DSS fields masked.
     *
     * Masked fields:
     * - Field 2 (PAN): Mask middle digits, keep $unmaskedPrefix and $unmaskedSuffix.
     * - Field 35 & 45 (Track Data): Mask account number portion.
     * - Field 52 (PIN Data): Fully replaced with '***MASKED***'.
     *
     * @param string $maskChar       Character to use for masking (default '*').
     * @param int    $unmaskedPrefix Number of leading unmasked digits in PAN (default 6).
     * @param int    $unmaskedSuffix Number of trailing unmasked digits in PAN (default 4).
     */
    public function toMaskedArray(string $maskChar = '*', int $unmaskedPrefix = 6, int $unmaskedSuffix = 4): array
    {
        $data = $this->toArray();

        if (isset($data['fields'][Field::PRIMARY_ACCOUNT_NUMBER])) {
            $pan = (string) $data['fields'][Field::PRIMARY_ACCOUNT_NUMBER];
            $data['fields'][Field::PRIMARY_ACCOUNT_NUMBER] = $this->maskPan($pan, $maskChar, $unmaskedPrefix, $unmaskedSuffix);
        }

        if (isset($data['fields'][Field::TRACK_2_DATA])) {
            $track = (string) $data['fields'][Field::TRACK_2_DATA];
            $data['fields'][Field::TRACK_2_DATA] = $this->maskTrack2($track, $maskChar, $unmaskedPrefix, $unmaskedSuffix);
        }

        if (isset($data['fields'][Field::PERSONAL_IDENTIFICATION_NUMBER_DATA])) {
            $data['fields'][Field::PERSONAL_IDENTIFICATION_NUMBER_DATA] = '***MASKED***';
        }

        return $data;
    }

    protected function maskPan(string $pan, string $maskChar, int $prefixLength, int $suffixLength): string
    {
        $len = strlen($pan);
        if ($len <= ($prefixLength + $suffixLength)) {
            return str_repeat($maskChar, $len);
        }

        $prefix = substr($pan, 0, $prefixLength);
        $suffix = substr($pan, -$suffixLength);
        $maskedLen = $len - $prefixLength - $suffixLength;

        return $prefix . str_repeat($maskChar, $maskedLen) . $suffix;
    }

    protected function maskTrack2(string $track, string $maskChar, int $prefixLength, int $suffixLength): string
    {
        $separatorPos = strpos($track, '=');
        if ($separatorPos === false) {
            $separatorPos = strpos($track, 'D');
        }

        if ($separatorPos === false) {
            return $this->maskPan($track, $maskChar, $prefixLength, $suffixLength);
        }

        $pan = substr($track, 0, $separatorPos);
        $rest = substr($track, $separatorPos);

        return $this->maskPan($pan, $maskChar, $prefixLength, $suffixLength) . $rest;
    }

    // =========================================================================
    // JSON SERIALIZATION & DESERIALIZATION
    // =========================================================================

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Reconstruct a Message object from a JSON string.
     *
     * @throws InvalidArgumentException If JSON is invalid.
     */
    public static function fromJson(string $json): static
    {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new InvalidArgumentException("Invalid JSON provided for Message reconstruction.");
        }

        $message = new static($data['mti'] ?? '');

        if (isset($data['header'])) {
            $message->setHeader($data['header']);
        }

        if (isset($data['bitmap'])) {
            $message->setBitmap($data['bitmap']);
        }

        if (isset($data['fields']) && is_array($data['fields'])) {
            foreach ($data['fields'] as $key => $val) {
                $message->setField((int) $key, $val);
            }
        }

        return $message;
    }

    // =========================================================================
    // FIELD ALIASES API
    // =========================================================================

    /**
     * Get field value by its friendly alias defined in the Spec.
     *
     * @throws \InvalidArgumentException If the alias is not registered in the Spec.
     */
    public function getByAlias(string $alias, \TerminalHero\Iso8583\Specs\BaseSpec $spec): mixed
    {
        $fieldNumber = $spec->getBitByAlias($alias);
        if ($fieldNumber === null) {
            throw new \InvalidArgumentException("Alias '{$alias}' is not defined in the provided Spec.");
        }
        return $this->getField($fieldNumber);
    }

    /**
     * Set field value by its friendly alias defined in the Spec.
     *
     * @throws \InvalidArgumentException If the alias is not registered in the Spec.
     */
    public function setByAlias(string $alias, mixed $value, \TerminalHero\Iso8583\Specs\BaseSpec $spec): static
    {
        $fieldNumber = $spec->getBitByAlias($alias);
        if ($fieldNumber === null) {
            throw new \InvalidArgumentException("Alias '{$alias}' is not defined in the provided Spec.");
        }
        return $this->setField($fieldNumber, $value);
    }

    /**
     * Check if a field defined by alias is present in the message.
     */
    public function hasAlias(string $alias, \TerminalHero\Iso8583\Specs\BaseSpec $spec): bool
    {
        $fieldNumber = $spec->getBitByAlias($alias);
        if ($fieldNumber === null) {
            return false;
        }
        return $this->hasField($fieldNumber);
    }

    /**
     * Remove a field by its alias.
     */
    public function removeByAlias(string $alias, \TerminalHero\Iso8583\Specs\BaseSpec $spec): static
    {
        $fieldNumber = $spec->getBitByAlias($alias);
        if ($fieldNumber !== null) {
            $this->removeField($fieldNumber);
        }
        return $this;
    }

    // =========================================================================
    // MESSAGE DIFF INSPECTION (Py8583 inspired)
    // =========================================================================

    /**
     * Compare this Message with another Message and return field-by-field differences.
     *
     * Returns an associative array:
     * - 'added':     Fields present in $otherMessage but absent in $this
     * - 'removed':   Fields present in $this but absent in $otherMessage
     * - 'modified':  Fields present in both but with different values ['from' => ..., 'to' => ...]
     * - 'unchanged': Fields present in both with identical values
     *
     * @param Message $otherMessage The message to compare against.
     * @return array{
     *     added: array<int, mixed>,
     *     removed: array<int, mixed>,
     *     modified: array<int, array{from: mixed, to: mixed}>,
     *     unchanged: array<int, mixed>
     * }
     */
    public function diff(Message $otherMessage): array
    {
        $thisFields  = $this->getFields();
        $otherFields = $otherMessage->getFields();

        $allFieldNumbers = array_unique(array_merge(array_keys($thisFields), array_keys($otherFields)));
        sort($allFieldNumbers);

        $added     = [];
        $removed   = [];
        $modified  = [];
        $unchanged = [];

        foreach ($allFieldNumbers as $fieldNumber) {
            $hasThis  = isset($thisFields[$fieldNumber]);
            $hasOther = isset($otherFields[$fieldNumber]);

            if (!$hasThis && $hasOther) {
                $added[$fieldNumber] = $otherFields[$fieldNumber];
            } elseif ($hasThis && !$hasOther) {
                $removed[$fieldNumber] = $thisFields[$fieldNumber];
            } else {
                $valThis  = $thisFields[$fieldNumber];
                $valOther = $otherFields[$fieldNumber];

                if ($valThis === $valOther) {
                    $unchanged[$fieldNumber] = $valThis;
                } else {
                    $modified[$fieldNumber] = [
                        'from' => $valThis,
                        'to'   => $valOther,
                    ];
                }
            }
        }

        return [
            'added'     => $added,
            'removed'   => $removed,
            'modified'  => $modified,
            'unchanged' => $unchanged,
        ];
    }

    // =========================================================================
    // UTILITY / INSPECTION METHODS
    // =========================================================================

    public function getActiveBits(): array
    {
        $keys = array_keys($this->dataElements);
        sort($keys);
        return $keys;
    }

    public function toArray(): array
    {
        return [
            'header'      => $this->getHeader(),
            'mti'         => $this->getMti(),
            'bitmap'      => $this->getBitmap(),
            'direction'   => $this->getDirection()->value,
            'active_bits' => $this->getActiveBits(),
            'fields'      => $this->getFields(),
        ];
    }
}
