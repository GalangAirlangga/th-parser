<?php

namespace TerminalHero\Iso8583\Enums;

enum Format: string
{
    case NUMERIC = 'n';
    case ALPHA = 'a';
    case ALPHA_NUMERIC = 'an';
    case ALPHA_NUMERIC_SPECIAL = 'ans';
    case BINARY = 'b';
    case TRACK_DATA = 'z';
    case SPECIAL = 'x';
}
