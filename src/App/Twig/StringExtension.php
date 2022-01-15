<?php

declare(strict_types=1);

namespace GitList\App\Twig;

use Symfony\Component\String\UnicodeString;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class StringExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('truncate', [$this, 'truncate']),
        ];
    }

    public function truncate($string, int $maxLength = 30, string $terminator = '', bool $cut = true): string
    {
        if (!$string) {
            return '';
        }

        return (new UnicodeString($string))
            ->truncate($maxLength, $terminator, $cut)
            ->toString();
    }
}
