<?php

declare(strict_types=1);

namespace GitList;

use GitList\SCM\Blame;
use GitList\SCM\Blob;
use GitList\SCM\Commit;
use GitList\SCM\Commit\Criteria;
use GitList\SCM\Repository as SourceRepository;
use GitList\SCM\System;
use GitList\SCM\Tree;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class RepositoryTest extends TestCase
{
    use ProphecyTrait;

    public function testIsGettingBranches(): void
    {
        $sourceRepository = new SourceRepository('/foo/bar');

        $system = $this->prophesize(System::class);
        $system->getBranches($sourceRepository)->shouldBeCalled()->willReturn([]);

        $repository = new Repository($system->reveal(), $sourceRepository, 'bar');
        $repository->getBranches();
    }

    public function testIsGettingTags(): void
    {
        $sourceRepository = new SourceRepository('/foo/bar');

        $system = $this->prophesize(System::class);
        $system->getTags($sourceRepository)->shouldBeCalled()->willReturn([]);

        $repository = new Repository($system->reveal(), $sourceRepository, 'bar');
        $repository->getTags();
    }

    public function testIsGettingTreeWithHash(): void
    {
        $sourceRepository = new SourceRepository('/foo/bar');

        $system = $this->prophesize(System::class);
        $system->getBranches($sourceRepository)->shouldBeCalled()->willReturn([]);
        $system->getTags($sourceRepository)->shouldBeCalled()->willReturn([]);
        $system->getTree($sourceRepository, '123')->shouldBeCalled()->willReturn(new Tree($sourceRepository, '123'));

        $repository = new Repository($system->reveal(), $sourceRepository, 'bar');
        $repository->getTree('123');
    }

    public function testIsGettingTreeWithoutHash(): void
    {
        $sourceRepository = new SourceRepository('/foo/bar');

        $system = $this->prophesize(System::class);
        $system->getTree($sourceRepository)->shouldBeCalled()->willReturn(new Tree($sourceRepository, '123'));

        $repository = new Repository($system->reveal(), $sourceRepository, 'bar');
        $repository->getTree();
    }

    public function testIsGettingPathTreeWithHash(): void
    {
        $sourceRepository = new SourceRepository('/foo/bar');

        $system = $this->prophesize(System::class);
        $system->getBranches($sourceRepository)->shouldBeCalled()->willReturn([]);
        $system->getTags($sourceRepository)->shouldBeCalled()->willReturn([]);
        $system->getPathTree($sourceRepository, 'f/b', '123')->shouldBeCalled()->willReturn(new Tree($sourceRepository, '123'));

        $repository = new Repository($system->reveal(), $sourceRepository, 'bar');
        $repository->getTree('123/f/b');
    }

    public function testIsGettingCommitWithHash(): void
    {
        $sourceRepository = new SourceRepository('/foo/bar');

        $system = $this->prophesize(System::class);
        $system->getBranches($sourceRepository)->shouldBeCalled()->willReturn([]);
        $system->getTags($sourceRepository)->shouldBeCalled()->willReturn([]);
        $system->getCommit($sourceRepository, '123')->shouldBeCalled()->willReturn(new Commit($sourceRepository, '123'));

        $repository = new Repository($system->reveal(), $sourceRepository, 'bar');
        $repository->getCommit('123');
    }

    public function testIsGettingCommitWithoutHash(): void
    {
        $sourceRepository = new SourceRepository('/foo/bar');

        $system = $this->prophesize(System::class);
        $system->getCommit($sourceRepository)->shouldBeCalled()->willReturn(new Commit($sourceRepository, '123'));

        $repository = new Repository($system->reveal(), $sourceRepository, 'bar');
        $repository->getCommit();
    }

    public function testIsGettingCommitsWithHash(): void
    {
        $sourceRepository = new SourceRepository('/foo/bar');

        $system = $this->prophesize(System::class);
        $system->getBranches($sourceRepository)->shouldBeCalled()->willReturn([]);
        $system->getTags($sourceRepository)->shouldBeCalled()->willReturn([]);
        $system->getCommits($sourceRepository, '123', 1, 10)->shouldBeCalled()->willReturn([]);

        $repository = new Repository($system->reveal(), $sourceRepository, 'bar');
        $repository->getCommits('123', 1, 10);
    }

    public function testIsGettingCommitsWithoutHash(): void
    {
        $sourceRepository = new SourceRepository('/foo/bar');

        $system = $this->prophesize(System::class);
        $system->getCommits($sourceRepository, null, 1, 10)->shouldBeCalled()->willReturn([]);

        $repository = new Repository($system->reveal(), $sourceRepository, 'bar');
        $repository->getCommits(null, 1, 10);
    }

    public function testIsGettingSpecificCommits(): void
    {
        $sourceRepository = new SourceRepository('/foo/bar');

        $system = $this->prophesize(System::class);
        $system->getSpecificCommits($sourceRepository, ['a', 'b'])->shouldBeCalled()->willReturn([]);

        $repository = new Repository($system->reveal(), $sourceRepository, 'bar');
        $repository->getSpecificCommits(['a', 'b']);
    }

    public function testIsGettingBlame(): void
    {
        $sourceRepository = new SourceRepository('/foo/bar');

        $system = $this->prophesize(System::class);
        $system->getBranches($sourceRepository)->shouldBeCalled()->willReturn([]);
        $system->getTags($sourceRepository)->shouldBeCalled()->willReturn([]);
        $system->getBlame($sourceRepository, '123', 'foo.php')->shouldBeCalled()->willReturn(new Blame('123', 'foo.php'));

        $repository = new Repository($system->reveal(), $sourceRepository, 'bar');
        $repository->getBlame('123/foo.php');
    }

    public function testIsGettingBlob(): void
    {
        $sourceRepository = new SourceRepository('/foo/bar');

        $system = $this->prophesize(System::class);
        $system->getBranches($sourceRepository)->shouldBeCalled()->willReturn([]);
        $system->getTags($sourceRepository)->shouldBeCalled()->willReturn([]);
        $system->getBlob($sourceRepository, 'master', 'test.c')->shouldBeCalled()->willReturn(new Blob($sourceRepository, 'test.c'));

        $repository = new Repository($system->reveal(), $sourceRepository, 'bar');
        $repository->getBlob('master/test.c');
    }

    public function testIsSearchingCommits(): void
    {
        $sourceRepository = new SourceRepository('/foo/bar');
        $criteria = new Criteria();

        $system = $this->prophesize(System::class);
        $system->searchCommits($sourceRepository, $criteria)->shouldBeCalled()->willReturn([]);

        $repository = new Repository($system->reveal(), $sourceRepository, 'bar');
        $repository->searchCommits($criteria);
    }

    public function testIsSearchingCommitsWithCommitish(): void
    {
        $sourceRepository = new SourceRepository('/foo/bar');
        $criteria = new Criteria();

        $system = $this->prophesize(System::class);
        $system->getBranches($sourceRepository)->shouldBeCalled()->willReturn([]);
        $system->getTags($sourceRepository)->shouldBeCalled()->willReturn([]);
        $system->searchCommits($sourceRepository, $criteria, 'master')->shouldBeCalled()->willReturn([]);

        $repository = new Repository($system->reveal(), $sourceRepository, 'bar');
        $repository->searchCommits($criteria, 'master');
    }

    public function testIsArchiving(): void
    {
        $sourceRepository = new SourceRepository('/foo/bar');

        $system = $this->prophesize(System::class);
        $system->getBranches($sourceRepository)->shouldBeCalled()->willReturn([]);
        $system->getTags($sourceRepository)->shouldBeCalled()->willReturn([]);
        $system->archive($sourceRepository, 'zip', 'master', 'test.c')->shouldBeCalled()->willReturn('/tmp/test.zip');

        $repository = new Repository($system->reveal(), $sourceRepository, 'bar');
        $repository->archive('zip', 'master/test.c');
    }
}
