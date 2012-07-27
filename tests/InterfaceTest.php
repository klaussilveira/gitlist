<?php

require 'vendor/autoload.php';

use Silex\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use GitList\Component\Git\Client;
use GitList\Application;

class InterfaceTest extends WebTestCase
{
    const PATH = '/tmp/gitlist/';

    public static function setUpBeforeClass()
    {
        $fs = new Filesystem();
        $fs->mkdir(InterfaceTest::PATH);

        if (!is_writable(InterfaceTest::PATH)) {
            $this->markTestSkipped('There are no write permissions in order to create test repositories.');
        }

        $options['path'] = getenv('GIT_CLIENT') ?: '/usr/bin/git';
        $options['hidden'] = array(InterfaceTest::PATH . '/hiddenrepo');
        $git = new Client($options);

        // GitTest repository fixture
        $git->createRepository(InterfaceTest::PATH . 'GitTest');
        $repository = $git->getRepository(InterfaceTest::PATH . 'GitTest');
        file_put_contents(InterfaceTest::PATH . 'GitTest/README.md', "## GitTest\nGitTest is a *test* repository!");
        file_put_contents(InterfaceTest::PATH . 'GitTest/test.php', "<?php\necho 'Hello World'; // This is a test");
        $repository->addAll();
        $repository->commit("Initial commit");
        $repository->createBranch('issue12');
        $repository->createBranch('issue42');

        // foobar repository fixture
        $git->createRepository(InterfaceTest::PATH . 'foobar');
        $repository = $git->getRepository(InterfaceTest::PATH . '/foobar');
        file_put_contents(InterfaceTest::PATH . 'foobar/bar.json', "{\n\"name\": \"foobar\"\n}");
        file_put_contents(InterfaceTest::PATH . 'foobar/.git/description', 'This is a test repo!');
        $fs->mkdir(InterfaceTest::PATH . 'foobar/myfolder');
        $fs->mkdir(InterfaceTest::PATH . 'foobar/testfolder');
        file_put_contents(InterfaceTest::PATH . 'foobar/myfolder/mytest.php', "<?php\necho 'Hello World'; // This is my test");
        file_put_contents(InterfaceTest::PATH . 'foobar/testfolder/test.php', "<?php\necho 'Hello World'; // This is a test");
        $repository->addAll();
        $repository->commit("First commit");
    }

    public function createApplication()
    {
        $app = new Application(__DIR__ . '/../config/test.php');
        require __DIR__.'/../src/controllers.php';

        $app['debug'] = true;
        $app['git.repos'] = InterfaceTest::PATH;
        return $app;
    }

    public function testInitialPage()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/');

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('title:contains("GitList")'));
        $this->assertCount(1, $crawler->filter('div.repository-header:contains("GitTest")'));
        $this->assertEquals('/GitTest/', $crawler->filter('.repository-header a')->eq(0)->attr('href'));
        $this->assertEquals('/GitTest/master/rss/', $crawler->filter('.repository-header a')->eq(1)->attr('href'));
        $this->assertCount(1, $crawler->filter('div.repository-header:contains("foobar")'));
        $this->assertCount(1, $crawler->filter('div.repository-body:contains("This is a test repo!")'));
        $this->assertEquals('/foobar/', $crawler->filter('.repository-header a')->eq(2)->attr('href'));
        $this->assertEquals('/foobar/master/rss/', $crawler->filter('.repository-header a')->eq(3)->attr('href'));
    }

    public function testRepositoryPage()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/GitTest/');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('.tree tr:contains("README.md")'));
        $this->assertCount(1, $crawler->filter('.tree tr:contains("test.php")'));
        $this->assertCount(1, $crawler->filter('.readme-header:contains("README.md")'));
        $this->assertEquals("## GitTest\nGitTest is a *test* repository!", $crawler->filter('#readme-content')->eq(0)->text());
        $this->assertEquals('/GitTest/blob/master/README.md', $crawler->filter('.tree tr td')->eq(0)->filter('a')->eq(0)->attr('href'));
        $this->assertEquals('/GitTest/blob/master/test.php', $crawler->filter('.tree tr td')->eq(3)->filter('a')->eq(0)->attr('href'));
        $this->assertEquals('issue12', $crawler->filter('.dropdown-menu li')->eq(1)->text());
        $this->assertEquals('issue42', $crawler->filter('.dropdown-menu li')->eq(2)->text());
        $this->assertEquals('master', $crawler->filter('.dropdown-menu li')->eq(3)->text());

        $crawler = $client->request('GET', '/foobar/');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('.tree tr:contains("myfolder")'));
        $this->assertCount(1, $crawler->filter('.tree tr:contains("testfolder")'));
        $this->assertCount(1, $crawler->filter('.tree tr:contains("bar.json")'));
        $this->assertEquals('/foobar/tree/master/myfolder/', $crawler->filter('.tree tr td')->eq(0)->filter('a')->eq(0)->attr('href'));
        $this->assertEquals('/foobar/tree/master/testfolder/', $crawler->filter('.tree tr td')->eq(3)->filter('a')->eq(0)->attr('href'));
        $this->assertEquals('/foobar/blob/master/bar.json', $crawler->filter('.tree tr td')->eq(6)->filter('a')->eq(0)->attr('href'));
        $this->assertCount(0, $crawler->filter('.readme-header'));
        $this->assertEquals('master', $crawler->filter('.dropdown-menu li')->eq(1)->text());
    }

    public static function tearDownAfterClass()
    {
        $fs = new Filesystem();
        $fs->remove(InterfaceTest::PATH);
    }
}
