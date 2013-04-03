<?php

namespace Gitter\Tests;

use Gitter\Client;
use Gitter\Repository;
use Symfony\Component\Filesystem\Filesystem;
use GitterTestCase;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    public static $tmpdir;
    protected static $cached_repos;
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

        $cached_dir = self::$tmpdir . DIRECTORY_SEPARATOR . 'cache';
        $fs->mkdir($cached_dir);
        self::$cached_repos = $cached_dir . DIRECTORY_SEPARATOR . 'repos.json';
    }

    public function setUp()
    {
        if (!is_writable(self::$tmpdir)) {
            $this->markTestSkipped('There are no write permissions in order to create test repositories.');
        }

        $options = array(
            'path' => getenv('GIT_CLIENT') ?: null,
            'hidden' => array(self::$tmpdir . '/hiddenrepo'),
            'ini.file' => 'config.ini',
            'cache.repos' =>  self::$cached_repos
        );
        $this->client = new Client($options);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIsNotFindingRepositories()
    {
        $this->client->getRepositories(self::$tmpdir, 'testrepo');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIsNotAbleToGetUnexistingRepository()
    {
        $this->client->getRepositoryCached(self::$tmpdir, 'testrepo');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIsNotAbleToGetUnexistingRepositories()
    {
        $this->client->getRepositories(self::$tmpdir);
    }

    public function testIsParsingGitVersion()
    {
        $version = $this->client->getVersion();
        $this->assertNotEmpty($version);
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

        $this->assertEquals($repositories['anothertestrepo']['name'], 'anothertestrepo');
        $this->assertEquals($repositories['bigbadrepo']['name'], 'bigbadrepo');
        $this->assertEquals($repositories['testbare']['name'], 'testbare');
        $this->assertEquals($repositories['testrepo']['name'], 'testrepo');
    }

    public function testIsNotListingHiddenRepositories()
    {
        $this->client->createRepository(self::$tmpdir . '/hiddenrepo');
        $repositories = $this->client->getRepositories(self::$tmpdir);

        $this->assertEquals($repositories['anothertestrepo']['name'], 'anothertestrepo');
        $this->assertEquals($repositories['bigbadrepo']['name'], 'bigbadrepo');
        $this->assertEquals($repositories['testbare']['name'], 'testbare');
        $this->assertEquals($repositories['testrepo']['name'], 'testrepo');

        $this->assertFalse(isset($repositories['hiddenrepo']));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIsNotOpeningHiddenRepositories()
    {

        $this->client->getRepositoryCached(self::$tmpdir, 'hiddenrepo');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIsCatchingGitCommandErrors()
    {
        $repository = $this->client->getRepositoryCached(self::$tmpdir, 'testrepo');
        $repository->getClient()->run($repository, 'wrong');
    }

    public static function tearDownAfterClass()
    {
        $fs = new Filesystem();
        $fs->remove(self::$tmpdir);
    }
}

