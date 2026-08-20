<?php

namespace TerminalHero\Iso8583\Exceptions;

/**
 * Thrown when an ISO Header parsing or building error occurs.
 *
 * This is raised when:
 * - The raw message string is too short to contain the configured header.
 * - The header provided to the builder does not match the configured header length.
 */
class InvalidHeaderException extends Iso8583Exception {}
