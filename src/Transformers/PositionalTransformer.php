<?php

namespace TerminalHero\Iso8583\Transformers;

use TerminalHero\Iso8583\Contracts\FieldTransformerInterface;
use Exception;

class PositionalTransformer implements FieldTransformerInterface
{
    /**
     * @var array<string, int> Mapping of key to fixed length
     */
    protected array $config;

    /**
     * @param array<string, int> $config e.g., ['trx_type' => 2, 'from_acc' => 2, 'to_acc' => 2]
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function parse(string $value): array
    {
        $result = [];
        $offset = 0;

        foreach ($this->config as $key => $length) {
            if (strlen($value) < $offset + $length) {
                // If the string is shorter than expected, we fill with empty or whatever is left
                $result[$key] = substr($value, $offset);
                break;
            }

            $result[$key] = substr($value, $offset, $length);
            $offset += $length;
        }

        return $result;
    }

    public function build(mixed $value): string
    {
        if (!is_array($value)) {
            throw new Exception("PositionalTransformer expects an array to build.");
        }

        $result = '';
        foreach ($this->config as $key => $length) {
            $val = $value[$key] ?? '';
            // Left pad with zero or right pad with space? We'll assume string padding for generic positional.
            // But usually the user supplies the exact length. Let's str_pad right with space as fallback.
            if (strlen($val) > $length) {
                $val = substr($val, 0, $length);
            } elseif (strlen($val) < $length) {
                $val = str_pad($val, $length, '0', STR_PAD_LEFT);
            }
            $result .= $val;
        }

        return $result;
    }
}
