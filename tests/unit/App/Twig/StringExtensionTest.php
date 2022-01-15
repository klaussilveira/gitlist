<?php

declare(strict_types=1);

namespace GitList\App\Twig;

use PHPUnit\Framework\TestCase;

class StringExtensionTest extends TestCase
{
    /**
     * @dataProvider provideTruncateFixtures
     */
    public function testIsTruncatingText(string $expected, string $string, int $length, string $ellipsis, bool $cut = true): void
    {
        $extension = new StringExtension();
        $this->assertEquals($expected, $extension->truncate($string, $length, $ellipsis, $cut));
    }

    public static function provideTruncateFixtures()
    {
        return [
            ['', '', 3, ''],
            ['', 'foo', 0, '...'],
            ['foo', 'foo', 0, '...', false],
            ['fo', 'foobar', 2, ''],
            ['foobar', 'foobar', 10, ''],
            ['foobar', 'foobar', 10, '...', false],
            ['foo', 'foo', 3, '...'],
            ['fo', 'foobar', 2, '...'],
            ['...', 'foobar', 3, '...'],
            ['fo...', 'foobar', 5, '...'],
            ['foobar...', 'foobar foo', 6, '...', false],
            ['foobar...', 'foobar foo', 7, '...', false],
            ['foobar foo...', 'foobar foo a', 10, '...', false],
            ['foobar foo aar', 'foobar foo aar', 12, '...', false],
        ];
    }
}
