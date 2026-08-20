<?php

namespace TerminalHero\Iso8583\Enums;

/**
 * Indicates whether an ISO8583 message was received (incoming) or generated for sending (outgoing).
 * Inspired by jPOS ISOMsg direction tracking.
 */
enum Direction: string
{
    case INCOMING = 'incoming';
    case OUTGOING = 'outgoing';
}
