<?php

declare(strict_types=1);

namespace GitList\Repository;

use GitList\SCM\AttributeRule;

class Attributes
{
    public const MACROS = [
        'binary' => ['diff' => false, 'merge' => false, 'text' => false],
    ];

    /**
     * @param AttributeRule[] $rules
     */
    public function __construct(protected array $rules)
    {
    }

    /**
     * @return array<string, string|bool>
     */
    public function resolve(string $path): array
    {
        $macros = self::MACROS;

        foreach ($this->rules as $rule) {
            if ($rule->isMacro() && '' === $rule->getDirectory()) {
                $macros[substr($rule->getPattern(), strlen(AttributeRule::MACRO_PREFIX))] = $rule->getAttributes();
            }
        }

        $resolved = [];

        foreach ($this->rules as $rule) {
            if ($rule->isMacro() || !$this->matches($rule, $path)) {
                continue;
            }

            foreach ($rule->getAttributes() as $name => $state) {
                $resolved[$name] = $state;

                if (true === $state && isset($macros[$name])) {
                    $resolved = [...$resolved, ...$macros[$name]];
                }
            }
        }

        return array_filter($resolved, fn (string|bool|null $state): bool => null !== $state);
    }

    protected function matches(AttributeRule $rule, string $path): bool
    {
        $pattern = $rule->getPattern();
        $directory = $rule->getDirectory();

        if ('' !== $directory) {
            if (!str_starts_with($path, sprintf('%s/', $directory))) {
                return false;
            }

            $path = substr($path, strlen($directory) + 1);
        }

        if (!str_contains($pattern, '/')) {
            return 1 === preg_match($this->toExpression($pattern), basename($path));
        }

        return 1 === preg_match($this->toExpression(ltrim($pattern, '/')), $path);
    }

    protected function toExpression(string $pattern): string
    {
        $expression = '';
        $length = strlen($pattern);

        for ($index = 0; $index < $length; ++$index) {
            $character = $pattern[$index];

            if ('*' === $character && '*' === ($pattern[$index + 1] ?? '')) {
                $trailingSlash = '/' === ($pattern[$index + 2] ?? '');
                $expression .= $trailingSlash ? '(?:.*/)?' : '.*';
                $index += $trailingSlash ? 2 : 1;
            } elseif ('*' === $character) {
                $expression .= '[^/]*';
            } elseif ('?' === $character) {
                $expression .= '[^/]';
            } elseif ('[' === $character && false !== ($end = strpos($pattern, ']', $index + 2))) {
                $class = str_replace('#', '\#', substr($pattern, $index + 1, $end - $index - 1));
                $expression .= sprintf('[%s]', str_starts_with($class, '!') ? sprintf('^%s', substr($class, 1)) : $class);
                $index = $end;
            } elseif ('\\' === $character && $index + 1 < $length) {
                $expression .= preg_quote($pattern[++$index], '#');
            } else {
                $expression .= preg_quote($character, '#');
            }
        }

        return sprintf('#^%s$#', $expression);
    }
}
