<?php

namespace Gitter\Tests;

use Gitter\Client;
use Gitter\Repository;
use Symfony\Component\Filesystem\Filesystem;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    public static $tmpdir;
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
            'hidden' => array(self::$tmpdir . '/hiddenrepo'),
        );
        $this->client = new Client($options);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIsNotFindingRepositories()
    {
        $this->client->getRepositories(self::$tmpdir . '/testrepo');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIsNotAbleToGetUnexistingRepository()
    {
        $this->client->getRepository(self::$tmpdir . '/testrepo');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIsNotAbleToGetUnexistingRepositories()
    {
        $this->client->getRepositories(self::$tmpdir);
    }

    public function testIsCreatingRepository()
    {
        $repository = $this->client->createRepository(self::$tmpdir . '/testrepo');
        $fs = new Filesystem();
        $fs->remove(self::$tmpdir . '/testrepo/.git/description');
        $this->assertRegExp("/nothing to commit/", $repository->getClient()->run($repository, 'status'));
    }

    public function testIsCreatingBareRepository()
    {
        $repository = $this->client->createRepository(self::$tmpdir . '/testbare', true);
        $this->assertInstanceOf('Gitter\Repository', $repository);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIsNotAbleToCreateRepositoryDueToExistingOne()
    {
        $this->client->createRepository(self::$tmpdir . '/testrepo');
    }

    public function testIsListingRepositories()
    {
        $this->client->createRepository(self::$tmpdir . '/anothertestrepo');
        $this->client->createRepository(self::$tmpdir . '/bigbadrepo');
        $repositories = $this->client->getRepositories(self::$tmpdir);

        $this->assertEquals($repositories[0]['name'], 'anothertestrepo');
        $this->assertEquals($repositories[1]['name'], 'bigbadrepo');
        $this->assertEquals($repositories[2]['name'], 'testbare');
        $this->assertEquals($repositories[3]['name'], 'testrepo');
    }

    public function testIsNotListingHiddenRepositories()
    {
        $this->client->createRepository(self::$tmpdir . '/hiddenrepo');
        $repositories = $this->client->getRepositories(self::$tmpdir);

        $this->assertEquals($repositories[0]['name'], 'anothertestrepo');
        $this->assertEquals($repositories[1]['name'], 'bigbadrepo');
        $this->assertEquals($repositories[2]['name'], 'testbare');
        $this->assertEquals($repositories[3]['name'], 'testrepo');
        $this->assertFalse(isset($repositories[4]));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIsNotOpeningHiddenRepositories()
    {
        $this->client->getRepository(self::$tmpdir . '/hiddenrepo');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIsCatchingGitCommandErrors()
    {
        $repository = $this->client->getRepository(self::$tmpdir . '/testrepo');
        $repository->getClient()->run($repository, 'wrong');
    }

    public static function tearDownAfterClass()
    {
        $fs = new Filesystem();
        $fs->remove(self::$tmpdir);
    }
}
