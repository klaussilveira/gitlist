<?php

declare(strict_types=1);

namespace GitList\App\Twig;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Twig\Attribute\AsTwigFilter;

class DateTimeExtension
{
    public function __construct(protected string $locale = 'en')
    {
    }

    #[AsTwigFilter('ago')]
    public function ago(DateTimeInterface $date): string
    {
        if (!$date instanceof CarbonInterface) {
            $date = new Carbon($date);
        }

        return $date->locale($this->locale)->diffForHumans();
    }
}
