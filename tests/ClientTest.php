<?php

require 'vendor/autoload.php';

use GitList\Component\Git\Client;
use GitList\Component\Git\Repository;
use Symfony\Component\Filesystem\Filesystem;

class ClientTest extends PHPUnit_Framework_TestCase
{
    const PATH = '/tmp/gitlist';
    protected $client;

    public static function setUpBeforeClass()
    {
        mkdir(ClientTest::PATH);
    }

    public function setUp()
    {
        if (!is_writable(ClientTest::PATH)) {
            $this->markTestSkipped('There are no write permissions in order to create test repositories.');
        }

        $options['path'] = getenv('GIT_CLIENT') ?: '/usr/bin/git';
        $options['hidden'] = array(ClientTest::PATH . '/hiddenrepo');
        $this->client = new Client($options);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIsNotFindingRepositories()
    {
        $this->client->getRepositories(ClientTest::PATH . '/testrepo');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIsNotAbleToGetUnexistingRepository()
    {
        $this->client->getRepository(ClientTest::PATH . '/testrepo');
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
        $repository = $this->client->createRepository(ClientTest::PATH . '/testrepo');
        $this->assertRegExp("/nothing to commit/", $repository->getClient()->run($repository, 'status'));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIsNotAbleToCreateRepositoryDueToExistingOne()
    {
        $this->client->createRepository(ClientTest::PATH . '/testrepo');
    }

    public function testIsListingRepositories()
    {
        $this->client->createRepository(ClientTest::PATH . '/anothertestrepo');
        $this->client->createRepository(ClientTest::PATH . '/bigbadrepo');
        $repositories = $this->client->getRepositories(ClientTest::PATH);

        $this->assertEquals($repositories[0]['name'], 'anothertestrepo');
        $this->assertEquals($repositories[1]['name'], 'bigbadrepo');
        $this->assertEquals($repositories[2]['name'], 'testrepo');
    }

    public function testIsNotListingHiddenRepositories()
    {
        $this->client->createRepository(ClientTest::PATH . '/hiddenrepo');
        $repositories = $this->client->getRepositories(ClientTest::PATH);

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
        $this->client->getRepository(ClientTest::PATH . '/hiddenrepo');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIsCatchingGitCommandErrors()
    {
        $repository = $this->client->getRepository(ClientTest::PATH . '/testrepo');
        $repository->getClient()->run($repository, 'wrong');
    }

    public static function tearDownAfterClass()
    {
        $fs = new Filesystem();
        $fs->remove(ClientTest::PATH);
    }
}
