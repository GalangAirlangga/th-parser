<?php

namespace TerminalHero\Iso8583\Specs;

use TerminalHero\Iso8583\Contracts\FieldTransformerInterface;
use TerminalHero\Iso8583\Enums\BitmapFormat;
use TerminalHero\Iso8583\Enums\Format;
use TerminalHero\Iso8583\Enums\LengthFormat;
use TerminalHero\Iso8583\Enums\LengthType;
use TerminalHero\Iso8583\Enums\Padding;

/**
 * Abstract base class for all ISO8583 Specification definitions.
 *
 * A "Specification" is a reusable, versioned definition of the ISO8583
 * message format for a particular payment switch or bank. It defines:
 *  - Which fields exist and their formats (type, length, encoding)
 *  - Optional sub-element transformers (TLV, Positional, Token)
 *  - Global message settings (bitmap format, header length, etc.)
 *
 * -----------------------------------------------------------------------
 * HOW TO CREATE A CUSTOM SPECIFICATION:
 * -----------------------------------------------------------------------
 * 1. Create a new class in your application (or in `src/Specs/`) that
 *    extends either `BaseSpec` directly or an existing spec like `Standard1987Spec`.
 *
 * 2. Override the `define()` method and call `$this->set(...)` for each
 *    field you need to configure or override.
 *
 * 3. Inject your spec into the `Iso8583` class:
 *    ```php
 *    $iso = new Iso8583(new MyBankSpec());
 *    ```
 *
 * -----------------------------------------------------------------------
 * EXAMPLE:
 * -----------------------------------------------------------------------
 * ```php
 * class MyBankSpec extends Standard1987Spec
 * {
 *     public function define(): void
 *     {
 *         parent::define(); // inherit standard fields
 *
 *         $this->setHeaderLength(10);
 *         $this->setBitmapFormat(BitmapFormat::RAW_BINARY);
 *         $this->setLengthIndicatorFormat(LengthFormat::BCD);
 *
 *         $this->set(Field::PROCESSING_CODE, Format::NUMERIC, 6, LengthType::FIXED)
 *              ->setPadding(Padding::LEFT_ZERO)
 *              ->setTransformer(new PositionalTransformer([
 *                  'trx_type' => 2,
 *                  'from_acc' => 2,
 *                  'to_acc'   => 2,
 *              ]));
 *     }
 * }
 * ```
 */
abstract class BaseSpec
{
    /**
     * Internal storage for all field definitions.
     * Key = field/bit number (1-128), Value = array of field config.
     *
     * @var array<int, array>
     */
    protected array $fields = [];

    /**
     * Tracks the last field number configured by `set()`.
     * Used internally to allow fluent chaining: `->set(...)->setTransformer(...)`.
     */
    protected ?int $lastModifiedField = null;

    /**
     * Length of the optional ISO header in characters (bytes).
     * Set to 0 if no header is expected (default).
     * Example: 10 for a TPDU header ("ISO0150000" = 10 characters).
     */
    protected int $headerLength = 0;

    /**
     * The encoding format used for the bitmap section of the message.
     * Defaults to HEX (most common for ASCII/modern connections).
     *
     * @see BitmapFormat
     */
    protected BitmapFormat $bitmapFormat = BitmapFormat::HEXADECIMAL;

    /**
     * The encoding format used for variable-length field length indicators.
     * Defaults to ASCII (most common for modern connections).
     *
     * @see LengthFormat
     */
    protected LengthFormat $lengthIndicatorFormat = LengthFormat::ASCII;

    /**
     * The TCP packet length framing format (length prefix).
     * Defaults to NONE.
     *
     * @see \TerminalHero\Iso8583\Enums\PacketLengthFormat
     */
    protected \TerminalHero\Iso8583\Enums\PacketLengthFormat $packetLengthFormat = \TerminalHero\Iso8583\Enums\PacketLengthFormat::NONE;

    /**
     * Storage for custom field aliases (e.g. 'card_number' -> 2).
     *
     * @var array<string, int>
     */
    protected array $aliases = [];

    /**
     * Reverse lookup map (2 -> 'card_number').
     *
     * @var array<int, string>
     */
    protected array $reverseAliases = [];

    /**
     * Constructor calls define() to populate all field configurations.
     * Do not override the constructor; use define() instead.
     */
    public function __construct()
    {
        $this->define();
    }

    /**
     * Define all field specifications for this ISO8583 format.
     *
     * This is the primary method to implement in your custom spec class.
     * Use `$this->set(...)` calls to define each field.
     */
    abstract public function define(): void;

    // =========================================================================
    // FIELD DEFINITION API
    // These methods are used inside define() to configure each field.
    // =========================================================================

    /**
     * Define or override a field's core specification.
     *
     * This method supports fluent chaining, allowing you to add a transformer
     * or custom padding immediately after:
     * ```php
     * $this->set(Field::PROCESSING_CODE, Format::NUMERIC, 6, LengthType::FIXED)
     *      ->setPadding(Padding::LEFT_ZERO)
     *      ->setTransformer(new PositionalTransformer([...]));
     * ```
     *
     * @param int        $fieldNumber The ISO8583 bit number (1–128).
     * @param Format     $format      The data type/format of the field.
     * @param int        $length      The max (or fixed) length of the field value.
     * @param LengthType $lengthType  Whether the field is fixed, LLVAR, or LLLVAR.
     *
     * @return $this For fluent chaining.
     */
    public function set(int $fieldNumber, Format $format, int $length, LengthType $lengthType = LengthType::FIXED): static
    {
        // Determine the default padding strategy based on the format type.
        // This mirrors the ISO8583 standard: numeric fields are zero-padded left,
        // and alphanumeric fields are space-padded right.
        $defaultPadding = in_array($format, [Format::NUMERIC, Format::BINARY])
            ? Padding::LEFT_ZERO
            : Padding::RIGHT_SPACE;

        $this->fields[$fieldNumber] = [
            'type'        => $format->value,      // Stored as string for easy comparison
            'format'      => $format,              // Stored as enum for validator use
            'length'      => $length,
            'length_type' => $lengthType->value,   // Stored as string for easy comparison
            'transformer' => null,                  // Optional FieldTransformerInterface
            'padding'     => $defaultPadding,       // Default, may be overridden by setPadding()
        ];

        $this->lastModifiedField = $fieldNumber;

        return $this;
    }

    /**
     * Attach a Field Transformer to the last defined field (or a specific one).
     *
     * Transformers allow a field's raw string to be automatically converted
     * to/from a structured format (array) during parse/build operations.
     *
     * Available transformers:
     * - `TlvTransformer`: For EMV/Chip TLV data (e.g., Bit 55).
     * - `PositionalTransformer`: For fixed-position sub-fields (e.g., Bit 3).
     * - `TokenTransformer`: For token-based private data (e.g., Bit 62).
     *
     * @param FieldTransformerInterface $transformer The transformer instance to attach.
     * @param int|null                  $fieldNumber Explicit field number, or null for the last set field.
     *
     * @return $this For fluent chaining.
     */
    public function setTransformer(FieldTransformerInterface $transformer, ?int $fieldNumber = null): static
    {
        $target = $fieldNumber ?? $this->lastModifiedField;

        if ($target !== null && isset($this->fields[$target])) {
            $this->fields[$target]['transformer'] = $transformer;
        }

        return $this;
    }

    /**
     * Set the padding strategy for the last defined field (or a specific one).
     *
     * Only applies to FIXED-length fields. Variable-length fields (LLVAR/LLLVAR)
     * do not need padding as their length indicator handles the length.
     *
     * @param Padding  $padding     The padding strategy to apply.
     * @param int|null $fieldNumber Explicit field number, or null for the last set field.
     *
     * @return $this For fluent chaining.
     */
    public function setPadding(Padding $padding, ?int $fieldNumber = null): static
    {
        $target = $fieldNumber ?? $this->lastModifiedField;

        if ($target !== null && isset($this->fields[$target])) {
            $this->fields[$target]['padding'] = $padding;
        }

        return $this;
    }

    /**
     * Define a friendly string alias for the last defined field (or a specific one).
     *
     * Example:
     * ```php
     * $this->set(Field::PRIMARY_ACCOUNT_NUMBER, Format::NUMERIC, 19, LengthType::LLVAR)
     *      ->alias('card_number');
     * ```
     *
     * @param string   $aliasName   The friendly name (e.g. 'card_number', 'amount').
     * @param int|null $fieldNumber Explicit field number, or null for the last set field.
     *
     * @return $this For fluent chaining.
     */
    public function alias(string $aliasName, ?int $fieldNumber = null): static
    {
        $target = $fieldNumber ?? $this->lastModifiedField;

        if ($target !== null) {
            $this->aliases[$aliasName]       = $target;
            $this->reverseAliases[$target]  = $aliasName;
        }

        return $this;
    }

    public function getBitByAlias(string $aliasName): ?int
    {
        return $this->aliases[$aliasName] ?? null;
    }

    public function getAliasByBit(int $fieldNumber): ?string
    {
        return $this->reverseAliases[$fieldNumber] ?? null;
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    // =========================================================================
    // GLOBAL MESSAGE SETTINGS
    // These methods configure the overall structure of the ISO8583 message.
    // =========================================================================

    /**
     * Set the expected length of the ISO header prefix (in characters/bytes).
     *
     * Many real-world switches prefix messages with a fixed-length header
     * before the MTI. Examples:
     * - TPDU (Transport Protocol Data Unit): typically 10 hex characters.
     * - ISO Header: e.g., "ISO015000050" (12 characters).
     *
     * Set to 0 (default) if no header is used.
     *
     * @param int $length The number of characters the header occupies.
     *
     * @return $this For fluent chaining.
     */
    public function setHeaderLength(int $length): static
    {
        $this->headerLength = $length;
        return $this;
    }

    /**
     * Set the bitmap encoding format (Hexadecimal or Raw Binary).
     *
     * @param BitmapFormat $format
     *
     * @return $this For fluent chaining.
     *
     * @see BitmapFormat For a detailed explanation of each format.
     */
    public function setBitmapFormat(BitmapFormat $format): static
    {
        $this->bitmapFormat = $format;
        return $this;
    }

    /**
     * Set the encoding format for LLVAR/LLLVAR length indicators.
     *
     * @param LengthFormat $format
     *
     * @return $this For fluent chaining.
     *
     * @see LengthFormat For a detailed explanation of each format.
     */
    public function setLengthIndicatorFormat(LengthFormat $format): static
    {
        $this->lengthIndicatorFormat = $format;
        return $this;
    }

    public function setPacketLengthFormat(\TerminalHero\Iso8583\Enums\PacketLengthFormat $format): static
    {
        $this->packetLengthFormat = $format;
        return $this;
    }

    // =========================================================================
    // GETTERS
    // Used by the Parser and Builder to read the configuration.
    // =========================================================================

    /** @return array<int, array> The full field configuration map. */
    public function getConfig(): array
    {
        return ['fields' => $this->fields];
    }

    public function getHeaderLength(): int
    {
        return $this->headerLength;
    }

    public function getBitmapFormat(): BitmapFormat
    {
        return $this->bitmapFormat;
    }

    public function getLengthIndicatorFormat(): LengthFormat
    {
        return $this->lengthIndicatorFormat;
    }

    public function getPacketLengthFormat(): \TerminalHero\Iso8583\Enums\PacketLengthFormat
    {
        return $this->packetLengthFormat;
    }
}
