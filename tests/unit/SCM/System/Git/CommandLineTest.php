<?php

declare(strict_types=1);

namespace GitList\SCM\System\Git;

use Carbon\Carbon;
use GitList\SCM\Blame;
use GitList\SCM\Blob;
use GitList\SCM\Commit\Criteria;
use GitList\SCM\Commit\Person;
use GitList\SCM\Exception\CommandException;
use GitList\SCM\Exception\InvalidCommitException;
use GitList\SCM\Repository;
use GitList\SCM\Submodule;
use GitList\SCM\Symlink;
use GitList\SCM\Tree;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use ZipArchive;

class CommandLineTest extends TestCase
{
    public const FIXTURE_REPO = FIXTURE_DIR.'/git-bare-repo';
    public const COMMIT_DATE = '2016-11-23 13:17:29 +0000';

    protected array $temporaryRepositories = [];

    public function setUp(): void
    {
        if (empty(shell_exec('which git 2> /dev/null'))) {
            $this->markTestSkipped('Git is not available.');
        }
    }

    public function tearDown(): void
    {
        (new Filesystem())->remove($this->temporaryRepositories);
        $this->temporaryRepositories = [];
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

    public function testIsGettingDefaultBranchOfRepositoryOwnedByAnotherUser(): void
    {
        $globalConfig = sys_get_temp_dir().'/gitlist-'.uniqid().'-gitconfig';

        $_ENV['GIT_TEST_ASSUME_DIFFERENT_OWNER'] = '1';
        $_ENV['GIT_CONFIG_GLOBAL'] = $globalConfig;

        try {
            $probe = new Process(['git', 'symbolic-ref', '--short', 'HEAD']);
            $probe->setWorkingDirectory(self::FIXTURE_REPO);
            $probe->run();

            if (0 === $probe->getExitCode()) {
                $this->markTestSkipped('Git does not enforce the ownership check.');
            }

            $commandLine = new CommandLine();

            $this->assertEquals('master', $commandLine->getDefaultBranch(new Repository(self::FIXTURE_REPO)));
            $this->assertFileDoesNotExist($globalConfig);
        } finally {
            unset($_ENV['GIT_TEST_ASSUME_DIFFERENT_OWNER'], $_ENV['GIT_CONFIG_GLOBAL']);

            if (file_exists($globalConfig)) {
                unlink($globalConfig);
            }
        }
    }

    public function testIsGettingDefaultBranchWhenHeadPointsToMissingBranch(): void
    {
        $path = $this->createTemporaryRepository();
        $this->commit($path, 'a.txt', 'Klaus Silveira', 'contact@klaussilveira.com', "Initial commit.\n");
        $this->git(['symbolic-ref', 'HEAD', 'refs/heads/nonexistent'], $path);

        $commandLine = new CommandLine();

        $this->assertEquals('master', $commandLine->getDefaultBranch(new Repository($path)));
    }

    public function testIsPreferringPlausibleDefaultBranchWhenHeadPointsToMissingBranch(): void
    {
        $path = $this->createTemporaryRepository();
        $this->commit($path, 'a.txt', 'Klaus Silveira', 'contact@klaussilveira.com', "Initial commit.\n");
        $this->git(['branch', '--move', 'aaa'], $path);
        $this->git(['branch', 'main'], $path);
        $this->git(['symbolic-ref', 'HEAD', 'refs/heads/nonexistent'], $path);

        $commandLine = new CommandLine();

        $this->assertEquals('main', $commandLine->getDefaultBranch(new Repository($path)));
    }

    public function testIsFallingBackToFirstBranchWhenHeadPointsToMissingBranch(): void
    {
        $path = $this->createTemporaryRepository();
        $this->commit($path, 'a.txt', 'Klaus Silveira', 'contact@klaussilveira.com', "Initial commit.\n");
        $this->git(['branch', '--move', 'production'], $path);
        $this->git(['symbolic-ref', 'HEAD', 'refs/heads/nonexistent'], $path);

        $commandLine = new CommandLine();

        $this->assertEquals('production', $commandLine->getDefaultBranch(new Repository($path)));
    }

    public function testIsGettingDefaultBranchOfRepositoryWithDetachedHead(): void
    {
        $path = $this->createTemporaryRepository();
        $this->commit($path, 'a.txt', 'Klaus Silveira', 'contact@klaussilveira.com', "Initial commit.\n");
        $this->git(['checkout', '--quiet', '--detach'], $path);

        $commandLine = new CommandLine();
        $repository = new Repository($path);

        $this->assertEquals('HEAD', $commandLine->getDefaultBranch($repository));
        $this->assertCount(1, $commandLine->getTree($repository)->getChildren());
    }

    public function testIsGettingTreeWhenHeadPointsToMissingBranch(): void
    {
        $path = $this->createTemporaryRepository();
        $this->commit($path, 'a.txt', 'Klaus Silveira', 'contact@klaussilveira.com', "Initial commit.\n");
        $this->git(['symbolic-ref', 'HEAD', 'refs/heads/nonexistent'], $path);

        $commandLine = new CommandLine();
        $tree = $commandLine->getTree(new Repository($path));

        $this->assertEquals('master', $tree->getHash());
        $this->assertCount(1, $tree->getChildren());
        $this->assertEquals('a.txt', $tree->getChildren()[0]->getName());
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

    public function testIsNotOverFetchingCommitsAfterFirstPage(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);

        $commandLine = new CommandLine();
        $page1 = $commandLine->getCommits($repository, 'HEAD', 1, 2);
        $page2 = $commandLine->getCommits($repository, 'HEAD', 2, 2);
        $page3 = $commandLine->getCommits($repository, 'HEAD', 3, 2);

        $this->assertCount(2, $page1);
        $this->assertCount(2, $page2);
        $this->assertCount(1, $page3);
        $this->assertEmpty(array_intersect_key($page1, $page2));
        $this->assertEmpty(array_intersect_key($page2, $page3));
    }

    public function testIsNotOverFetchingCommitsFromPathAfterFirstPage(): void
    {
        $path = $this->createTemporaryRepository();
        mkdir($path.'/pages');

        for ($index = 1; $index <= 5; ++$index) {
            $this->commit($path, 'pages/file'.$index.'.txt', 'Klaus Silveira', 'contact@klaussilveira.com', "Change {$index}.\n");
        }

        $commandLine = new CommandLine();
        $repository = new Repository($path);
        $page1 = $commandLine->getCommitsFromPath($repository, 'pages', 'HEAD', 1, 2);
        $page2 = $commandLine->getCommitsFromPath($repository, 'pages', 'HEAD', 2, 2);

        $this->assertCount(2, $page1);
        $this->assertCount(2, $page2);
        $this->assertEmpty(array_intersect_key($page1, $page2));
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
        $this->temporaryRepositories[] = dirname($archive);
        $this->assertFileExists($archive);

        $zip = new ZipArchive();
        $zip->open($archive);
        $this->assertEquals(8, $zip->numFiles);
    }

    public function testIsArchivingZipWithPath(): void
    {
        $commandLine = new CommandLine();
        $archive = $commandLine->archive(new Repository(self::FIXTURE_REPO), 'zip', 'master', 'mm');
        $this->temporaryRepositories[] = dirname($archive);
        $this->assertFileExists($archive);

        $zip = new ZipArchive();
        $zip->open($archive);
        $this->assertEquals(3, $zip->numFiles);
    }

    public function testIsArchivingTarball(): void
    {
        $commandLine = new CommandLine();
        $archive = $commandLine->archive(new Repository(self::FIXTURE_REPO), 'tar', 'master');
        $this->temporaryRepositories[] = dirname($archive);
        $this->assertFileExists($archive);

        $archive = $commandLine->archive(new Repository(self::FIXTURE_REPO), 'tar.gz', 'master');

        $this->temporaryRepositories[] = dirname($archive);
        $this->assertFileExists($archive);
    }

    public function testIsArchivingBranchWithSlashInsideTemporaryDirectory(): void
    {
        $commandLine = new CommandLine();
        $archive = $commandLine->archive(new Repository(self::FIXTURE_REPO), 'zip', 'feature/1.2-dev');
        $this->temporaryRepositories[] = dirname($archive);
        $this->assertFileExists($archive);
        $this->assertEquals('feature-1.2-dev.zip', basename($archive));
        $this->assertEquals(sys_get_temp_dir(), dirname($archive, 2));
    }

    public function testIsRemovingArchiveDirectoryWhenArchiveFails(): void
    {
        $before = glob(sys_get_temp_dir().'/gitlist-*', GLOB_ONLYDIR) ?: [];
        $commandLine = new CommandLine();

        try {
            $commandLine->archive(new Repository(self::FIXTURE_REPO), 'zip', 'does-not-exist');
            $this->fail('Expected CommandException');
        } catch (CommandException) {
        }

        $this->assertEquals($before, glob(sys_get_temp_dir().'/gitlist-*', GLOB_ONLYDIR) ?: []);
    }

    public function testIsNotInjectingOptionsThroughArchiveHash(): void
    {
        $target = sys_get_temp_dir().'/gitlist-archive-injection-'.uniqid();
        $commandLine = new CommandLine();

        try {
            $commandLine->archive(new Repository(self::FIXTURE_REPO), 'tar', '-o'.$target);
            $this->fail('Expected InvalidCommitException');
        } catch (InvalidCommitException) {
        }

        $this->assertFileDoesNotExist($target);
    }

    public function testIsRejectingHashesStartingWithHyphen(): void
    {
        $this->expectException(InvalidCommitException::class);

        $commandLine = new CommandLine();
        $commandLine->getCommits(new Repository(self::FIXTURE_REPO), '--output');
    }

    public function testIsParsingCommitsWithMarkupBreakingMetadata(): void
    {
        $path = $this->createTemporaryRepository();
        $this->commit($path, 'a.txt', 'A & B ]]', 'a&b@example.com', "init & <item> ]]> </item>\n\nbody & <![CDATA[ ]]> </body>\n");

        $repository = new Repository($path);
        $commandLine = new CommandLine();
        $commits = $commandLine->getCommits($repository);

        $this->assertCount(1, $commits);

        $commit = reset($commits);
        $this->assertEquals('init & <item> ]]> </item>', $commit->getSubject());
        $this->assertEquals('body & <![CDATA[ ]]> </body>', trim($commit->getBody()));
        $this->assertEquals('A & B ]]', $commit->getAuthor()->getName());
        $this->assertEquals('a&b@example.com', $commit->getAuthor()->getEmail());
        $this->assertEquals('2016-11-23 13:17:29', $commit->getAuthoredAt()->format('Y-m-d H:i:s'));
        $this->assertEquals('2016-11-23 13:17:29', $commit->getCommitedAt()->format('Y-m-d H:i:s'));
    }

    public function testIsParsingCommitWithMarkupBreakingMetadataAndItsDiff(): void
    {
        $path = $this->createTemporaryRepository();
        $this->commit($path, 'a.txt', 'A & B ]]', 'a&b@example.com', "init & <item> ]]> </item>\n");

        $repository = new Repository($path);
        $commandLine = new CommandLine();
        $commit = $commandLine->getCommit($repository);

        $this->assertEquals('init & <item> ]]> </item>', $commit->getSubject());
        $this->assertEquals('A & B ]]', $commit->getAuthor()->getName());
        $this->assertCount(1, $commit->getDiffs());
        $this->assertMatchesRegularExpression('/^diff --git a\/a.txt b\/a.txt/m', $commit->getRawDiffs());
    }

    public function testIsParsingCommitsWithMetadataCarryingFieldDelimiters(): void
    {
        $path = $this->createTemporaryRepository();
        $this->commit($path, 'a.txt', 'Klaus Silveira', 'contact@klaussilveira.com', "Initial commit.\n");
        $this->commit($path, 'b.txt', "R\x1eS\x1fT", 'test@example.com', "injected \x1f subject\n\ninjected \x1e body\n");

        $repository = new Repository($path);
        $commandLine = new CommandLine();
        $commits = $commandLine->getCommits($repository);

        $this->assertCount(2, $commits);

        $injected = array_shift($commits);
        $this->assertEquals("injected \x1f subject", $injected->getSubject());
        $this->assertEquals("injected \x1e body", trim($injected->getBody()));
        $this->assertEquals("R\x1eS\x1fT", $injected->getAuthor()->getName());
        $this->assertEquals('test@example.com', $injected->getAuthor()->getEmail());
        $this->assertEquals('2016-11-23 13:17:29', $injected->getAuthoredAt()->format('Y-m-d H:i:s'));

        $initial = array_shift($commits);
        $this->assertEquals('Initial commit.', $initial->getSubject());
        $this->assertEquals('Klaus Silveira', $initial->getAuthor()->getName());
        $this->assertEquals('contact@klaussilveira.com', $initial->getAuthor()->getEmail());
        $this->assertEquals('2016-11-23 13:17:29', $initial->getAuthoredAt()->format('Y-m-d H:i:s'));
    }

    public function testIsGettingGraphOfAllBranches(): void
    {
        $commandLine = new CommandLine();
        $commits = $commandLine->getGraph(new Repository(self::FIXTURE_REPO), 20);

        $graph = array_map(fn ($commit) => [$commit->getShortHash(), array_map(fn ($parent) => $parent->getShortHash(), $commit->getParents())], array_values($commits));

        $this->assertEquals([
            ['a003d30', ['5570c14']],
            ['5570c14', ['85e6568']],
            ['85e6568', ['0e3ddf0']],
            ['859f67f', ['9d78a8c']],
            ['9d78a8c', ['0e3ddf0']],
            ['0e3ddf0', ['b064e71']],
            ['b064e71', []],
        ], $graph);
    }

    public function testIsLimitingGraph(): void
    {
        $commandLine = new CommandLine();
        $commits = $commandLine->getGraph(new Repository(self::FIXTURE_REPO), 2);

        $this->assertEquals(['a003d30', '5570c14'], array_map(fn ($commit) => $commit->getShortHash(), array_values($commits)));
    }

    public function testIsGettingAheadBehind(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);
        $commandLine = new CommandLine();

        $this->assertEquals([2, 3], $commandLine->getAheadBehind($repository, 'master', 'feature/1.2-dev'));
        $this->assertEquals([3, 2], $commandLine->getAheadBehind($repository, 'feature/1.2-dev', 'master'));
        $this->assertEquals([0, 0], $commandLine->getAheadBehind($repository, 'master', 'master'));
    }

    public function testIsRejectingUnknownRevisionInAheadBehind(): void
    {
        $this->expectException(InvalidCommitException::class);

        $commandLine = new CommandLine();
        $commandLine->getAheadBehind(new Repository(self::FIXTURE_REPO), 'master', 'does-not-exist');
    }

    public function testIsGettingMergeBase(): void
    {
        $commandLine = new CommandLine();

        $this->assertEquals('0e3ddf0c6d39e7a300bef61b99a914a86ce5e267', $commandLine->getMergeBase(new Repository(self::FIXTURE_REPO), 'master', 'feature/1.2-dev'));
    }

    public function testIsGettingNoMergeBaseForUnrelatedHistories(): void
    {
        $path = $this->createTemporaryRepository();
        $this->commit($path, 'a.txt', 'Test', 'test@example.com', "first\n");
        $this->git(['checkout', '--quiet', '--orphan', 'unrelated'], $path);
        $this->commit($path, 'b.txt', 'Test', 'test@example.com', "unrelated\n");

        $commandLine = new CommandLine();

        $this->assertNull($commandLine->getMergeBase(new Repository($path), 'master', 'unrelated'));
    }

    public function testIsGettingCommitsBetween(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);
        $commandLine = new CommandLine();

        $ahead = $commandLine->getCommitsBetween($repository, 'master', 'feature/1.2-dev', 10);
        $behind = $commandLine->getCommitsBetween($repository, 'feature/1.2-dev', 'master', 10);

        $this->assertEquals(['Added symlink.', 'Added new config.'], array_map(fn ($commit) => $commit->getSubject(), array_values($ahead)));
        $this->assertEquals(['Fixed mm.', 'Added mm.', 'Added symlink.'], array_map(fn ($commit) => $commit->getSubject(), array_values($behind)));
        $this->assertCount(1, $commandLine->getCommitsBetween($repository, 'master', 'feature/1.2-dev', 1));
    }

    public function testIsGettingDiffBetween(): void
    {
        $commandLine = new CommandLine();
        $files = $commandLine->getDiffBetween(new Repository(self::FIXTURE_REPO), 'master', 'feature/1.2-dev');

        $this->assertEquals(['new.json', 'old.json'], array_map(fn ($file) => $file->getName(), $files));
    }

    public function testIsNotExecutingRepositoryControlledExternalDiff(): void
    {
        $path = $this->createTemporaryRepository();
        $marker = sprintf('%s/gitlist-external-diff-%s', sys_get_temp_dir(), uniqid());
        $this->temporaryRepositories[] = $marker;

        file_put_contents(sprintf('%s/rce.sh', $path), sprintf("#!/bin/sh\ntouch %s\n", escapeshellarg($marker)));
        chmod(sprintf('%s/rce.sh', $path), 0755);
        $this->git(['config', 'diff.external', './rce.sh'], $path);

        $this->commit($path, 'a.txt', 'Test', 'test@example.com', "first\n");
        $this->git(['checkout', '--quiet', '-b', 'feature'], $path);
        file_put_contents(sprintf('%s/a.txt', $path), "changed\n");
        $this->git(['-c', 'commit.gpgsign=false', '-c', 'user.name=Test', '-c', 'user.email=test@example.com', 'commit', '--quiet', '--all', '--message', 'change'], $path);

        $commandLine = new CommandLine();
        $files = $commandLine->getDiffBetween(new Repository($path), 'master', 'feature');

        $this->assertFileDoesNotExist($marker);
        $this->assertEquals(['a.txt'], array_map(fn ($file) => $file->getName(), $files));
    }

    public function testIsGettingForkPoint(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);
        $commandLine = new CommandLine();

        $this->assertEquals('0e3ddf0c6d39e7a300bef61b99a914a86ce5e267', $commandLine->getForkPoint($repository, 'feature/1.2-dev', ['master']));
        $this->assertEquals('0e3ddf0c6d39e7a300bef61b99a914a86ce5e267', $commandLine->getForkPoint($repository, 'master', ['feature/1.2-dev']));
        $this->assertNull($commandLine->getForkPoint($repository, 'feature/1.2-dev', []));
        $this->assertNull($commandLine->getForkPoint($repository, 'feature/1.2-dev', ['feature/1.2-dev']));
    }

    public function testIsGettingRefsWithPeeledTags(): void
    {
        $commandLine = new CommandLine();

        $this->assertEquals([
            ['type' => 'branch', 'name' => 'feature/1.2-dev', 'hash' => '859f67f18fc4522a4da5b784bce6cb9ada034a16'],
            ['type' => 'branch', 'name' => 'master', 'hash' => 'a003d30bc7a355f55bf28479e62134186bae1aed'],
            ['type' => 'tag', 'name' => '1.2', 'hash' => '9d78a8cbc59caa98aebaf4ca9d6ee63cca241c4d'],
        ], $commandLine->getRefs(new Repository(self::FIXTURE_REPO)));
    }

    public function testIsGettingBranchesContainingCommit(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);
        $commandLine = new CommandLine();

        $this->assertEquals(['feature/1.2-dev', 'master'], $commandLine->getBranchesContaining($repository, '0e3ddf0c6d39e7a300bef61b99a914a86ce5e267'));
        $this->assertEquals(['master'], $commandLine->getBranchesContaining($repository, 'a003d30bc7a355f55bf28479e62134186bae1aed'));
    }

    public function testIsReportingSignatureStatus(): void
    {
        if (empty(shell_exec('which ssh-keygen 2> /dev/null'))) {
            $this->markTestSkipped('ssh-keygen is not available.');
        }

        $path = $this->createTemporaryRepository();
        $keys = sprintf('%s/.keys', $path);
        mkdir($keys);
        (new Process(['ssh-keygen', '-q', '-t', 'ed25519', '-N', '', '-C', 'trusted', '-f', sprintf('%s/trusted', $keys)]))->mustRun();
        (new Process(['ssh-keygen', '-q', '-t', 'ed25519', '-N', '', '-C', 'stranger', '-f', sprintf('%s/stranger', $keys)]))->mustRun();
        file_put_contents(sprintf('%s/allowed', $keys), sprintf('test@example.com %s', file_get_contents(sprintf('%s/trusted.pub', $keys))));

        $this->git(['config', 'gpg.format', 'ssh'], $path);
        $this->git(['config', 'gpg.ssh.allowedSignersFile', sprintf('%s/allowed', $keys)], $path);

        $this->signedCommit($path, 'a.txt', sprintf('%s/trusted.pub', $keys), "trusted\n");
        $this->signedCommit($path, 'b.txt', sprintf('%s/stranger.pub', $keys), "stranger\n");
        $this->commit($path, 'c.txt', 'Test', 'test@example.com', "unsigned\n");

        $forged = new Process(['sh', '-c', 'git cat-file commit HEAD~2 | sed "s/^trusted$/forged/" | git hash-object -t commit -w --stdin'], $path);
        $forged->mustRun();
        $this->git(['update-ref', 'refs/heads/forged', trim($forged->getOutput())], $path);

        $repository = new Repository($path);
        $commandLine = new CommandLine();
        $commits = $commandLine->getCommits($repository);
        [$unsigned, $stranger, $trusted] = array_values($commits);

        $this->assertNull($unsigned->getSignature());

        $this->assertTrue($trusted->getSignature()->isValid());
        $this->assertFalse($trusted->getSignature()->isBad());

        $this->assertFalse($stranger->getSignature()->isValid());
        $this->assertFalse($stranger->getSignature()->isBad());
        $this->assertSame('U', $stranger->getSignature()->getStatus());

        $forgedCommit = $commandLine->getCommit($repository, 'forged');
        $this->assertSame('forged', $forgedCommit->getSubject());
        $this->assertFalse($forgedCommit->getSignature()->isValid());
        $this->assertTrue($forgedCommit->getSignature()->isBad());
    }

    public function testIsNotExecutingRepositoryControlledTextconvHelper(): void
    {
        $path = $this->createTemporaryRepository();
        $marker = sys_get_temp_dir().'/gitlist-textconv-'.uniqid();
        $this->temporaryRepositories[] = $marker;

        file_put_contents($path.'/rce.sh', sprintf("#!/bin/sh\ntouch %s\ncat \"\$1\"\n", escapeshellarg($marker)));
        chmod($path.'/rce.sh', 0755);
        file_put_contents($path.'/.gitattributes', "*.poc diff=poc\n");
        file_put_contents($path.'/demo.poc', "first\n");

        $this->git(['config', 'user.name', 'tester'], $path);
        $this->git(['config', 'user.email', 'tester@example.com'], $path);
        $this->git(['config', 'diff.poc.textconv', './rce.sh'], $path);
        $this->git(['add', '.gitattributes', 'demo.poc', 'rce.sh'], $path);
        $this->git(['-c', 'commit.gpgsign=false', 'commit', '--quiet', '--message', 'init'], $path);

        file_put_contents($path.'/demo.poc', "second\n");
        $this->git(['add', 'demo.poc'], $path);
        $this->git(['-c', 'commit.gpgsign=false', 'commit', '--quiet', '--message', 'second'], $path);

        $commandLine = new CommandLine();
        $commandLine->getCommit(new Repository($path));
        $commandLine->getBlame(new Repository($path), 'HEAD', 'demo.poc');

        $this->assertFileDoesNotExist($marker);
    }

    public function testIsGettingHooks(): void
    {
        $path = $this->createTemporaryRepository();
        $hooks = sprintf('%s/.git/hooks', $path);
        (new Filesystem())->remove(glob(sprintf('%s/*', $hooks)));

        file_put_contents(sprintf('%s/pre-receive', $hooks), "#!/bin/sh\nexit 0\n");
        chmod(sprintf('%s/pre-receive', $hooks), 0755);
        file_put_contents(sprintf('%s/update', $hooks), "#!/bin/sh\n");
        chmod(sprintf('%s/update', $hooks), 0644);
        file_put_contents(sprintf('%s/commit-msg.sample', $hooks), "#!/bin/sh\n");
        chmod(sprintf('%s/commit-msg.sample', $hooks), 0755);
        mkdir(sprintf('%s/pre-receive.d', $hooks));
        file_put_contents(sprintf('%s/outside', $path), "secret\n");
        chmod(sprintf('%s/outside', $path), 0755);
        symlink(sprintf('%s/outside', $path), sprintf('%s/post-receive', $hooks));
        symlink(sprintf('%s/pre-receive', $hooks), sprintf('%s/post-update', $hooks));

        $result = (new CommandLine())->getHooks(new Repository($path));
        $summary = array_map(fn ($hook) => [$hook->getName(), $hook->isActive(), $hook->isExecutable(), $hook->isSample()], $result);

        $this->assertSame([
            ['commit-msg.sample', false, true, true],
            ['post-update', true, true, false],
            ['pre-receive', true, true, false],
            ['update', false, false, false],
        ], $summary);
        $this->assertSame("#!/bin/sh\nexit 0\n", $result[2]->getContents());
        $this->assertSame(17, $result[2]->getSize());
        $this->assertSame('306c6ca74075', $result[2]->getChecksum());
    }

    public function testIsGettingHooksFromHooksPath(): void
    {
        $path = $this->createTemporaryRepository();
        mkdir(sprintf('%s/custom-hooks', $path));
        file_put_contents(sprintf('%s/custom-hooks/pre-commit', $path), "#!/bin/sh\n");
        chmod(sprintf('%s/custom-hooks/pre-commit', $path), 0755);
        $this->git(['config', 'core.hooksPath', 'custom-hooks'], $path);

        $commandLine = new CommandLine();
        $this->assertSame(['pre-commit'], array_map(fn ($hook) => $hook->getName(), $commandLine->getHooks(new Repository($path))));

        $this->git(['config', 'core.hooksPath', 'missing-hooks'], $path);
        $this->assertSame([], $commandLine->getHooks(new Repository($path)));
    }

    public function testIsGettingAttributes(): void
    {
        $rules = (new CommandLine())->getAttributes(new Repository(FIXTURE_DIR.'/git-introspection-repo'), 'master');

        $this->assertSame([
            ['.gitattributes', 1, '*', ['text' => 'auto']],
            ['.gitattributes', 2, '*.md', ['text' => true, 'eol' => 'lf']],
            ['.gitattributes', 3, '*.bin', ['filter' => 'lfs', 'diff' => 'lfs', 'merge' => 'lfs', 'text' => false]],
            ['.gitattributes', 4, '[attr]docs', ['linguist-documentation' => true, 'diff' => false]],
            ['docs/.gitattributes', 1, '*.md', ['docs' => true]],
            ['info/attributes', 1, 'docs/guide.md', ['export-ignore' => true]],
        ], array_map(fn ($rule) => [$rule->getSource(), $rule->getLine(), $rule->getPattern(), $rule->getAttributes()], $rules));
    }

    public function testIsGettingOnlyAttributesApplyingToPath(): void
    {
        $commandLine = new CommandLine();
        $repository = new Repository(FIXTURE_DIR.'/git-introspection-repo');
        $describe = fn (array $rules) => array_map(fn ($rule) => sprintf('%s:%d', $rule->getSource(), $rule->getLine()), $rules);

        $this->assertSame(['.gitattributes:1', '.gitattributes:2', '.gitattributes:3', '.gitattributes:4', 'info/attributes:1'], $describe($commandLine->getAttributes($repository, 'master', 'README.md')));
        $this->assertSame(['.gitattributes:1', '.gitattributes:2', '.gitattributes:3', '.gitattributes:4', 'docs/.gitattributes:1', 'info/attributes:1'], $describe($commandLine->getAttributes($repository, 'master', 'docs/guide.md')));
        $this->assertSame(['.gitattributes:1', '.gitattributes:2', '.gitattributes:3', '.gitattributes:4', 'info/attributes:1'], $describe($commandLine->getAttributes($repository, 'master', 'docsx/guide.md')));
    }

    public function testIsListingSubmodulesInTree(): void
    {
        $tree = (new CommandLine())->getPathTree(new Repository(FIXTURE_DIR.'/git-introspection-repo'), 'vendor', 'master');
        [$submodule] = $tree->getChildren();

        $this->assertCount(1, $tree->getChildren());
        $this->assertInstanceOf(Submodule::class, $submodule);
        $this->assertSame('vendor/lib', $submodule->getName());
        $this->assertSame('lib', $submodule->getFileName());
        $this->assertSame('1111111111111111111111111111111111111111', $submodule->getHash());
        $this->assertSame('1111111', $submodule->getShortHash());
        $this->assertSame('https://example.com/lib.git', $submodule->getUrl());
        $this->assertTrue($submodule->isBrowsable());
        $this->assertSame('Initial commit.', $submodule->getFirstParent()->getSubject());
    }

    public function testIsOrderingAttributeFilesByDepth(): void
    {
        $path = $this->createTemporaryRepository();
        mkdir(sprintf('%s/a/-b', $path), 0755, true);
        file_put_contents(sprintf('%s/.gitattributes', $path), "* root\n");
        file_put_contents(sprintf('%s/a/.gitattributes', $path), "* parent\n");
        file_put_contents(sprintf('%s/a/-b/.gitattributes', $path), "* child\n");
        $this->git(['add', '.'], $path);
        $this->git(['-c', 'commit.gpgsign=false', '-c', 'user.name=Test', '-c', 'user.email=test@example.com', 'commit', '--quiet', '--message', 'attributes'], $path);

        $rules = (new CommandLine())->getAttributes(new Repository($path));

        $this->assertSame(['.gitattributes', 'a/.gitattributes', 'a/-b/.gitattributes'], array_map(fn ($rule) => $rule->getSource(), $rules));
    }

    public function testIsGettingNoAttributesWhenAbsent(): void
    {
        $this->assertSame([], (new CommandLine())->getAttributes(new Repository(self::FIXTURE_REPO)));
    }

    public function testIsGettingInfoWithoutSecrets(): void
    {
        $info = (new CommandLine())->getInfo(new Repository(FIXTURE_DIR.'/git-introspection-repo'));

        $this->assertSame(['origin' => 'https://example.com/repo.git', 'mirror' => 'git@example.com:mirror.git'], $info['remotes']);
        $this->assertSame(['core.repositoryformatversion' => '0', 'receive.denynonfastforwards' => 'true'], $info['config']);
        $this->assertGreaterThan(0, $info['properties']['loose_size']);

        unset($info['properties']['loose_size']);

        $this->assertSame([
            'object_format' => 'sha1',
            'bare' => true,
            'shallow' => false,
            'loose_objects' => 16,
            'packed_objects' => 0,
            'packs' => 0,
            'packed_size' => 0,
            'garbage' => 0,
            'alternates' => 0,
            'worktrees' => 0,
        ], $info['properties']);
    }

    public function testIsGettingInfoForLinkedRepository(): void
    {
        $path = $this->createTemporaryRepository();
        $this->commit($path, 'a.txt', 'Test', 'test@example.com', 'first');
        $worktree = sprintf('%s-worktree', $path);
        $clone = sprintf('%s-clone', $path);
        $this->temporaryRepositories[] = $worktree;
        $this->temporaryRepositories[] = $clone;

        $this->git(['worktree', 'add', '--quiet', $worktree], $path);
        file_put_contents(sprintf('%s/.git/objects/info/alternates', $path), sprintf("%s/.git/objects\n", $clone));
        $this->git(['clone', '--quiet', '--depth', '1', sprintf('file://%s', $path), $clone], sys_get_temp_dir());

        $commandLine = new CommandLine();
        $info = $commandLine->getInfo(new Repository($path));

        $this->assertFalse($info['properties']['bare']);
        $this->assertFalse($info['properties']['shallow']);
        $this->assertSame(1, $info['properties']['worktrees']);
        $this->assertSame(1, $info['properties']['alternates']);
        $this->assertTrue($commandLine->getInfo(new Repository($clone))['properties']['shallow']);
    }

    public function testIsGettingSubmodules(): void
    {
        $commandLine = new CommandLine();

        $this->assertSame([
            ['path' => 'vendor/lib', 'url' => 'https://example.com/lib.git', 'hash' => '1111111111111111111111111111111111111111'],
        ], $commandLine->getSubmodules(new Repository(FIXTURE_DIR.'/git-introspection-repo'), 'master'));
        $this->assertSame([], $commandLine->getSubmodules(new Repository(self::FIXTURE_REPO)));
    }

    public function testIsGettingNotes(): void
    {
        $commandLine = new CommandLine();

        $this->assertSame(['commits' => "Reviewed by QA.\n", 'review' => "Ship it.\n"], $commandLine->getNotes(new Repository(FIXTURE_DIR.'/git-introspection-repo'), 'master'));
        $this->assertSame([], $commandLine->getNotes(new Repository(self::FIXTURE_REPO), 'master'));
    }

    protected function createTemporaryRepository(): string
    {
        $path = sys_get_temp_dir().'/gitlist-'.uniqid();
        mkdir($path);
        $this->temporaryRepositories[] = $path;

        $this->git(['-c', 'init.defaultBranch=master', 'init', '--quiet', '.'], $path);

        return $path;
    }

    protected function commit(string $path, string $file, string $name, string $email, string $message): void
    {
        file_put_contents($path.'/'.$file, "contents\n");

        $this->git(['add', $file], $path);
        $this->git(['-c', 'commit.gpgsign=false', 'commit', '--quiet', '--cleanup=verbatim', '--file', '-'], $path, [
            'GIT_AUTHOR_NAME' => $name,
            'GIT_AUTHOR_EMAIL' => $email,
            'GIT_AUTHOR_DATE' => self::COMMIT_DATE,
            'GIT_COMMITTER_NAME' => $name,
            'GIT_COMMITTER_EMAIL' => $email,
            'GIT_COMMITTER_DATE' => self::COMMIT_DATE,
        ], $message);
    }

    protected function signedCommit(string $path, string $file, string $key, string $message): void
    {
        file_put_contents(sprintf('%s/%s', $path, $file), "contents\n");

        $this->git(['add', $file], $path);
        $this->git(['-c', sprintf('user.signingkey=%s', $key), 'commit', '--quiet', '-S', '--cleanup=verbatim', '--file', '-'], $path, [
            'GIT_AUTHOR_NAME' => 'Test',
            'GIT_AUTHOR_EMAIL' => 'test@example.com',
            'GIT_AUTHOR_DATE' => self::COMMIT_DATE,
            'GIT_COMMITTER_NAME' => 'Test',
            'GIT_COMMITTER_EMAIL' => 'test@example.com',
            'GIT_COMMITTER_DATE' => self::COMMIT_DATE,
        ], $message);
    }

    protected function git(array $command, string $path, array $env = [], ?string $input = null): void
    {
        $process = new Process([...['git'], ...$command], $path, $env, $input);
        $process->mustRun();
    }
}
