<?php

namespace TerminalHero\Iso8583\Transformers;

use TerminalHero\Iso8583\Contracts\FieldTransformerInterface;
use Exception;

class TlvTransformer implements FieldTransformerInterface
{
    public function parse(string $value): array
    {
        $result = [];
        $offset = 0;
        $len = strlen($value);

        while ($offset < $len) {
            // Parse Tag
            // Simplification: Assume 2-character hex (1 byte) or 4-character hex (2 bytes) tag.
            // Since ISO8583 strings are often represented in HEX strings in PHP (e.g., '9F0206...')
            // We'll read 2 chars at a time.
            if ($offset + 2 > $len) break;
            
            $b1 = hexdec(substr($value, $offset, 2));
            if (($b1 & 0x1F) === 0x1F) {
                // Tag is 2 bytes (4 hex chars)
                $tag = substr($value, $offset, 4);
                $offset += 4;
            } else {
                // Tag is 1 byte (2 hex chars)
                $tag = substr($value, $offset, 2);
                $offset += 2;
            }

            // Parse Length
            if ($offset + 2 > $len) break;
            
            $l1 = hexdec(substr($value, $offset, 2));
            if ($l1 > 127) {
                // Multi-byte length
                $numLengthBytes = $l1 & 0x7F;
                $offset += 2;
                
                $lengthHex = substr($value, $offset, $numLengthBytes * 2);
                $length = hexdec($lengthHex);
                $offset += ($numLengthBytes * 2);
            } else {
                $length = $l1;
                $offset += 2;
            }

            // The length is in bytes. Since the string is in HEX, it takes 2 * length chars.
            $valLength = $length * 2;
            
            if ($offset + $valLength > $len) {
                break; // Incomplete string
            }

            $tagValue = substr($value, $offset, $valLength);
            $result[strtoupper($tag)] = $tagValue;
            
            $offset += $valLength;
        }

        return $result;
    }

    public function build(mixed $value): string
    {
        if (!is_array($value)) {
            throw new Exception("TlvTransformer expects an array to build.");
        }

        $result = '';
        foreach ($value as $tag => $val) {
            $result .= strtoupper($tag);
            
            // Length is number of bytes (half the hex string length)
            $byteLength = strlen($val) / 2;
            
            if ($byteLength <= 127) {
                $result .= str_pad(dechex($byteLength), 2, '0', STR_PAD_LEFT);
            } else {
                // For simplicity, handle up to 255 bytes (1 byte length indicator + 1 byte length)
                $result .= '81' . str_pad(dechex($byteLength), 2, '0', STR_PAD_LEFT);
            }
            
            $result .= $val;
        }

        return strtoupper($result);
    }
}
