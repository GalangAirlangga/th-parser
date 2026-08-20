<?php

namespace TerminalHero\Iso8583\Exceptions;

/**
 * Thrown when an MTI (Message Type Indicator) is invalid.
 *
 * The MTI must be exactly 4 characters long and (in strict mode) numeric.
 * Examples of valid MTIs: "0200", "0210", "0800", "0810".
 */
class InvalidMtiException extends Iso8583Exception {}
