<?php

namespace TerminalHero\Iso8583\Exceptions;

use RuntimeException;

/**
 * Base exception for all ISO8583 library errors.
 *
 * All specific exceptions in this library extend this class so that
 * callers can catch the base exception to handle any ISO8583 error,
 * or catch a specific subclass for fine-grained error handling.
 *
 * Usage example:
 * ```php
 * try {
 *     $iso->parse($rawString);
 * } catch (Iso8583Exception $e) {
 *     // Catch any ISO8583 related error
 *     Log::error('ISO8583 Error: ' . $e->getMessage());
 * } catch (InvalidFieldFormatException $e) {
 *     // Catch only format validation errors
 * }
 * ```
 */
class Iso8583Exception extends RuntimeException {}
