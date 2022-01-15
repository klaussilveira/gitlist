<?php

declare(strict_types=1);

namespace GitList\SCM;

use GitList\SCM\Diff\File;
use PHPUnit\Framework\TestCase;

class CommitTest extends TestCase
{
    public function testIsCountingChanges(): void
    {
        $fileA = new File('foo.php');
        $fileA->increaseAdditions();
        $fileA->increaseAdditions();

        $fileB = new File('bar.php');
        $fileB->increaseAdditions();
        $fileB->increaseDeletions();

        $commit = new Commit(new Repository('/my/repo'), sha1((string) random_int(0, mt_getrandmax())));
        $commit->addDiff($fileA);
        $commit->addDiff($fileB);

        $this->assertEquals(3, $commit->getAdditions());
        $this->assertEquals(1, $commit->getDeletions());
    }
}
