<?php

require 'vendor/autoload.php';

use GitList\Component\Git\Client;
use GitList\Component\Git\Repository;
use Symfony\Component\Filesystem\Filesystem;

class RepositoryTest extends PHPUnit_Framework_TestCase
{
    const PATH = '/tmp/gitlist';
    protected $client;

    public static function setUpBeforeClass()
    {
        mkdir(RepositoryTest::PATH);
    }

    public function setUp()
    {
        if (!is_writable(RepositoryTest::PATH)) {
            $this->markTestSkipped('There are no write permissions in order to create test repositories.');
        }

        $options['path'] = getenv('GIT_CLIENT') ?: '/usr/bin/git';
        $options['hidden'] = array();
        $this->client = new Client($options);
    }

    public function testIsCreatingRepositoryFixtures()
    {
        $a = $this->client->createRepository(RepositoryTest::PATH . '/testrepo');
        $b = $this->client->createRepository(RepositoryTest::PATH . '/anothertestrepo');
        $c = $this->client->createRepository(RepositoryTest::PATH . '/bigbadrepo');
        $this->assertRegExp("/nothing to commit/", $a->getClient()->run($a, 'status'));
        $this->assertRegExp("/nothing to commit/", $b->getClient()->run($b, 'status'));
        $this->assertRegExp("/nothing to commit/", $c->getClient()->run($c, 'status'));
    }

    public function testIsConfiguratingRepository()
    {
        $repository = $this->client->getRepository(RepositoryTest::PATH . '/testrepo');
        $repository->setConfig('user.name', 'Luke Skywalker');
        $repository->setConfig('user.email', 'luke@rebel.org');

        $this->assertEquals($repository->getConfig('user.name'), 'Luke Skywalker');
        $this->assertEquals($repository->getConfig('user.email'), 'luke@rebel.org');
    }
    
    public function testIsAdding()
    {
        $repository = $this->client->getRepository(RepositoryTest::PATH . '/testrepo');
        file_put_contents(RepositoryTest::PATH . '/testrepo/test_file.txt', 'Your mother is so ugly, glCullFace always returns TRUE.');
        $repository->add('test_file.txt');
        $this->assertRegExp("/new file:   test_file.txt/", $repository->getClient()->run($repository, 'status'));
    }

    /**
     * @depends testIsAdding
     */
    public function testIsAddingDot()
    {
        $repository = $this->client->getRepository(RepositoryTest::PATH . '/testrepo');

        file_put_contents(RepositoryTest::PATH . '/testrepo/test_file1.txt', 'Your mother is so ugly, glCullFace always returns TRUE.');
        file_put_contents(RepositoryTest::PATH . '/testrepo/test_file2.txt', 'Your mother is so ugly, glCullFace always returns TRUE.');
        file_put_contents(RepositoryTest::PATH . '/testrepo/test_file3.txt', 'Your mother is so ugly, glCullFace always returns TRUE.');

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
        $repository = $this->client->getRepository(RepositoryTest::PATH . '/testrepo');

        file_put_contents(RepositoryTest::PATH . '/testrepo/test_file4.txt', 'Your mother is so ugly, glCullFace always returns TRUE.');
        file_put_contents(RepositoryTest::PATH . '/testrepo/test_file5.txt', 'Your mother is so ugly, glCullFace always returns TRUE.');
        file_put_contents(RepositoryTest::PATH . '/testrepo/test_file6.txt', 'Your mother is so ugly, glCullFace always returns TRUE.');

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
        $repository = $this->client->getRepository(RepositoryTest::PATH . '/testrepo');

        file_put_contents(RepositoryTest::PATH . '/testrepo/test_file7.txt', 'Your mother is so ugly, glCullFace always returns TRUE.');
        file_put_contents(RepositoryTest::PATH . '/testrepo/test_file8.txt', 'Your mother is so ugly, glCullFace always returns TRUE.');
        file_put_contents(RepositoryTest::PATH . '/testrepo/test_file9.txt', 'Your mother is so ugly, glCullFace always returns TRUE.');

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
        $repository = $this->client->getRepository(RepositoryTest::PATH . '/testrepo');
        $repository->commit("The truth unveiled");
        $this->assertRegExp("/The truth unveiled/", $repository->getClient()->run($repository, 'log'));
    }

    public function testIsCreatingBranches()
    {
        $repository = $this->client->getRepository(RepositoryTest::PATH . '/testrepo');
        $repository->createBranch('issue12');
        $repository->createBranch('issue42');
        $branches = $repository->getBranches();
        $this->assertContains('issue12', $branches);
        $this->assertContains('issue42', $branches);
        $this->assertContains('master', $branches);
    }

    public function testIsGettingCurrentBranch()
    {
        $repository = $this->client->getRepository(RepositoryTest::PATH . '/testrepo');
        $branch = $repository->getCurrentBranch();
        $this->assertTrue($branch === 'master');
    }

    /**
     * @depends testIsCommiting
     */
    public function testIsGettingCommits()
    {
        $repository = $this->client->getRepository(RepositoryTest::PATH . '/testrepo');
        $commits = $repository->getCommits();
        
        foreach ($commits as $commit) {
            $this->assertInstanceOf('GitList\Component\Git\Commit\Commit', $commit);
            $this->assertTrue($commit->getMessage() === 'The truth unveiled');
            $this->assertInstanceOf('GitList\Component\Git\Commit\Author', $commit->getAuthor());
            $this->assertEquals($commit->getAuthor()->getName(), 'Luke Skywalker');
            $this->assertEquals($commit->getAuthor()->getEmail(), 'luke@rebel.org');
            $this->assertEquals($commit->getCommiter()->getName(), 'Luke Skywalker');
            $this->assertEquals($commit->getCommiter()->getEmail(), 'luke@rebel.org');
            $this->assertEquals($commit->getParentHash(), '');
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
        $repository = $this->client->getRepository(RepositoryTest::PATH . '/testrepo');
        $commits = $repository->getCommits('test_file4.txt');
        
        foreach ($commits as $commit) {
            $this->assertInstanceOf('GitList\Component\Git\Commit\Commit', $commit);
            $this->assertTrue($commit->getMessage() === 'The truth unveiled');
            $this->assertInstanceOf('GitList\Component\Git\Commit\Author', $commit->getAuthor());
            $this->assertEquals($commit->getAuthor()->getName(), 'Luke Skywalker');
            $this->assertEquals($commit->getAuthor()->getEmail(), 'luke@rebel.org');
        }
    }

    public function testIsGettingTree()
    {
        $repository = $this->client->getRepository(RepositoryTest::PATH . '/testrepo');
        $files = $repository->getTree('master');
        
        foreach ($files as $file) {
            $this->assertInstanceOf('GitList\Component\Git\Model\Blob', $file);
            $this->assertRegExp('/test_file[0-9]*.txt/', $file->getName());
            $this->assertEquals($file->getSize(), '55');
            $this->assertEquals($file->getMode(), '100644');
            $this->assertRegExp('/[a-f0-9]+/', $file->getHash());
        }
    }

    public function testIsGettingTreeOutput()
    {
        $repository = $this->client->getRepository(RepositoryTest::PATH . '/testrepo');
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
        $repository = $this->client->getRepository(RepositoryTest::PATH . '/testrepo');

        // Creating folders
        mkdir(RepositoryTest::PATH . '/testrepo/MyFolder');
        mkdir(RepositoryTest::PATH . '/testrepo/MyTest');
        mkdir(RepositoryTest::PATH . '/testrepo/MyFolder/Tests');

        // Populating created folders
        file_put_contents(RepositoryTest::PATH . '/testrepo/MyFolder/crazy.php', 'Lorem ipsum dolor sit amet');
        file_put_contents(RepositoryTest::PATH . '/testrepo/MyFolder/skywalker.php', 'Lorem ipsum dolor sit amet');
        file_put_contents(RepositoryTest::PATH . '/testrepo/MyTest/fortytwo.php', 'Lorem ipsum dolor sit amet');
        file_put_contents(RepositoryTest::PATH . '/testrepo/MyFolder/Tests/web.php', 'Lorem ipsum dolor sit amet');
        file_put_contents(RepositoryTest::PATH . '/testrepo/MyFolder/Tests/cli.php', 'Lorem ipsum dolor sit amet');

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
        $repository = $this->client->getRepository(RepositoryTest::PATH . '/testrepo');
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
        $repository = $this->client->getRepository(RepositoryTest::PATH . '/testrepo');
        $blob = $repository->getBlob('master:MyFolder/crazy.php')->output();
        $this->assertEquals('Lorem ipsum dolor sit amet', $blob);

        $blob = $repository->getBlob('master:test_file4.txt')->output();
        $this->assertEquals('Your mother is so ugly, glCullFace always returns TRUE.', $blob);
    }

    public function testIsGettingStatistics()
    {
        $repository = $this->client->getRepository(RepositoryTest::PATH . '/testrepo');
        $stats = $repository->getStatistics('master');

        $this->assertEquals('10', $stats['extensions']['.txt']);
        $this->assertEquals('5', $stats['extensions']['.php']);
        $this->assertEquals('680', $stats['size']);
        $this->assertEquals('15', $stats['files']);
    }

    public function testIsGettingAuthorStatistics()
    {
        $repository = $this->client->getRepository(RepositoryTest::PATH . '/testrepo');
        $stats = $repository->getAuthorStatistics();

        $this->assertEquals('Luke Skywalker', $stats[0]['name']);
        $this->assertEquals('luke@rebel.org', $stats[0]['email']);
        $this->assertEquals('2', $stats[0]['commits']);

        $repository->setConfig('user.name', 'Princess Leia');
        $repository->setConfig('user.email', 'sexyleia@republic.com');
        file_put_contents(RepositoryTest::PATH . '/testrepo/MyFolder/crazy.php', 'Lorem ipsum dolor sit AMET');
        $repository->addAll();
        $repository->commit("Fixing AMET case");

        $stats = $repository->getAuthorStatistics();

        $this->assertEquals('Luke Skywalker', $stats[0]['name']);
        $this->assertEquals('luke@rebel.org', $stats[0]['email']);
        $this->assertEquals('2', $stats[0]['commits']);

        $this->assertEquals('Princess Leia', $stats[1]['name']);
        $this->assertEquals('sexyleia@republic.com', $stats[1]['email']);
        $this->assertEquals('1', $stats[1]['commits']);
    }

    public function testIsGettingSymlinksWithinTrees()
    {
        $repository = $this->client->getRepository(RepositoryTest::PATH . '/testrepo');
        $fs = new Filesystem();
        $fs->touch(RepositoryTest::PATH . '/testrepo/original_file.txt');
        $fs->symlink(RepositoryTest::PATH . '/testrepo/original_file.txt', RepositoryTest::PATH . '/testrepo/link.txt');
        $repository->addAll();
        $repository->commit("Testing symlinks");
        $files = $repository->getTree('master');

        foreach ($files as $file) {
            if ($file instanceof GitList\Component\Git\Model\Symlink) {
                $this->assertEquals($file->getPath(), RepositoryTest::PATH . '/testrepo/original_file.txt');
                $this->assertEquals($file->getName(), 'link.txt');
                $this->assertEquals($file->getMode(), '120000');
                return;
            }
        }

        $this->fail('No symlink found inside tree');
    }

    public function testIsGettingSymlinksWithinTreesOutput()
    {
        $repository = $this->client->getRepository(RepositoryTest::PATH . '/testrepo');
        $fs = new Filesystem();
        $fs->touch(RepositoryTest::PATH . '/testrepo/original_file.txt');
        $fs->symlink(RepositoryTest::PATH . '/testrepo/original_file.txt', RepositoryTest::PATH . '/testrepo/link2.txt');
        $repository->addAll();
        $repository->commit("Testing symlinks");
        $files = $repository->getTree('master')->output();

        foreach ($files as $file) {
            if ($file['type'] == 'symlink') {
                $this->assertEquals($file['path'], RepositoryTest::PATH . '/testrepo/original_file.txt');
                $this->assertEquals($file['name'], 'link.txt');
                $this->assertEquals($file['mode'], '120000');
                return;
            }
        }

        $this->fail('No symlink found inside tree output');
    }

    public static function tearDownAfterClass()
    {
        $fs = new Filesystem();
        $fs->remove(RepositoryTest::PATH);
    }
}
