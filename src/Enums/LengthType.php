<?php

namespace TerminalHero\Iso8583\Enums;

enum LengthType: string
{
    case FIXED = 'fixed';
    case LLVAR = 'llvar';
    case LLLVAR = 'lllvar';
}
