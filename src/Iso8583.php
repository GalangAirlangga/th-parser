<?php

namespace TerminalHero\Iso8583;

use TerminalHero\Iso8583\Specs\BaseSpec;
use TerminalHero\Iso8583\Specs\Standard1987Spec;

class Iso8583
{
    protected BaseSpec $spec;
    
    public function __construct(?BaseSpec $spec = null)
    {
        $this->spec = $spec ?? new Standard1987Spec();
    }

    public function getSpec(): BaseSpec
    {
        return $this->spec;
    }

    /**
     * Parse raw ISO8583 string to a Message object
     */
    public function parse(string $raw): Message
    {
        $parser = new Parser($this->spec);
        return $parser->parse($raw);
    }

    /**
     * Build an ISO8583 string from a Message object
     */
    public function build(Message $message): string
    {
        $builder = new Builder($this->spec);
        return $builder->build($message);
    }
    
    /**
     * Create a new empty message
     */
    public function makeMessage(string $mti = ''): Message
    {
        return new Message($mti);
    }

    /**
     * Reconstruct a Message object from JSON
     */
    public static function fromJson(string $json): Message
    {
        return Message::fromJson($json);
    }
}
