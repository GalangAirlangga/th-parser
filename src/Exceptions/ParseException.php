<?php

namespace TerminalHero\Iso8583\Exceptions;

/**
 * Thrown when a raw ISO8583 string is malformed or too short to be parsed.
 *
 * This is raised when the Parser encounters an unexpected end of string,
 * a missing required section (MTI, Bitmap, or Data Element), or a corrupt
 * length indicator.
 */
class ParseException extends Iso8583Exception {}
