<?php

use Gitter\Client;
use Gitter\Repository;
use Symfony\Component\Filesystem\Filesystem;

class RepositoryTest extends PHPUnit_Framework_TestCase
{
    protected static $tmpdir;

    protected $client;

    public static function setUpBeforeClass()
    {
        if (getenv('TMP')) {
            self::$tmpdir = getenv('TMP');
        } elseif (getenv('TMPDIR')) {
            self::$tmpdir = getenv('TMPDIR');
        } else {
           self::$tmpdir = '/tmp';
        }

        self::$tmpdir .= '/gitlist_' . md5(time() . mt_rand());

        $fs = new Filesystem();
        $fs->mkdir(self::$tmpdir);

        if (!is_writable(self::$tmpdir)) {
            $this->markTestSkipped('There are no write permissions in order to create test repositories.');
        }
    }

    public function setUp()
    {
        if (!is_writable(self::$tmpdir)) {
            $this->markTestSkipped('There are no write permissions in order to create test repositories.');
        }

        $options = array(
            'path' => getenv('GIT_CLIENT') ?: null,
        );
        $this->client = new Client($options);
    }

    public function testIsCreatingRepositoryFixtures()
    {
        $a = $this->client->createRepository(self::$tmpdir . '/testrepo');
        $b = $this->client->createRepository(self::$tmpdir . '/anothertestrepo');
        $c = $this->client->createRepository(self::$tmpdir . '/bigbadrepo');
        $this->assertRegExp("/nothing to commit/", $a->getClient()->run($a, 'status'));
        $this->assertRegExp("/nothing to commit/", $b->getClient()->run($b, 'status'));
        $this->assertRegExp("/nothing to commit/", $c->getClient()->run($c, 'status'));
    }

    public function testIsConfiguratingRepository()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');
        $repository->setConfig('user.name', 'Luke Skywalker');
        $repository->setConfig('user.email', 'luke@rebel.org');

        $this->assertEquals($repository->getConfig('user.name'), 'Luke Skywalker');
        $this->assertEquals($repository->getConfig('user.email'), 'luke@rebel.org');
    }

    public function testIsAdding()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');
        file_put_contents(self::$tmpdir . '/testrepo/test_file.txt', 'Your mother is so ugly, glCullFace always returns TRUE.');
        $repository->add('test_file.txt');
        $this->assertRegExp("/new file:   test_file.txt/", $repository->getClient()->run($repository, 'status'));
    }

    /**
     * @depends testIsAdding
     */
    public function testIsAddingDot()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');

        file_put_contents(self::$tmpdir . '/testrepo/test_file1.txt', 'Your mother is so ugly, glCullFace always returns TRUE.');
        file_put_contents(self::$tmpdir . '/testrepo/test_file2.txt', 'Your mother is so ugly, glCullFace always returns TRUE.');
        file_put_contents(self::$tmpdir . '/testrepo/test_file3.txt', 'Your mother is so ugly, glCullFace always returns TRUE.');

        $repository->add();

        $this->assertRegExp("/new file:   test_file1.txt/", $repository->getClient()->run($repository, 'status'));
        $this->assertRegExp("/new file:   test_file2.txt/", $repository->getClient()->run($repository, 'status'));
        $this->assertRegExp("/new file:   test_file3.txt/", $repository->getClient()->run($repository, 'status'));
    }

    /**
     * @depends testIsAddingDot
     */
    public function testIsAddingAll()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');

        file_put_contents(self::$tmpdir . '/testrepo/test_file4.txt', 'Your mother is so ugly, glCullFace always returns TRUE.');
        file_put_contents(self::$tmpdir . '/testrepo/test_file5.txt', 'Your mother is so ugly, glCullFace always returns TRUE.');
        file_put_contents(self::$tmpdir . '/testrepo/test_file6.txt', 'Your mother is so ugly, glCullFace always returns TRUE.');

        $repository->addAll();

        $this->assertRegExp("/new file:   test_file4.txt/", $repository->getClient()->run($repository, 'status'));
        $this->assertRegExp("/new file:   test_file5.txt/", $repository->getClient()->run($repository, 'status'));
        $this->assertRegExp("/new file:   test_file6.txt/", $repository->getClient()->run($repository, 'status'));
    }

    /**
     * @depends testIsAddingAll
     */
    public function testIsAddingArrayOfFiles()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');

        file_put_contents(self::$tmpdir . '/testrepo/test_file7.txt', 'Your mother is so ugly, glCullFace always returns TRUE.');
        file_put_contents(self::$tmpdir . '/testrepo/test_file8.txt', 'Your mother is so ugly, glCullFace always returns TRUE.');
        file_put_contents(self::$tmpdir . '/testrepo/test_file9.txt', 'Your mother is so ugly, glCullFace always returns TRUE.');

        $repository->add(array('test_file7.txt', 'test_file8.txt', 'test_file9.txt'));

        $this->assertRegExp("/new file:   test_file7.txt/", $repository->getClient()->run($repository, 'status'));
        $this->assertRegExp("/new file:   test_file8.txt/", $repository->getClient()->run($repository, 'status'));
        $this->assertRegExp("/new file:   test_file9.txt/", $repository->getClient()->run($repository, 'status'));
    }

    /**
     * @depends testIsAddingArrayOfFiles
     */
    public function testIsCommiting()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');
        $repository->commit("The truth unveiled");
        $this->assertRegExp("/The truth unveiled/", $repository->getClient()->run($repository, 'log'));
    }

    public function testIsCreatingBranches()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');
        $repository->createBranch('issue12');
        $repository->createBranch('issue42');
        $branches = $repository->getBranches();
        $this->assertContains('issue12', $branches);
        $this->assertContains('issue42', $branches);
        $this->assertContains('master', $branches);
    }

    public function testIsCreatingTags()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');
        $repository->createTag('1.0.0');
        $repository->createTag('1.0.1', 'annotated tag');
        $tags = $repository->getTags();
        $this->assertContains('1.0.0', $tags);
        $this->assertContains('1.0.1', $tags);
    }

    public function testIsGettingCurrentBranch()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');
        $branch = $repository->getCurrentBranch();
        $this->assertTrue($branch === 'master');
    }

    public function testIsCheckingIfBranchExists()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');
        $this->assertTrue($repository->hasBranch('issue12'));
    }

    public function testIsCheckingOut()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');
        $branch = $repository->checkout('issue12');
        $branch = $repository->getCurrentBranch();
        $this->assertTrue($branch === 'issue12');
        $repository->checkout('master');
        $branch = $repository->getCurrentBranch();
        $this->assertTrue($branch === 'master');
    }

    /**
     * @depends testIsCommiting
     */
    public function testIsGettingCommits()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');
        $commits = $repository->getCommits();

        foreach ($commits as $commit) {
            $this->assertInstanceOf('Gitter\Model\Commit\Commit', $commit);
            $this->assertEquals($commit->getMessage(), "The truth unveiled");
            $this->assertInstanceOf('Gitter\Model\Commit\Author', $commit->getAuthor());
            $this->assertEquals($commit->getAuthor()->getName(), 'Luke Skywalker');
            $this->assertEquals($commit->getAuthor()->getEmail(), 'luke@rebel.org');
            $this->assertEquals($commit->getCommiter()->getName(), 'Luke Skywalker');
            $this->assertEquals($commit->getCommiter()->getEmail(), 'luke@rebel.org');
            $this->assertEquals($commit->getParentsHash(), array());
            $this->assertInstanceOf('DateTime', $commit->getDate());
            $this->assertInstanceOf('DateTime', $commit->getCommiterDate());
            $this->assertRegExp('/[a-f0-9]+/', $commit->getHash());
            $this->assertRegExp('/[a-f0-9]+/', $commit->getShortHash());
            $this->assertRegExp('/[a-f0-9]+/', $commit->getTreeHash());
        }
    }

    /**
     * @depends testIsGettingCommits
     */
    public function testIsGettingCommitsFromSpecificFile()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');
        $commits = $repository->getCommits('test_file4.txt');

        foreach ($commits as $commit) {
            $this->assertInstanceOf('Gitter\Model\Commit\Commit', $commit);
            $this->assertEquals($commit->getMessage(), "The truth unveiled");
            $this->assertInstanceOf('Gitter\Model\Commit\Author', $commit->getAuthor());
            $this->assertEquals($commit->getAuthor()->getName(), 'Luke Skywalker');
            $this->assertEquals($commit->getAuthor()->getEmail(), 'luke@rebel.org');
        }
    }

    public function testIsGettingTree()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');
        $files = $repository->getTree('master');

        foreach ($files as $file) {
            $this->assertInstanceOf('Gitter\Model\Blob', $file);
            $this->assertRegExp('/test_file[0-9]*.txt/', $file->getName());
            $this->assertEquals($file->getSize(), '55');
            $this->assertEquals($file->getMode(), '100644');
            $this->assertRegExp('/[a-f0-9]+/', $file->getHash());
        }
    }

    public function testIsGettingTreeOutput()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');
        $files = $repository->getTree('master')->output();

        foreach ($files as $file) {
            $this->assertEquals('blob', $file['type']);
            $this->assertRegExp('/test_file[0-9]*.txt/', $file['name']);
            $this->assertEquals($file['size'], '55');
            $this->assertEquals($file['mode'], '100644');
            $this->assertRegExp('/[a-f0-9]+/', $file['hash']);
        }
    }

    public function testIsGettingTreesWithinTree()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');

        // Creating folders
        mkdir(self::$tmpdir . '/testrepo/MyFolder');
        mkdir(self::$tmpdir . '/testrepo/MyTest');
        mkdir(self::$tmpdir . '/testrepo/MyFolder/Tests');

        // Populating created folders
        file_put_contents(self::$tmpdir . '/testrepo/MyFolder/crazy.php', 'Lorem ipsum dolor sit amet');
        file_put_contents(self::$tmpdir . '/testrepo/MyFolder/skywalker.php', 'Lorem ipsum dolor sit amet');
        file_put_contents(self::$tmpdir . '/testrepo/MyTest/fortytwo.php', 'Lorem ipsum dolor sit amet');
        file_put_contents(self::$tmpdir . '/testrepo/MyFolder/Tests/web.php', 'Lorem ipsum dolor sit amet');
        file_put_contents(self::$tmpdir . '/testrepo/MyFolder/Tests/cli.php', 'Lorem ipsum dolor sit amet');

        // Adding and commiting
        $repository->addAll();
        $repository->commit("Creating folders for testIsGettingTreesWithinTrees");

        // Checking tree
        $files = $repository->getTree('master')->output();

        $this->assertEquals('folder', $files[0]['type']);
        $this->assertEquals('MyFolder', $files[0]['name']);
        $this->assertEquals('', $files[0]['size']);
        $this->assertEquals('040000', $files[0]['mode']);
        $this->assertEquals('4143e982237f3bdf56b5350f862c334054aad69e', $files[0]['hash']);

        $this->assertEquals('folder', $files[1]['type']);
        $this->assertEquals('MyTest', $files[1]['name']);
        $this->assertEquals('', $files[1]['size']);
        $this->assertEquals('040000', $files[1]['mode']);
        $this->assertEquals('632240595eabd59e4217d196d6c12efb81f9c011', $files[1]['hash']);

        $this->assertEquals('blob', $files[2]['type']);
        $this->assertEquals('test_file.txt', $files[2]['name']);
        $this->assertEquals('55', $files[2]['size']);
        $this->assertEquals('100644', $files[2]['mode']);
        $this->assertEquals('a773bfc0fda6f878e3d17d78c667d18297c8831f', $files[2]['hash']);
    }

    public function testIsGettingBlobsWithinTrees()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');
        $files = $repository->getTree('master:MyFolder/')->output();

        $this->assertEquals('folder', $files[0]['type']);
        $this->assertEquals('Tests', $files[0]['name']);
        $this->assertEquals('', $files[0]['size']);
        $this->assertEquals('040000', $files[0]['mode']);
        $this->assertEquals('8542f67d011ff2ea5ec49a729ba81a52935676d1', $files[0]['hash']);

        $this->assertEquals('blob', $files[1]['type']);
        $this->assertEquals('crazy.php', $files[1]['name']);
        $this->assertEquals('26', $files[1]['size']);
        $this->assertEquals('100644', $files[1]['mode']);
        $this->assertEquals('d781006b2d05cc31751954a0fb920c990e825aad', $files[1]['hash']);

        $this->assertEquals('blob', $files[2]['type']);
        $this->assertEquals('skywalker.php', $files[2]['name']);
        $this->assertEquals('26', $files[2]['size']);
        $this->assertEquals('100644', $files[2]['mode']);
        $this->assertEquals('d781006b2d05cc31751954a0fb920c990e825aad', $files[2]['hash']);
    }

    public function testIsGettingBlobOutput()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');
        $blob = $repository->getBlob('master:MyFolder/crazy.php')->output();
        $this->assertEquals('Lorem ipsum dolor sit amet', $blob);

        $blob = $repository->getBlob('master:test_file4.txt')->output();
        $this->assertEquals('Your mother is so ugly, glCullFace always returns TRUE.', $blob);
    }

    public function testIsGettingSymlinksWithinTrees()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestSkipped('Unable to run on Windows');
        }

        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');
        $fs = new Filesystem();
        $fs->touch(self::$tmpdir . '/testrepo/original_file.txt');
        $fs->symlink(self::$tmpdir . '/testrepo/original_file.txt', self::$tmpdir . '/testrepo/link.txt');
        $repository->addAll();
        $repository->commit("Testing symlinks");
        $files = $repository->getTree('master');

        foreach ($files as $file) {
            if ($file instanceof Gitter\Model\Symlink) {
                $this->assertEquals($file->getPath(), self::$tmpdir . '/testrepo/original_file.txt');
                $this->assertEquals($file->getName(), 'link.txt');
                $this->assertEquals($file->getMode(), '120000');
                return;
            }
        }

        $this->fail('No symlink found inside tree');
    }

    public function testIsGettingSymlinksWithinTreesOutput()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestSkipped('Unable to run on Windows');
        }

        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');
        $fs = new Filesystem();
        $fs->touch(self::$tmpdir . '/testrepo/original_file.txt');
        $fs->symlink(self::$tmpdir . '/testrepo/original_file.txt', self::$tmpdir . '/testrepo/link2.txt');
        $repository->addAll();
        $repository->commit("Testing symlinks");
        $files = $repository->getTree('master')->output();

        foreach ($files as $file) {
            if ($file['type'] == 'symlink') {
                $this->assertEquals($file['path'], self::$tmpdir . '/testrepo/original_file.txt');
                $this->assertEquals($file['name'], 'link.txt');
                $this->assertEquals($file['mode'], '120000');
                return;
            }
        }

        $this->fail('No symlink found inside tree output');
    }

    public function testIsGettingTotalCommits()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');
        $this->assertEquals($repository->getTotalCommits(), '4');
    }

    public function testIsGettingCommit()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');
        $commits = $repository->getCommits();

        foreach ($commits as $commit) {
            $singleCommit = $repository->getCommit($commit->getHash());
            $this->assertInstanceOf('Gitter\Model\Commit\Commit', $singleCommit);
            $this->assertInstanceOf('Gitter\Model\Commit\Author', $singleCommit->getAuthor());
            $this->assertEquals($singleCommit->getAuthor()->getName(), 'Luke Skywalker');
            $this->assertEquals($singleCommit->getAuthor()->getEmail(), 'luke@rebel.org');
            $this->assertEquals($singleCommit->getCommiter()->getName(), 'Luke Skywalker');
            $this->assertEquals($singleCommit->getCommiter()->getEmail(), 'luke@rebel.org');
            $this->assertInstanceOf('DateTime', $singleCommit->getDate());
            $this->assertInstanceOf('DateTime', $singleCommit->getCommiterDate());
            $this->assertRegExp('/[a-f0-9]+/', $singleCommit->getHash());
            $this->assertRegExp('/[a-f0-9]+/', $singleCommit->getShortHash());
            $this->assertRegExp('/[a-f0-9]+/', $singleCommit->getTreeHash());
        }
    }

    public function testIsGettingCurrentHead()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');
        $this->assertEquals($repository->getHead(), 'master');
    }

    public function testIsGettingBranchTree()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');
        $this->assertRegExp('/[a-f0-9]+/', $repository->getBranchTree('issue12'));
    }

    public function testIsGettingBlame()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');
        $blame = $repository->getBlame('test_file4.txt');
        $this->assertEquals($blame[1]['line'], PHP_EOL . ' Your mother is so ugly, glCullFace always returns TRUE.');
        $this->assertEquals($repository->getBlame('original_file.txt'), array());
    }

    public static function tearDownAfterClass()
    {
        $fs = new Filesystem();
        $fs->remove(self::$tmpdir);
    }
}
