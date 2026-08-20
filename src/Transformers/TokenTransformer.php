<?php

namespace TerminalHero\Iso8583\Transformers;

use TerminalHero\Iso8583\Contracts\FieldTransformerInterface;
use Exception;

class TokenTransformer implements FieldTransformerInterface
{
    protected string $prefix;
    protected int $tokenNameLength;
    protected int $tokenLengthIndicatorSize;

    /**
     * @param string $prefix E.g., "!"
     * @param int $tokenNameLength E.g., 2 for "B2"
     * @param int $tokenLengthIndicatorSize E.g., 4 for "0012"
     */
    public function __construct(string $prefix = '!', int $tokenNameLength = 2, int $tokenLengthIndicatorSize = 4)
    {
        $this->prefix = $prefix;
        $this->tokenNameLength = $tokenNameLength;
        $this->tokenLengthIndicatorSize = $tokenLengthIndicatorSize;
    }

    public function parse(string $value): array
    {
        $result = [];
        $offset = 0;
        $len = strlen($value);

        while ($offset < $len) {
            // Find prefix
            if (substr($value, $offset, strlen($this->prefix)) !== $this->prefix) {
                // If it doesn't match prefix, skip or break. Usually token data is contiguous.
                // Let's break to be safe or try to find next prefix.
                break;
            }
            $offset += strlen($this->prefix);
            
            // Skip spaces if prefix is typically followed by space (e.g., "! B2" vs "!B2")
            while ($offset < $len && $value[$offset] === ' ') {
                $offset++;
            }

            // Read Token Name
            if ($offset + $this->tokenNameLength > $len) break;
            $tokenName = substr($value, $offset, $this->tokenNameLength);
            $offset += $this->tokenNameLength;

            // Skip spaces
            while ($offset < $len && $value[$offset] === ' ') {
                $offset++;
            }

            // Read Length
            if ($offset + $this->tokenLengthIndicatorSize > $len) break;
            $lengthStr = substr($value, $offset, $this->tokenLengthIndicatorSize);
            $dataLength = (int)$lengthStr;
            $offset += $this->tokenLengthIndicatorSize;

            // Skip spaces
            while ($offset < $len && $value[$offset] === ' ') {
                $offset++;
            }

            // Read Data
            if ($offset + $dataLength > $len) break;
            $data = substr($value, $offset, $dataLength);
            $result[$tokenName] = $data;
            $offset += $dataLength;
        }

        return $result;
    }

    public function build(mixed $value): string
    {
        if (!is_array($value)) {
            throw new Exception("TokenTransformer expects an array to build.");
        }

        $result = '';
        foreach ($value as $tokenName => $data) {
            $result .= $this->prefix . ' ';
            $result .= str_pad($tokenName, $this->tokenNameLength, ' ', STR_PAD_RIGHT) . ' ';
            $result .= str_pad((string)strlen($data), $this->tokenLengthIndicatorSize, '0', STR_PAD_LEFT) . ' ';
            $result .= $data;
        }

        return $result;
    }
}
