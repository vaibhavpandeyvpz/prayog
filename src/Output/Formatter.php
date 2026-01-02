<?php

declare(strict_types=1);

namespace Prayog\Output;

/**
 * Formats output values for display in the REPL.
 */
class Formatter
{
    public function __construct(
        private readonly bool $colorize = true,
    ) {}

    public function format(mixed $value): string
    {
        if ($value === null) {
            return $this->colorize ? "\033[90mnull\033[0m" : 'null';
        }

        return match (gettype($value)) {
            'boolean' => $this->formatBool($value),
            'integer' => $this->formatInt($value),
            'double' => $this->formatFloat($value),
            'string' => $this->formatString($value),
            'array' => $this->formatArray($value),
            'object' => $this->formatObject($value),
            'resource' => $this->formatResource($value),
            default => (string) $value,
        };
    }

    private function formatBool(bool $value): string
    {
        $text = $value ? 'true' : 'false';

        return $this->colorize ? "\033[33m$text\033[0m" : $text;
    }

    private function formatInt(int $value): string
    {
        return $this->colorize ? "\033[36m$value\033[0m" : (string) $value;
    }

    private function formatFloat(float $value): string
    {
        return $this->colorize ? "\033[36m$value\033[0m" : (string) $value;
    }

    private function formatString(string $value): string
    {
        $quoted = var_export($value, true);

        return $this->colorize ? "\033[32m$quoted\033[0m" : $quoted;
    }

    private function formatArray(array $value): string
    {
        $count = count($value);
        $preview = $this->getArrayPreview($value);
        $text = "array($count) $preview";

        return $this->colorize ? "\033[35m$text\033[0m" : $text;
    }

    private function formatObject(object $value): string
    {
        $class = $value::class;
        $text = "object($class)";

        return $this->colorize ? "\033[34m$text\033[0m" : $text;
    }

    private function formatResource($resource): string
    {
        $type = get_resource_type($resource);
        $text = "resource($type)";

        return $this->colorize ? "\033[31m$text\033[0m" : $text;
    }

    private function getArrayPreview(array $array, int $maxDepth = 2, int $maxItems = 3): string
    {
        if (empty($array)) {
            return '[]';
        }

        $items = [];
        $count = 0;
        foreach ($array as $key => $value) {
            if ($count >= $maxItems) {
                $items[] = '...';
                break;
            }

            $keyStr = is_string($key) ? var_export($key, true) : (string) $key;
            $valueStr = $this->getValuePreview($value, $maxDepth - 1);
            $items[] = "$keyStr => $valueStr";
            $count++;
        }

        return '['.implode(', ', $items).']';
    }

    private function getValuePreview(mixed $value, int $maxDepth): string
    {
        if ($maxDepth <= 0) {
            return '...';
        }

        return match (gettype($value)) {
            'array' => $this->getArrayPreview($value, $maxDepth, 2),
            'object' => $value::class,
            'string' => strlen($value) > 20 ? substr($value, 0, 20).'...' : var_export($value, true),
            default => (string) $value,
        };
    }
}
