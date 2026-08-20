<?php

namespace TerminalHero\Iso8583\Enums;

/**
 * Defines the format used for TCP packet framing (length prefix).
 *
 * When transmitting ISO8583 messages over raw TCP/IP sockets, payment switches
 * almost universally prepend a 2-byte or 4-byte length prefix to the packet
 * so the receiver knows how many bytes to read from the socket stream.
 *
 * Supported formats:
 * - NONE: No packet length prefix (default).
 * - BINARY_2_BYTE: 2-byte unsigned short in big-endian network byte order (e.g., 0x00 0x5C = 92 bytes).
 * - ASCII_4_BYTE: 4 ASCII decimal digits (e.g., "0092" = 92 bytes).
 * - BCD_2_BYTE: 2-byte Binary Coded Decimal (e.g., 0x00 0x92 = 92 bytes).
 */
enum PacketLengthFormat: string
{
    case NONE = 'none';
    case BINARY_2_BYTE = 'binary_2_byte';
    case ASCII_4_BYTE = 'ascii_4_byte';
    case BCD_2_BYTE = 'bcd_2_byte';
}
