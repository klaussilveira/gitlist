<?php

declare(strict_types=1);

namespace GitList\SCM\System\Git;

use Carbon\Carbon;
use GitList\SCM\Blame;
use GitList\SCM\Blob;
use GitList\SCM\Commit\Criteria;
use GitList\SCM\Commit\Person;
use GitList\SCM\Repository;
use GitList\SCM\Symlink;
use GitList\SCM\Tree;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class CommandLineTest extends TestCase
{
    public const FIXTURE_REPO = FIXTURE_DIR.'/git-bare-repo';

    public function setUp(): void
    {
        if (empty(shell_exec('which git 2> /dev/null'))) {
            $this->markTestSkipped('Git is not available.');
        }
    }

    public function testIsValidatingRepository(): void
    {
        $commandLine = new CommandLine();
        $this->assertTrue($commandLine->isValidRepository(new Repository(self::FIXTURE_REPO)));
        $this->assertFalse($commandLine->isValidRepository(new Repository('/tmp')));
    }

    public function testIsGettingDescription(): void
    {
        $commandLine = new CommandLine();
        $this->assertEquals("foobar\n", $commandLine->getDescription(new Repository(self::FIXTURE_REPO)));
    }

    public function testIsGettingDefaultBranch(): void
    {
        $commandLine = new CommandLine();
        $this->assertEquals('master', $commandLine->getDefaultBranch(new Repository(self::FIXTURE_REPO)));
    }

    public function testIsGettingBranches(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);

        $commandLine = new CommandLine();
        $branches = $commandLine->getBranches($repository);

        $this->assertCount(2, $branches);

        // Branch feature/1.2-dev
        $this->assertEquals('feature/1.2-dev', $branches[0]->getName());
        $this->assertEquals('859f67f18fc4522a4da5b784bce6cb9ada034a16', $branches[0]->getTarget()->getHash());
        $this->assertEquals('Added symlink.', $branches[0]->getTarget()->getSubject());

        // Branch master
        $this->assertEquals('master', $branches[1]->getName());
        $this->assertEquals('a003d30bc7a355f55bf28479e62134186bae1aed', $branches[1]->getTarget()->getHash());
        $this->assertEquals('Fixed mm.', $branches[1]->getTarget()->getSubject());
    }

    public function testIsGettingTags(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);

        $commandLine = new CommandLine();
        $tags = $commandLine->getTags($repository);

        $this->assertCount(1, $tags);
        $this->assertEquals('Version 1.2', $tags[0]->getSubject());
        $this->assertEquals('Klaus Silveira', $tags[0]->getAuthor()->getName());
        $this->assertEquals('contact@klaussilveira.com', $tags[0]->getAuthor()->getEmail());
        $this->assertEquals('2016-11-23 13:28:45', $tags[0]->getAuthoredAt()->format('Y-m-d H:i:s'));
        $this->assertEquals('1.2', $tags[0]->getName());
        $this->assertEquals('ec587c3ebbaf491605d24a0b1d42e6de7dc26372', $tags[0]->getTarget()->getHash());
        $this->assertEquals('ec587c3', $tags[0]->getTarget()->getShortHash());
    }

    public function testIsGettingTree(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);

        $commandLine = new CommandLine();
        $tree = $commandLine->getTree($repository);

        $this->assertEquals('HEAD', $tree->getHash());
        $this->assertCount(4, $tree->getChildren());

        $file1 = $tree->getChildren()[0];
        $this->assertInstanceOf(Tree::class, $file1);
        $this->assertEquals('mm', $file1->getName());
        $this->assertEquals('c323e7368bd5da960547f53b25596f7b43a2d3d9', $file1->getHash());
        $this->assertEquals('040000', $file1->getMode());
        $this->assertEquals('Fixed mm.', $file1->getFirstParent()->getSubject());

        $file2 = $tree->getChildren()[1];
        $this->assertInstanceOf(Symlink::class, $file2);
        $this->assertEquals('old.json', $file2->getName());
        $this->assertEquals('new.json', $file2->getTarget());
        $this->assertEquals('ff6accf4931975aa697ea40f7b6d22af23ec77a2', $file2->getHash());
        $this->assertEquals('120000', $file2->getMode());
        $this->assertEquals(8, $file2->getSize());

        $file3 = $tree->getChildren()[2];
        $this->assertInstanceOf(Tree::class, $file3);
        $this->assertEquals('src', $file3->getName());
        $this->assertEquals('595019ca2e1bfab6711424c8567af364ec3cc319', $file3->getHash());
        $this->assertEquals('040000', $file3->getMode());
        $this->assertEquals('Added function call.', $file3->getFirstParent()->getSubject());

        $file4 = $tree->getChildren()[3];
        $this->assertInstanceOf(Blob::class, $file4);
        $this->assertEquals('test.json', $file4->getName());
        $this->assertEquals('e69de29bb2d1d6434b8b29ae775ad8c2e48c5391', $file4->getHash());
        $this->assertEquals('100644', $file4->getMode());
        $this->assertEquals(0, $file4->getSize());
        $this->assertEquals('Initial commit.', $file4->getFirstParent()->getSubject());
    }

    public function testIsGettingRecursiveTree(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);

        $commandLine = new CommandLine();
        $tree = $commandLine->getRecursiveTree($repository);

        $this->assertEquals('HEAD', $tree->getHash());
        $this->assertCount(6, $tree->getChildren());

        $file1 = $tree->getChildren()[0];
        $this->assertInstanceOf(Blob::class, $file1);
        $this->assertEquals('mm/cma.c', $file1->getName());
        $this->assertEquals('c17751c0dcafb95eda9f41e9cdc9ce101edbebed', $file1->getHash());
        $this->assertEquals('100644', $file1->getMode());
        $this->assertEquals(8866, $file1->getSize());

        $file2 = $tree->getChildren()[1];
        $this->assertInstanceOf(Blob::class, $file2);
        $this->assertEquals('mm/cma.h', $file2->getName());
        $this->assertEquals('17c75a4246c8bbab8b56fe4d562cd85ea670a21f', $file2->getHash());
        $this->assertEquals('100644', $file2->getMode());
        $this->assertEquals(515, $file2->getSize());

        $file3 = $tree->getChildren()[2];
        $this->assertInstanceOf(Symlink::class, $file3);
        $this->assertEquals('old.json', $file3->getName());
        $this->assertEquals('new.json', $file3->getTarget());
        $this->assertEquals('ff6accf4931975aa697ea40f7b6d22af23ec77a2', $file3->getHash());
        $this->assertEquals('120000', $file3->getMode());
        $this->assertEquals(8, $file3->getSize());

        $file4 = $tree->getChildren()[3];
        $this->assertInstanceOf(Blob::class, $file4);
        $this->assertEquals('src/index.php', $file4->getName());
        $this->assertEquals('290863292f509e947f62efed0b9cb6eac9b9ecb9', $file4->getHash());
        $this->assertEquals('100644', $file4->getMode());
        $this->assertEquals(14, $file4->getSize());

        $file5 = $tree->getChildren()[4];
        $this->assertInstanceOf(Blob::class, $file5);
        $this->assertEquals('src/test.php', $file5->getName());
        $this->assertEquals('e69de29bb2d1d6434b8b29ae775ad8c2e48c5391', $file5->getHash());
        $this->assertEquals('100644', $file5->getMode());
        $this->assertEquals(0, $file5->getSize());

        $file6 = $tree->getChildren()[5];
        $this->assertInstanceOf(Blob::class, $file6);
        $this->assertEquals('test.json', $file6->getName());
        $this->assertEquals('e69de29bb2d1d6434b8b29ae775ad8c2e48c5391', $file6->getHash());
        $this->assertEquals('100644', $file6->getMode());
        $this->assertEquals(0, $file6->getSize());
    }

    public function testIsGettingPathTree(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);

        $commandLine = new CommandLine();
        $tree = $commandLine->getPathTree($repository, 'mm');

        $this->assertEquals('HEAD', $tree->getHash());
        $this->assertEquals('mm', $tree->getName());
        $this->assertEquals('mm', $tree->getFileName());
        $children = $tree->getChildren();
        $this->assertCount(2, $children);

        $file1 = $children[0];
        $this->assertInstanceOf(Blob::class, $file1);
        $this->assertEquals('mm/cma.c', $file1->getName());
        $this->assertEquals('cma.c', $file1->getFileName());
        $this->assertEquals('c17751c0dcafb95eda9f41e9cdc9ce101edbebed', $file1->getHash());
        $this->assertEquals('100644', $file1->getMode());
        $this->assertEquals(8866, $file1->getSize());
        $this->assertEquals('Fixed mm.', $file1->getFirstParent()->getSubject());

        $file2 = $children[1];
        $this->assertInstanceOf(Blob::class, $file2);
        $this->assertEquals('mm/cma.h', $file2->getName());
        $this->assertEquals('cma.h', $file2->getFileName());
        $this->assertEquals('17c75a4246c8bbab8b56fe4d562cd85ea670a21f', $file2->getHash());
        $this->assertEquals('100644', $file2->getMode());
        $this->assertEquals(515, $file2->getSize());
        $this->assertEquals('Added mm.', $file2->getFirstParent()->getSubject());
    }

    public function testIsGettingPathTreeAndTrimmingSlash(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);

        $commandLine = new CommandLine();
        $tree = $commandLine->getPathTree($repository, 'mm/');

        $this->assertEquals('HEAD', $tree->getHash());
        $children = $tree->getChildren();
        $this->assertCount(2, $children);

        $file1 = $children[0];
        $this->assertInstanceOf(Blob::class, $file1);
        $this->assertEquals('mm/cma.c', $file1->getName());
        $this->assertEquals('cma.c', $file1->getFileName());
        $this->assertEquals('c17751c0dcafb95eda9f41e9cdc9ce101edbebed', $file1->getHash());
        $this->assertEquals('100644', $file1->getMode());
        $this->assertEquals(8866, $file1->getSize());
        $this->assertEquals('Fixed mm.', $file1->getFirstParent()->getSubject());

        $file2 = $children[1];
        $this->assertInstanceOf(Blob::class, $file2);
        $this->assertEquals('mm/cma.h', $file2->getName());
        $this->assertEquals('cma.h', $file2->getFileName());
        $this->assertEquals('17c75a4246c8bbab8b56fe4d562cd85ea670a21f', $file2->getHash());
        $this->assertEquals('100644', $file2->getMode());
        $this->assertEquals(515, $file2->getSize());
        $this->assertEquals('Added mm.', $file2->getFirstParent()->getSubject());
    }

    public function testIsGettingCommits(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);

        $commandLine = new CommandLine();
        $commits = $commandLine->getCommits($repository);

        $commit1 = $commits['a003d30bc7a355f55bf28479e62134186bae1aed'];
        $this->assertEquals('a003d30bc7a355f55bf28479e62134186bae1aed', $commit1->getHash());
        $this->assertEquals('Fixed mm.', $commit1->getSubject());
        $this->assertInstanceOf(Person::class, $commit1->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit1->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit1->getAuthor()->getName());
        $this->assertEquals('2016-11-24 10:30:04', $commit1->getCommitedAt()->format('Y-m-d H:i:s'));

        $commit2 = $commits['5570c142146e430b7356a84175f281ab2a364d48'];
        $this->assertEquals('5570c142146e430b7356a84175f281ab2a364d48', $commit2->getHash());
        $this->assertEquals('Added mm.', $commit2->getSubject());
        $this->assertInstanceOf(Person::class, $commit2->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit2->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit2->getAuthor()->getName());
        $this->assertEquals('2016-11-24 10:28:56', $commit2->getCommitedAt()->format('Y-m-d H:i:s'));

        $commit3 = $commits['85e656875f18b1985dd71dccaffe3eeffd6abf6f'];
        $this->assertEquals('85e656875f18b1985dd71dccaffe3eeffd6abf6f', $commit3->getHash());
        $this->assertEquals('Added symlink.', $commit3->getSubject());
        $this->assertInstanceOf(Person::class, $commit3->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit3->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit3->getAuthor()->getName());
        $this->assertEquals('2016-11-23 15:55:30', $commit3->getCommitedAt()->format('Y-m-d H:i:s'));
    }

    public function testIsGettingCommitsWithPagination(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);

        $commandLine = new CommandLine();
        $commits = $commandLine->getCommits($repository, 'HEAD', 1, 3);
        $this->assertCount(3, $commits);

        $commits = $commandLine->getCommits($repository, 'HEAD', 2, 3);
        $this->assertCount(2, $commits);
    }

    public function testIsGettingCommitsFromPath(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);

        $commandLine = new CommandLine();
        $commits = $commandLine->getCommitsFromPath($repository, 'mm/cma.c');
        $this->assertCount(2, $commits);

        $commit1 = $commits['a003d30bc7a355f55bf28479e62134186bae1aed'];
        $this->assertEquals('a003d30bc7a355f55bf28479e62134186bae1aed', $commit1->getHash());
        $this->assertEquals('Fixed mm.', $commit1->getSubject());
        $this->assertInstanceOf(Person::class, $commit1->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit1->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit1->getAuthor()->getName());
        $this->assertEquals('2016-11-24 10:30:04', $commit1->getCommitedAt()->format('Y-m-d H:i:s'));

        $commit2 = $commits['5570c142146e430b7356a84175f281ab2a364d48'];
        $this->assertEquals('5570c142146e430b7356a84175f281ab2a364d48', $commit2->getHash());
        $this->assertEquals('Added mm.', $commit2->getSubject());
        $this->assertInstanceOf(Person::class, $commit2->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit2->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit2->getAuthor()->getName());
        $this->assertEquals('2016-11-24 10:28:56', $commit2->getCommitedAt()->format('Y-m-d H:i:s'));
    }

    public function testIsGettingCommit(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);

        $commandLine = new CommandLine();
        $commit = $commandLine->getCommit($repository, 'a003d30bc7a355f55bf28479e62134186bae1aed');

        $this->assertEquals('a003d30bc7a355f55bf28479e62134186bae1aed', $commit->getHash());
        $this->assertEquals('Fixed mm.', $commit->getSubject());
        $this->assertInstanceOf(Person::class, $commit->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit->getAuthor()->getName());
        $this->assertEquals('2016-11-24 10:30:04', $commit->getCommitedAt()->format('Y-m-d H:i:s'));
    }

    public function testIsGettingSpecificCommits(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);

        $commandLine = new CommandLine();
        $commits = $commandLine->getSpecificCommits($repository, [
            'a003d30bc7a355f55bf28479e62134186bae1aed',
            '5570c142146e430b7356a84175f281ab2a364d48',
        ]);

        $commit1 = $commits['a003d30bc7a355f55bf28479e62134186bae1aed'];
        $this->assertEquals('a003d30bc7a355f55bf28479e62134186bae1aed', $commit1->getHash());
        $this->assertEquals('Fixed mm.', $commit1->getSubject());
        $this->assertInstanceOf(Person::class, $commit1->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit1->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit1->getAuthor()->getName());
        $this->assertEquals('2016-11-24 10:30:04', $commit1->getCommitedAt()->format('Y-m-d H:i:s'));

        $commit2 = $commits['5570c142146e430b7356a84175f281ab2a364d48'];
        $this->assertEquals('5570c142146e430b7356a84175f281ab2a364d48', $commit2->getHash());
        $this->assertEquals('Added mm.', $commit2->getSubject());
        $this->assertInstanceOf(Person::class, $commit2->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit2->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit2->getAuthor()->getName());
        $this->assertEquals('2016-11-24 10:28:56', $commit2->getCommitedAt()->format('Y-m-d H:i:s'));
    }

    public function testIsGettingBlame(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);

        $commandLine = new CommandLine();
        $blame = $commandLine->getBlame($repository, 'master', 'mm/cma.c');

        $this->assertInstanceOf(Blame::class, $blame);
        $this->assertEquals('5570c142146e430b7356a84175f281ab2a364d48', $blame->getAnnotatedLines()[90]->getCommit()->getHash());
        $this->assertEquals("91) \tstruct zone *zone;", $blame->getAnnotatedLines()[90]->getContents());
    }

    public function testIsGettingBlob(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);

        $commandLine = new CommandLine();
        $blob = $commandLine->getBlob($repository, 'master', 'mm/cma.h');

        $this->assertInstanceOf(Blob::class, $blob);
        $this->assertMatchesRegularExpression('/#ifdef CONFIG_CMA_DEBUGFS/', $blob->getContents());
    }

    public function testIsSearchingCommitsFromDate(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);
        $criteria = new Criteria();
        $criteria->setFrom(new Carbon('2016-11-24'));

        $commandLine = new CommandLine();
        $commits = $commandLine->searchCommits($repository, $criteria);
        $this->assertCount(2, $commits);

        $commit1 = $commits['a003d30bc7a355f55bf28479e62134186bae1aed'];
        $this->assertEquals('a003d30bc7a355f55bf28479e62134186bae1aed', $commit1->getHash());
        $this->assertEquals('Fixed mm.', $commit1->getSubject());
        $this->assertInstanceOf(Person::class, $commit1->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit1->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit1->getAuthor()->getName());
        $this->assertEquals('2016-11-24 10:30:04', $commit1->getCommitedAt()->format('Y-m-d H:i:s'));

        $commit2 = $commits['5570c142146e430b7356a84175f281ab2a364d48'];
        $this->assertEquals('5570c142146e430b7356a84175f281ab2a364d48', $commit2->getHash());
        $this->assertEquals('Added mm.', $commit2->getSubject());
        $this->assertInstanceOf(Person::class, $commit2->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit2->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit2->getAuthor()->getName());
        $this->assertEquals('2016-11-24 10:28:56', $commit2->getCommitedAt()->format('Y-m-d H:i:s'));
    }

    public function testIsSearchingCommitsToDate(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);
        $criteria = new Criteria();
        $criteria->setTo(new Carbon('2016-11-24'));

        $commandLine = new CommandLine();
        $commits = $commandLine->searchCommits($repository, $criteria);
        $this->assertCount(3, $commits);

        $commit1 = $commits['85e656875f18b1985dd71dccaffe3eeffd6abf6f'];
        $this->assertEquals('85e656875f18b1985dd71dccaffe3eeffd6abf6f', $commit1->getHash());
        $this->assertEquals('Added symlink.', $commit1->getSubject());
        $this->assertInstanceOf(Person::class, $commit1->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit1->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit1->getAuthor()->getName());
        $this->assertEquals('2016-11-23 15:55:30', $commit1->getCommitedAt()->format('Y-m-d H:i:s'));

        $commit2 = $commits['0e3ddf0c6d39e7a300bef61b99a914a86ce5e267'];
        $this->assertEquals('0e3ddf0c6d39e7a300bef61b99a914a86ce5e267', $commit2->getHash());
        $this->assertEquals('Added function call.', $commit2->getSubject());
        $this->assertInstanceOf(Person::class, $commit2->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit2->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit2->getAuthor()->getName());
        $this->assertEquals('2016-11-23 13:18:18', $commit2->getCommitedAt()->format('Y-m-d H:i:s'));

        $commit3 = $commits['b064e711b341b3d160288cd121caf56811ca8991'];
        $this->assertEquals('b064e711b341b3d160288cd121caf56811ca8991', $commit3->getHash());
        $this->assertEquals('Initial commit.', $commit3->getSubject());
        $this->assertInstanceOf(Person::class, $commit3->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit3->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit3->getAuthor()->getName());
        $this->assertEquals('2016-11-23 13:17:29', $commit3->getCommitedAt()->format('Y-m-d H:i:s'));
    }

    public function testIsSearchingCommitsByAuthor(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);
        $criteria = new Criteria();
        $criteria->setAuthor('Klaus');

        $commandLine = new CommandLine();
        $commits = $commandLine->searchCommits($repository, $criteria);
        $this->assertCount(5, $commits);
    }

    public function testIsSearchingCommitsByMessage(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);
        $criteria = new Criteria();
        $criteria->setMessage('Initial commit.');

        $commandLine = new CommandLine();
        $commits = $commandLine->searchCommits($repository, $criteria);
        $this->assertCount(1, $commits);

        $commit1 = $commits['b064e711b341b3d160288cd121caf56811ca8991'];
        $this->assertEquals('b064e711b341b3d160288cd121caf56811ca8991', $commit1->getHash());
        $this->assertEquals('Initial commit.', $commit1->getSubject());
        $this->assertInstanceOf(Person::class, $commit1->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit1->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit1->getAuthor()->getName());
        $this->assertEquals('2016-11-23 13:17:29', $commit1->getCommitedAt()->format('Y-m-d H:i:s'));
    }

    public function testIsArchivingZip(): void
    {
        $commandLine = new CommandLine();
        $archive = $commandLine->archive(new Repository(self::FIXTURE_REPO), 'zip', 'master');
        $this->assertFileExists($archive);

        $zip = new ZipArchive();
        $zip->open($archive);
        $this->assertEquals(8, $zip->numFiles);
    }

    public function testIsArchivingZipWithPath(): void
    {
        $commandLine = new CommandLine();
        $archive = $commandLine->archive(new Repository(self::FIXTURE_REPO), 'zip', 'master', 'mm');
        $this->assertFileExists($archive);

        $zip = new ZipArchive();
        $zip->open($archive);
        $this->assertEquals(3, $zip->numFiles);
    }

    public function testIsArchivingTarball(): void
    {
        $commandLine = new CommandLine();
        $archive = $commandLine->archive(new Repository(self::FIXTURE_REPO), 'tar', 'master');
        $this->assertFileExists($archive);

        $archive = $commandLine->archive(new Repository(self::FIXTURE_REPO), 'tar.gz', 'master');
        $this->assertFileExists($archive);
    }
}
