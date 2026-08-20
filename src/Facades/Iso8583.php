<?php

namespace TerminalHero\Iso8583\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \TerminalHero\Iso8583\Message parse(string $raw)
 * @method static string build(\TerminalHero\Iso8583\Message $message)
 * @method static \TerminalHero\Iso8583\Message makeMessage(string $mti = '')
 * @method static \TerminalHero\Iso8583\Message fromJson(string $json)
 * @method static \TerminalHero\Iso8583\Specs\BaseSpec getSpec()
 *
 * @see \TerminalHero\Iso8583\Iso8583
 */
class Iso8583 extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'iso8583';
    }
}
