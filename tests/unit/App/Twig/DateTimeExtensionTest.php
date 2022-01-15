<?php

declare(strict_types=1);

namespace GitList\App\Twig;

use Carbon\Carbon;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class DateTimeExtensionTest extends TestCase
{
    public function setUp(): void
    {
        // Define fake current date for mocks
        Carbon::setTestNow('2021-01-01 12:00:00');
    }

    public function tearDown(): void
    {
        // Clear fake current date
        Carbon::setTestNow();
    }

    public function testIsGettingTimeAgo(): void
    {
        $date = new Carbon('2012-01-01 12:00:00');
        $extension = new DateTimeExtension();
        $this->assertEquals('9 years ago', $extension->ago($date));
    }

    public function testIsGettingLocalizedTimeAgo(): void
    {
        $date = new Carbon('2012-01-01 12:00:00');
        $extension = new DateTimeExtension('pt_BR');
        $this->assertEquals('há 9 anos', $extension->ago($date));
    }

    public function testIsConvertingDateTime(): void
    {
        $date = new DateTime('2012-01-01 12:00:00');
        $extension = new DateTimeExtension();
        $this->assertEquals('9 years ago', $extension->ago($date));
    }

    public function testIsConvertingDateTimeImmutable(): void
    {
        $date = new DateTimeImmutable('2012-01-01 12:00:00');
        $extension = new DateTimeExtension();
        $this->assertEquals('9 years ago', $extension->ago($date));
    }
}
