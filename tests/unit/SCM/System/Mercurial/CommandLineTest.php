<?php

declare(strict_types=1);

namespace GitList\SCM\System\Mercurial;

use Carbon\Carbon;
use GitList\SCM\Blame;
use GitList\SCM\Blob;
use GitList\SCM\Commit\Criteria;
use GitList\SCM\Commit\Person;
use GitList\SCM\Repository;
use GitList\SCM\Symlink;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class CommandLineTest extends TestCase
{
    public const FIXTURE_REPO = FIXTURE_DIR.'/hg-repo';

    public function setUp(): void
    {
        if (empty(shell_exec('which hg 2> /dev/null'))) {
            $this->markTestSkipped('Mercurial is not available.');
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
        $this->assertEquals('foobar', $commandLine->getDescription(new Repository(self::FIXTURE_REPO)));
    }

    public function testIsGettingDefaultBranch(): void
    {
        $commandLine = new CommandLine();
        $this->assertEquals('default', $commandLine->getDefaultBranch(new Repository(self::FIXTURE_REPO)));
    }

    public function testIsGettingBranches(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);

        $commandLine = new CommandLine();
        $branches = $commandLine->getBranches($repository);

        $this->assertCount(2, $branches);

        // Branch master
        $this->assertEquals('master', $branches[0]->getName());
        $this->assertEquals('5ea48553c4dfbba5ee51189bb5a145f75db4b425', $branches[0]->getTarget()->getHash());
        $this->assertEquals('update tags', $branches[0]->getTarget()->getSubject());

        // Branch feature/1.2-dev
        $this->assertEquals('feature/1.2-dev', $branches[1]->getName());
        $this->assertEquals('7bc72b056e7c13b3d7a2f8fdb2d3fbb5556a7ec2', $branches[1]->getTarget()->getHash());
        $this->assertEquals('Added symlink.', $branches[1]->getTarget()->getSubject());
    }

    public function testIsGettingTags(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);

        $commandLine = new CommandLine();
        $tags = $commandLine->getTags($repository);

        $this->assertCount(2, $tags);

        $this->assertEquals('update tags', $tags[0]->getSubject());
        $this->assertEquals('tip', $tags[0]->getName());
        $this->assertEquals('5ea48553c4dfbba5ee51189bb5a145f75db4b425', $tags[0]->getTarget()->getHash());

        $this->assertEquals('Added new config.', $tags[1]->getSubject());
        $this->assertEquals('1.2', $tags[1]->getName());
        $this->assertEquals('8acbca7fcefa3029c29d6a3eb4a676e607a73c81', $tags[1]->getTarget()->getHash());
        $this->assertEquals('8acbca7fcefa', $tags[1]->getTarget()->getShortHash());
    }

    public function testIsGettingTree(): never
    {
        // Todo: Implement Mercurial tree parsing
        $this->markTestIncomplete();

        $repository = new Repository(self::FIXTURE_REPO);

        $commandLine = new CommandLine();
        $tree = $commandLine->getTree($repository);

        static::assertEquals('tip', $tree->getHash());
        static::assertCount(4, $tree->getChildren());

        $file1 = $tree->getChildren()[0];
        static::assertInstanceOf(Tree::class, $file1);
        static::assertEquals('mm', $file1->getName());
        static::assertEquals('3ef8d557da820c5c0fbb3a47c0f4b16cabd01a4c', $file1->getHash());
        static::assertEquals('644', $file1->getMode());
        static::assertEquals(0, $file1->getSize());

        $file2 = $tree->getChildren()[1];
        static::assertInstanceOf(Symlink::class, $file2);
        static::assertEquals('old.json', $file2->getName());
        static::assertEquals('new.json', $file2->getTarget());
        static::assertEquals('fed4d44d9baa4e47f0f29da6cf76a53fa6252225', $file2->getHash());
        static::assertEquals('644', $file2->getMode());
        static::assertEquals(0, $file2->getSize());

        $file3 = $tree->getChildren()[2];
        static::assertInstanceOf(Tree::class, $file3);
        static::assertEquals('src', $file3->getName());
        static::assertEquals('8afa803b8db8f69d45ca64f6e75abd240f5c4417', $file3->getHash());
        static::assertEquals('644', $file3->getMode());
        static::assertEquals(0, $file3->getSize());

        $file4 = $tree->getChildren()[3];
        static::assertInstanceOf(Blob::class, $file4);
        static::assertEquals('test.json', $file4->getName());
        static::assertEquals('b80de5d138758541c5f05265ad144ab9fa86d1db', $file4->getHash());
        static::assertEquals('644', $file4->getMode());
        static::assertEquals(0, $file4->getSize());
    }

    public function testIsGettingRecursiveTree(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);

        $commandLine = new CommandLine();
        $tree = $commandLine->getRecursiveTree($repository);

        $this->assertEquals('tip', $tree->getHash());
        $this->assertCount(6, $tree->getChildren());

        $file1 = $tree->getChildren()[0];
        $this->assertInstanceOf(Blob::class, $file1);
        $this->assertEquals('mm/cma.c', $file1->getName());
        $this->assertEquals('3ef8d557da820c5c0fbb3a47c0f4b16cabd01a4c', $file1->getHash());
        $this->assertEquals('644', $file1->getMode());
        $this->assertEquals(0, $file1->getSize());

        $file2 = $tree->getChildren()[1];
        $this->assertInstanceOf(Blob::class, $file2);
        $this->assertEquals('mm/cma.h', $file2->getName());
        $this->assertEquals('a1a1554ed45995782acadb10747e32246bc0d3f8', $file2->getHash());
        $this->assertEquals('644', $file2->getMode());
        $this->assertEquals(0, $file2->getSize());

        $file3 = $tree->getChildren()[2];
        $this->assertInstanceOf(Symlink::class, $file3);
        $this->assertEquals('old.json', $file3->getName());
        $this->assertEquals('new.json', $file3->getTarget());
        $this->assertEquals('fed4d44d9baa4e47f0f29da6cf76a53fa6252225', $file3->getHash());
        $this->assertEquals('644', $file3->getMode());
        $this->assertEquals(0, $file3->getSize());

        $file4 = $tree->getChildren()[3];
        $this->assertInstanceOf(Blob::class, $file4);
        $this->assertEquals('src/index.php', $file4->getName());
        $this->assertEquals('8afa803b8db8f69d45ca64f6e75abd240f5c4417', $file4->getHash());
        $this->assertEquals('644', $file4->getMode());
        $this->assertEquals(0, $file4->getSize());

        $file5 = $tree->getChildren()[4];
        $this->assertInstanceOf(Blob::class, $file5);
        $this->assertEquals('src/test.php', $file5->getName());
        $this->assertEquals('b80de5d138758541c5f05265ad144ab9fa86d1db', $file5->getHash());
        $this->assertEquals('644', $file5->getMode());
        $this->assertEquals(0, $file5->getSize());

        $file6 = $tree->getChildren()[5];
        $this->assertInstanceOf(Blob::class, $file6);
        $this->assertEquals('test.json', $file6->getName());
        $this->assertEquals('b80de5d138758541c5f05265ad144ab9fa86d1db', $file6->getHash());
        $this->assertEquals('644', $file6->getMode());
        $this->assertEquals(0, $file6->getSize());
    }

    public function testIsGettingPathTree(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);

        $commandLine = new CommandLine();
        $tree = $commandLine->getPathTree($repository, 'mm/');

        $this->assertEquals('tip', $tree->getHash());
        $children = $tree->getChildren();
        $this->assertCount(2, $children);

        $file1 = $children[0];
        $this->assertInstanceOf(Blob::class, $file1);
        $this->assertEquals('mm/cma.c', $file1->getName());
        $this->assertEquals('3ef8d557da820c5c0fbb3a47c0f4b16cabd01a4c', $file1->getHash());
        $this->assertEquals('644', $file1->getMode());
        $this->assertEquals(0, $file1->getSize());

        $file2 = $children[1];
        $this->assertInstanceOf(Blob::class, $file2);
        $this->assertEquals('mm/cma.h', $file2->getName());
        $this->assertEquals('a1a1554ed45995782acadb10747e32246bc0d3f8', $file2->getHash());
        $this->assertEquals('644', $file2->getMode());
        $this->assertEquals(0, $file2->getSize());
    }

    public function testIsGettingCommits(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);

        $commandLine = new CommandLine();
        $commits = $commandLine->getCommits($repository);

        $commit1 = $commits['5ea48553c4df'];
        $this->assertEquals('5ea48553c4dfbba5ee51189bb5a145f75db4b425', $commit1->getHash());
        $this->assertEquals('update tags', $commit1->getSubject());
        $this->assertInstanceOf(Person::class, $commit1->getAuthor());
        $this->assertEquals('convert-repo', $commit1->getAuthor()->getEmail());
        $this->assertEquals('convert-repo', $commit1->getAuthor()->getName());
        $this->assertEquals('2017-12-29 22:08:29', $commit1->getCommitedAt()->format('Y-m-d H:i:s'));

        $commit2 = $commits['d471ea0b4d78'];
        $this->assertEquals('d471ea0b4d78d5296f7c266161ba79f1f0be4927', $commit2->getHash());
        $this->assertEquals('Fixed mm.', $commit2->getSubject());
        $this->assertInstanceOf(Person::class, $commit2->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit2->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit2->getAuthor()->getName());
        $this->assertEquals('2016-11-24 10:30:04', $commit2->getCommitedAt()->format('Y-m-d H:i:s'));

        $commit3 = $commits['4447d3262dcd'];
        $this->assertEquals('4447d3262dcd2ec2a122813ae6dcb64b4941a305', $commit3->getHash());
        $this->assertEquals('Added mm.', $commit3->getSubject());
        $this->assertInstanceOf(Person::class, $commit3->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit3->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit3->getAuthor()->getName());
        $this->assertEquals('2016-11-24 10:28:56', $commit3->getCommitedAt()->format('Y-m-d H:i:s'));

        $commit4 = $commits['828cda676385'];
        $this->assertEquals('828cda676385bf21433a1c313c1b3bb84ef87232', $commit4->getHash());
        $this->assertEquals('Added symlink.', $commit4->getSubject());
        $this->assertInstanceOf(Person::class, $commit4->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit4->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit4->getAuthor()->getName());
        $this->assertEquals('2016-11-23 15:55:30', $commit4->getCommitedAt()->format('Y-m-d H:i:s'));
    }

    public function testIsGettingCommitsWithPagination(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);

        $commandLine = new CommandLine();
        $commits = $commandLine->getCommits($repository, 'tip', 1, 3);
        $this->assertCount(3, $commits);

        $commits = $commandLine->getCommits($repository, 'tip', 2, 3);
        $this->assertCount(5, $commits);
    }

    public function testIsGettingCommitsFromPath(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);

        $commandLine = new CommandLine();
        $commits = $commandLine->getCommitsFromPath($repository, 'mm/cma.c');
        $this->assertCount(2, $commits);

        $commit1 = $commits['d471ea0b4d78'];
        $this->assertEquals('d471ea0b4d78d5296f7c266161ba79f1f0be4927', $commit1->getHash());
        $this->assertEquals('Fixed mm.', $commit1->getSubject());
        $this->assertInstanceOf(Person::class, $commit1->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit1->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit1->getAuthor()->getName());
        $this->assertEquals('2016-11-24 10:30:04', $commit1->getCommitedAt()->format('Y-m-d H:i:s'));

        $commit2 = $commits['4447d3262dcd'];
        $this->assertEquals('4447d3262dcd2ec2a122813ae6dcb64b4941a305', $commit2->getHash());
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
        $commit = $commandLine->getCommit($repository, 'd471ea0b4d78d5296f7c266161ba79f1f0be4927');

        $this->assertEquals('d471ea0b4d78d5296f7c266161ba79f1f0be4927', $commit->getHash());
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
            'd471ea0b4d78d5296f7c266161ba79f1f0be4927',
            '4447d3262dcd2ec2a122813ae6dcb64b4941a305',
        ]);

        $commit1 = $commits['d471ea0b4d78'];
        $this->assertEquals('d471ea0b4d78d5296f7c266161ba79f1f0be4927', $commit1->getHash());
        $this->assertEquals('Fixed mm.', $commit1->getSubject());
        $this->assertInstanceOf(Person::class, $commit1->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit1->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit1->getAuthor()->getName());
        $this->assertEquals('2016-11-24 10:30:04', $commit1->getCommitedAt()->format('Y-m-d H:i:s'));

        $commit2 = $commits['4447d3262dcd'];
        $this->assertEquals('4447d3262dcd2ec2a122813ae6dcb64b4941a305', $commit2->getHash());
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
        $this->assertEquals('4447d3262dcd2ec2a122813ae6dcb64b4941a305', $blame->getAnnotatedLines()[90]->getCommit()->getHash());
        $this->assertEquals("\tstruct zone *zone;", $blame->getAnnotatedLines()[90]->getContents());
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
        $this->assertCount(3, $commits);

        $commit1 = $commits['5ea48553c4df'];
        $this->assertEquals('5ea48553c4dfbba5ee51189bb5a145f75db4b425', $commit1->getHash());
        $this->assertEquals('update tags', $commit1->getSubject());
        $this->assertInstanceOf(Person::class, $commit1->getAuthor());
        $this->assertEquals('convert-repo', $commit1->getAuthor()->getEmail());
        $this->assertEquals('convert-repo', $commit1->getAuthor()->getName());
        $this->assertEquals('2017-12-29 22:08:29', $commit1->getCommitedAt()->format('Y-m-d H:i:s'));

        $commit2 = $commits['d471ea0b4d78'];
        $this->assertEquals('d471ea0b4d78d5296f7c266161ba79f1f0be4927', $commit2->getHash());
        $this->assertEquals('Fixed mm.', $commit2->getSubject());
        $this->assertInstanceOf(Person::class, $commit2->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit2->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit2->getAuthor()->getName());
        $this->assertEquals('2016-11-24 10:30:04', $commit2->getCommitedAt()->format('Y-m-d H:i:s'));

        $commit3 = $commits['4447d3262dcd'];
        $this->assertEquals('4447d3262dcd2ec2a122813ae6dcb64b4941a305', $commit3->getHash());
        $this->assertEquals('Added mm.', $commit3->getSubject());
        $this->assertInstanceOf(Person::class, $commit3->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit3->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit3->getAuthor()->getName());
        $this->assertEquals('2016-11-24 10:28:56', $commit3->getCommitedAt()->format('Y-m-d H:i:s'));
    }

    public function testIsSearchingCommitsToDate(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);
        $criteria = new Criteria();
        $criteria->setTo(new Carbon('2016-11-24'));

        $commandLine = new CommandLine();
        $commits = $commandLine->searchCommits($repository, $criteria);
        $this->assertCount(5, $commits);

        $commit1 = $commits['828cda676385'];
        $this->assertEquals('828cda676385bf21433a1c313c1b3bb84ef87232', $commit1->getHash());
        $this->assertEquals('Added symlink.', $commit1->getSubject());
        $this->assertInstanceOf(Person::class, $commit1->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit1->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit1->getAuthor()->getName());
        $this->assertEquals('2016-11-23 15:55:30', $commit1->getCommitedAt()->format('Y-m-d H:i:s'));

        $commit2 = $commits['7bc72b056e7c'];
        $this->assertEquals('7bc72b056e7c13b3d7a2f8fdb2d3fbb5556a7ec2', $commit2->getHash());
        $this->assertEquals('Added symlink.', $commit2->getSubject());
        $this->assertInstanceOf(Person::class, $commit2->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit2->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit2->getAuthor()->getName());
        $this->assertEquals('2016-11-23 15:55:07', $commit2->getCommitedAt()->format('Y-m-d H:i:s'));

        $commit3 = $commits['8acbca7fcefa'];
        $this->assertEquals('8acbca7fcefa3029c29d6a3eb4a676e607a73c81', $commit3->getHash());
        $this->assertEquals('Added new config.', $commit3->getSubject());
        $this->assertInstanceOf(Person::class, $commit3->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit3->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit3->getAuthor()->getName());
        $this->assertEquals('2016-11-23 13:19:02', $commit3->getCommitedAt()->format('Y-m-d H:i:s'));

        $commit4 = $commits['bf6c7defa0f2'];
        $this->assertEquals('bf6c7defa0f269df1389cef460c98ea18e007a8d', $commit4->getHash());
        $this->assertEquals('Added function call.', $commit4->getSubject());
        $this->assertInstanceOf(Person::class, $commit4->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit4->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit4->getAuthor()->getName());
        $this->assertEquals('2016-11-23 13:18:18', $commit4->getCommitedAt()->format('Y-m-d H:i:s'));

        $commit5 = $commits['e88dc2aa0e74'];
        $this->assertEquals('e88dc2aa0e74b10445f92de34d2a61d274a919fe', $commit5->getHash());
        $this->assertEquals('Initial commit.', $commit5->getSubject());
        $this->assertInstanceOf(Person::class, $commit5->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit5->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit5->getAuthor()->getName());
        $this->assertEquals('2016-11-23 13:17:29', $commit5->getCommitedAt()->format('Y-m-d H:i:s'));
    }

    public function testIsSearchingCommitsByAuthor(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);
        $criteria = new Criteria();
        $criteria->setAuthor('convert-repo');

        $commandLine = new CommandLine();
        $commits = $commandLine->searchCommits($repository, $criteria);
        $this->assertCount(1, $commits);

        $commit1 = $commits['5ea48553c4df'];
        $this->assertEquals('5ea48553c4dfbba5ee51189bb5a145f75db4b425', $commit1->getHash());
        $this->assertEquals('update tags', $commit1->getSubject());
        $this->assertInstanceOf(Person::class, $commit1->getAuthor());
        $this->assertEquals('convert-repo', $commit1->getAuthor()->getEmail());
        $this->assertEquals('convert-repo', $commit1->getAuthor()->getName());
        $this->assertEquals('2017-12-29 22:08:29', $commit1->getCommitedAt()->format('Y-m-d H:i:s'));
    }

    public function testIsSearchingCommitsByMessage(): void
    {
        $repository = new Repository(self::FIXTURE_REPO);
        $criteria = new Criteria();
        $criteria->setMessage('Initial commit.');

        $commandLine = new CommandLine();
        $commits = $commandLine->searchCommits($repository, $criteria);
        $this->assertCount(1, $commits);

        $commit1 = $commits['e88dc2aa0e74'];
        $this->assertEquals('e88dc2aa0e74b10445f92de34d2a61d274a919fe', $commit1->getHash());
        $this->assertEquals('Initial commit.', $commit1->getSubject());
        $this->assertInstanceOf(Person::class, $commit1->getAuthor());
        $this->assertEquals('contact@klaussilveira.com', $commit1->getAuthor()->getEmail());
        $this->assertEquals('Klaus Silveira', $commit1->getAuthor()->getName());
        $this->assertEquals('2016-11-23 13:17:29', $commit1->getCommitedAt()->format('Y-m-d H:i:s'));
    }

    public function testIsArchivingZip(): void
    {
        $commandLine = new CommandLine();
        $archive = $commandLine->archive(new Repository(self::FIXTURE_REPO), 'zip', 'tip');
        $this->assertFileExists($archive);

        $zip = new ZipArchive();
        $zip->open($archive);
        $this->assertEquals(8, $zip->numFiles);
    }

    public function testIsArchivingZipWithPath(): void
    {
        $commandLine = new CommandLine();
        $archive = $commandLine->archive(new Repository(self::FIXTURE_REPO), 'zip', 'tip', 'mm');
        $this->assertFileExists($archive);

        $zip = new ZipArchive();
        $zip->open($archive);
        $this->assertEquals(2, $zip->numFiles);
    }

    public function testIsArchivingTarball(): void
    {
        $commandLine = new CommandLine();
        $archive = $commandLine->archive(new Repository(self::FIXTURE_REPO), 'tar', 'tip');
        $this->assertFileExists($archive);

        $archive = $commandLine->archive(new Repository(self::FIXTURE_REPO), 'tar.gz', 'tip');
        $this->assertFileExists($archive);
    }
}
