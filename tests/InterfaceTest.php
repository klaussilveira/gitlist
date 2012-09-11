<?php

use Silex\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Gitter\Client;

class InterfaceTest extends WebTestCase
{
    protected static $tmpdir;

    public static function setUpBeforeClass()
    {
        if (getenv('TMP')) {
            self::$tmpdir = getenv('TMP');
        } elseif (getenv('TMPDIR')) {
            self::$tmpdir = getenv('TMPDIR');
        } else {
           self::$tmpdir = '/tmp';
        }

        self::$tmpdir .= '/gitlist_' . md5(time() . mt_rand()) . '/';

        $fs = new Filesystem();
        $fs->mkdir(self::$tmpdir);

        if (!is_writable(self::$tmpdir)) {
            $this->markTestSkipped('There are no write permissions in order to create test repositories.');
        }

        $options['path'] = getenv('GIT_CLIENT') ?: '/usr/bin/git';
        $options['hidden'] = array(self::$tmpdir . '/hiddenrepo');
        $git = new Client($options);

        // GitTest repository fixture
        $git->createRepository(self::$tmpdir . 'GitTest');
        $repository = $git->getRepository(self::$tmpdir . 'GitTest');
        file_put_contents(self::$tmpdir . 'GitTest/README.md', "## GitTest\nGitTest is a *test* repository!");
        file_put_contents(self::$tmpdir . 'GitTest/test.php', "<?php\necho 'Hello World'; // This is a test");
        $repository->setConfig('user.name', 'Luke Skywalker');
        $repository->setConfig('user.email', 'luke@rebel.org');
        $repository->addAll();
        $repository->commit("Initial commit");
        $repository->createBranch('issue12');
        $repository->createBranch('issue42');

        // foobar repository fixture
        $git->createRepository(self::$tmpdir . 'foobar');
        $repository = $git->getRepository(self::$tmpdir . '/foobar');
        file_put_contents(self::$tmpdir . 'foobar/bar.json', "{\n\"name\": \"foobar\"\n}");
        file_put_contents(self::$tmpdir . 'foobar/.git/description', 'This is a test repo!');
        $fs->mkdir(self::$tmpdir . 'foobar/myfolder');
        $fs->mkdir(self::$tmpdir . 'foobar/testfolder');
        file_put_contents(self::$tmpdir . 'foobar/myfolder/mytest.php', "<?php\necho 'Hello World'; // This is my test");
        file_put_contents(self::$tmpdir . 'foobar/testfolder/test.php', "<?php\necho 'Hello World'; // This is a test");
        $repository->setConfig('user.name', 'Luke Skywalker');
        $repository->setConfig('user.email', 'luke@rebel.org');
        $repository->addAll();
        $repository->commit("First commit");
    }

    public function createApplication()
    {
        $app = require 'boot.php';
        $app['debug'] = true;
        $app['git.repos'] = self::$tmpdir;
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

    public function testBlobPage()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/GitTest/blob/master/test.php');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('.breadcrumb .active:contains("test.php")'));
        $this->assertEquals('/GitTest/raw/master/test.php', $crawler->filter('.source-header .btn-group a')->eq(0)->attr('href'));
        $this->assertEquals('/GitTest/blame/master/test.php', $crawler->filter('.source-header .btn-group a')->eq(1)->attr('href'));
        $this->assertEquals('/GitTest/commits/master/test.php', $crawler->filter('.source-header .btn-group a')->eq(2)->attr('href'));
    }

    public function testRawPage()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/GitTest/raw/master/test.php');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals("<?php\necho 'Hello World'; // This is a test", $client->getResponse()->getContent());
    }

    public function testBlamePage()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/GitTest/blame/master/test.php');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('.source-header .meta:contains("test.php")'));
        $this->assertRegexp('/\/GitTest\/commit\/[a-zA-Z0-9%]+\//', $crawler->filter('.blame-view .commit')->eq(0)->filter('a')->attr('href'));

        $crawler = $client->request('GET', '/foobar/blame/master/bar.json');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('.source-header .meta:contains("bar.json")'));
        $this->assertRegexp('/\/foobar\/commit\/[a-zA-Z0-9%]+\//', $crawler->filter('.blame-view .commit')->eq(0)->filter('a')->attr('href'));
    }

    public function testHistoryPage()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/GitTest/commits/master/test.php');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('Initial commit', $crawler->filter('.table tbody tr td h4')->eq(0)->text());

        $crawler = $client->request('GET', '/GitTest/commits/master/README.md');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('Initial commit', $crawler->filter('.table tbody tr td h4')->eq(0)->text());

        $crawler = $client->request('GET', '/foobar/commits/master/bar.json');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('First commit', $crawler->filter('.table tbody tr td h4')->eq(0)->text());
    }

    public function testCommitsPage()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/GitTest/commits');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('Initial commit', $crawler->filter('.table tbody tr td h4')->eq(0)->text());

        $crawler = $client->request('GET', '/foobar/commits');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('First commit', $crawler->filter('.table tbody tr td h4')->eq(0)->text());
    }

    public function testStatsPage()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/GitTest/stats');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertRegexp('/.php: 1 files/', $crawler->filter('.table tbody')->eq(0)->text());
        $this->assertRegexp('/.md: 1 files/', $crawler->filter('.table tbody')->eq(0)->text());
        $this->assertRegexp('/Total files: 2/', $crawler->filter('.table tbody')->eq(0)->text());
        $this->assertRegexp('/Luke Skywalker: 1 commits/', $crawler->filter('.table tbody')->eq(0)->text());
    }

    public function testRssPage()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/GitTest/master/rss/');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertRegexp('/Latest commits in GitTest:master/', $client->getResponse()->getContent());
        $this->assertRegexp('/Initial commit/', $client->getResponse()->getContent());
    }

    public static function tearDownAfterClass()
    {
        $fs = new Filesystem();
        $fs->remove(self::$tmpdir);
    }
}
