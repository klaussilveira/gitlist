<?php

declare(strict_types=1);

namespace GitList\App\Twig;

use GitList\SCM\Blob;
use GitList\SCM\Repository;
use GitList\SCM\Submodule;
use GitList\SCM\Tree;
use PHPUnit\Framework\TestCase;

class RepositoryExtensionTest extends TestCase
{
    public function testIsDetectingTree(): void
    {
        $extension = new RepositoryExtension();
        $this->assertFalse($extension->isTree(null));
        $this->assertFalse($extension->isTree(new Blob(new Repository('foo'), '123')));
        $this->assertTrue($extension->isTree(new Tree(new Repository('foo'), '123')));
    }

    public function testIsSeparatingSubmodulesFromFiles(): void
    {
        $extension = new RepositoryExtension();
        $blob = new Blob(new Repository('foo'), '123');
        $tree = new Tree(new Repository('foo'), '456');
        $submodule = new Submodule(new Repository('foo'), '789', 'lib');
        $items = [$blob, $tree, $submodule];

        $this->assertSame([0 => $blob], $extension->onlyFiles($items));
        $this->assertSame([1 => $tree], $extension->onlyTrees($items));
        $this->assertSame([2 => $submodule], $extension->onlySubmodules($items));
    }

    public function testIsFormattingFileSizeWithInvalidInput(): void
    {
        $extension = new RepositoryExtension();
        $this->assertEquals('0 B', $extension->formatFileSize(0));
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
