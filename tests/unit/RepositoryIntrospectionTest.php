<?php

declare(strict_types=1);

namespace GitList;

use GitList\SCM\Repository as SourceRepository;
use GitList\SCM\System\Git\CommandLine;
use PHPUnit\Framework\TestCase;

class RepositoryIntrospectionTest extends TestCase
{
    protected Repository $repository;

    public function setUp(): void
    {
        if (empty(shell_exec('which git 2> /dev/null'))) {
            $this->markTestSkipped('Git is not available.');
        }

        $this->repository = new Repository(new CommandLine(), new SourceRepository(FIXTURE_DIR.'/git-introspection-repo'), 'git-introspection-repo');
    }

    public function testIsResolvingFileAttributesAtRevision(): void
    {
        $this->assertSame([
            'diff' => false,
            'docs' => true,
            'eol' => 'lf',
            'export-ignore' => true,
            'linguist-documentation' => true,
            'text' => true,
        ], $this->sorted($this->repository->getFileAttributes('master/docs/guide.md')));
        $this->assertSame(['diff' => 'lfs', 'filter' => 'lfs', 'merge' => 'lfs', 'text' => false], $this->sorted($this->repository->getFileAttributes('master/data.bin')));
    }

    protected function sorted(array $attributes): array
    {
        ksort($attributes);

        return $attributes;
    }
}
