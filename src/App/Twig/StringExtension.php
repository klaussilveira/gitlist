<?php

declare(strict_types=1);

namespace GitList\App\Twig;

use Symfony\Component\String\UnicodeString;
use Twig\Attribute\AsTwigFilter;

class StringExtension
{
    #[AsTwigFilter('truncate')]
    public function truncate(?string $string, int $maxLength = 30, string $terminator = '', bool $cut = true): string
    {
        if (!$string) {
            return '';
        }

        return (new UnicodeString($string))
            ->truncate($maxLength, $terminator, $cut)
            ->toString();
    }
}
