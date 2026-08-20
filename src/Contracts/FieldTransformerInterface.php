<?php

namespace TerminalHero\Iso8583\Contracts;

interface FieldTransformerInterface
{
    /**
     * Parse the raw string value from the ISO8583 message into a structured format (e.g. array)
     *
     * @param string $value
     * @return mixed
     */
    public function parse(string $value): mixed;

    /**
     * Build the structured format (e.g. array) back into a raw string for the ISO8583 message
     *
     * @param mixed $value
     * @return string
     */
    public function build(mixed $value): string;
}
