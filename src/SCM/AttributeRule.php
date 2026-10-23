<?php

declare(strict_types=1);

namespace GitList\SCM;

class AttributeRule
{
    public const MACRO_PREFIX = '[attr]';

    /**
     * @param array<string, string|bool|null> $attributes
     */
    public function __construct(protected string $source, protected int $line, protected string $pattern, protected array $attributes)
    {
    }

    /**
     * @return AttributeRule[]
     */
    public static function parse(string $source, string $contents): array
    {
        $rules = [];

        foreach (explode("\n", $contents) as $index => $line) {
            $fields = preg_split('/\s+/', trim($line), -1, PREG_SPLIT_NO_EMPTY);

            if (!$fields || str_starts_with($fields[0], '#')) {
                continue;
            }

            $pattern = array_shift($fields);
            $attributes = [];

            foreach ($fields as $field) {
                if (str_starts_with($field, '-')) {
                    $attributes[substr($field, 1)] = false;
                } elseif (str_starts_with($field, '!')) {
                    $attributes[substr($field, 1)] = null;
                } elseif (str_contains($field, '=')) {
                    [$name, $value] = explode('=', $field, 2);
                    $attributes[$name] = $value;
                } else {
                    $attributes[$field] = true;
                }
            }

            $rules[] = new self($source, $index + 1, $pattern, $attributes);
        }

        return $rules;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * @return array<string, string|bool|null>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function isMacro(): bool
    {
        return str_starts_with($this->pattern, self::MACRO_PREFIX);
    }

    public function isInTree(): bool
    {
        return '.gitattributes' === basename($this->source);
    }

    public function getDirectory(): string
    {
        $directory = dirname($this->source);

        if (!$this->isInTree() || '.' === $directory) {
            return '';
        }

        return $directory;
    }
}
