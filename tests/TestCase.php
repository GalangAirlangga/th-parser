<?php

namespace TerminalHero\Iso8583\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use TerminalHero\Iso8583\Iso8583ServiceProvider;

/**
 * Base TestCase for all tests in this package.
 *
 * Extends Orchestra Testbench to provide a full Laravel application context.
 * This is needed so tests can use the ServiceProvider, Facade, and config.
 *
 * For unit tests that don't need Laravel (e.g., testing a single class),
 * it's fine to extend PHPUnit\Framework\TestCase directly instead.
 */
class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            Iso8583ServiceProvider::class,
        ];
    }
}
