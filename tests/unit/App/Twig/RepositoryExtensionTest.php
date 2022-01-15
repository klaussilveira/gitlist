<?php

declare(strict_types=1);

namespace GitList\App\Twig;

use GitList\SCM\Repository;
use GitList\SCM\Tree;
use PHPUnit\Framework\TestCase;

class RepositoryExtensionTest extends TestCase
{
    public function testIsDetectingTree(): void
    {
        $extension = new RepositoryExtension();
        $this->assertFalse($extension->isTree(false));
        $this->assertTrue($extension->isTree(new Tree(new Repository('foo'), '123')));
    }

    public function testIsFormattingFileSizeWithInvalidInput(): void
    {
        $extension = new RepositoryExtension();
        $this->assertEquals('0 B', $extension->formatFileSize(false));
        $this->assertEquals('0 B', $extension->formatFileSize(null));
        $this->assertEquals('0 B', $extension->formatFileSize());
    }

    public function testIsFormattingFileSize(): void
    {
        $extension = new RepositoryExtension();
        $this->assertEquals('200 B', $extension->formatFileSize(200));
        $this->assertEquals('19.53 KB', $extension->formatFileSize(20000));
        $this->assertEquals('4.35 MB', $extension->formatFileSize(4_560_000));
    }

    public function testIsGettingCommitish(): void
    {
        $extension = new RepositoryExtension();
        $this->assertEquals('master/foo.php', $extension->getCommitish('master', 'foo.php'));
    }
}
