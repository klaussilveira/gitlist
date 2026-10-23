<?php

declare(strict_types=1);

namespace GitList\SCM;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RemoteUrlTest extends TestCase
{
    public static function urlProvider(): array
    {
        return [
            ['https://deploy:hunter2@example.com/repo.git', 'https://example.com/repo.git'],
            ['https://ghp_token@github.com/org/repo.git', 'https://github.com/org/repo.git'],
            ['https://user:p@ss@example.com/repo.git', 'https://example.com/repo.git'],
            ['ssh://git@example.com:2222/repo.git', 'ssh://example.com:2222/repo.git'],
            ['[git]https://user:secret@example.com/sub.git', '[git]https://example.com/sub.git'],
            ['git@example.com:org/repo.git', 'git@example.com:org/repo.git'],
            ['https://example.com/path/with@sign.git', 'https://example.com/path/with@sign.git'],
            ['/srv/git/repo.git', '/srv/git/repo.git'],
        ];
    }

    #[DataProvider('urlProvider')]
    public function testIsRemovingCredentials(string $url, string $expected): void
    {
        $this->assertSame($expected, RemoteUrl::redact($url));
    }
}
