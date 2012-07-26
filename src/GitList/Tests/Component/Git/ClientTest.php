<?php

namespace GitList\Tests\Component\Git;

use GitList\Component\Git\Client;
use GitList\Component\Git\Repository;

use Symfony\Component\Filesystem\Filesystem;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    public static $path;
    protected $client;

    public static function setUpBeforeClass()
    {
        self::$path = sprintf('%s/gitlist/%s', sys_get_temp_dir(), time());

        $fs = new Filesystem();
        try {
            $fs->mkdir(self::$path);
        } catch (IOException $e) {
            $this->markTestSkipped('There are no write permissions in order to create test repositories.');
        }
    }

    public function setUp()
    {
        if (!is_writable(self::$path)) {
            $this->markTestSkipped('There are no write permissions in order to create test repositories.');
        }

        $options['path'] = getenv('GIT_CLIENT') ?: '/usr/bin/git';
        $options['hidden'] = array(self::$path . '/hiddenrepo');
        $this->client = new Client($options);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIsNotFindingRepositories()
    {
        $this->client->getRepositories(self::$path . '/testrepo');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIsNotAbleToGetUnexistingRepository()
    {
        $this->client->getRepository(self::$path . '/testrepo');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIsNotAbleToGetUnexistingRepositories()
    {
        $this->client->getRepositories('/tmp');
    }

    public function testIsCreatingRepository()
    {
        $repository = $this->client->createRepository(self::$path . '/testrepo');
        $this->assertRegExp("/nothing to commit/", $repository->getClient()->run($repository, 'status'));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIsNotAbleToCreateRepositoryDueToExistingOne()
    {
        $this->client->createRepository(self::$path . '/testrepo');
    }

    public function testIsListingRepositories()
    {
        $this->client->createRepository(self::$path . '/anothertestrepo');
        $this->client->createRepository(self::$path . '/bigbadrepo');
        $repositories = $this->client->getRepositories(self::$path);

        $this->assertEquals($repositories[0]['name'], 'anothertestrepo');
        $this->assertEquals($repositories[1]['name'], 'bigbadrepo');
        $this->assertEquals($repositories[2]['name'], 'testrepo');
    }

    public function testIsNotListingHiddenRepositories()
    {
        $this->client->createRepository(self::$path . '/hiddenrepo');
        $repositories = $this->client->getRepositories(self::$path);

        $this->assertEquals($repositories[0]['name'], 'anothertestrepo');
        $this->assertEquals($repositories[1]['name'], 'bigbadrepo');
        $this->assertEquals($repositories[2]['name'], 'testrepo');
        $this->assertFalse(isset($repositories[3]));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIsNotOpeningHiddenRepositories()
    {
        $this->client->getRepository(self::$path . '/hiddenrepo');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIsCatchingGitCommandErrors()
    {
        $repository = $this->client->getRepository(self::$path . '/testrepo');
        $repository->getClient()->run($repository, 'wrong');
    }

    public static function tearDownAfterClass()
    {
        $fs = new Filesystem();
        $fs->remove(self::$path);
    }
}
