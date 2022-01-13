<?php

use GitList\Test\WebTestCase;
use GitList\Git\Client;
use Symfony\Component\Filesystem\Filesystem;

class InterfaceTest extends WebTestCase
{
    protected static $tmpdir;
    protected static $gitPath;

    public static function setUpBeforeClass(): void
    {
        if (sys_get_temp_dir()) {
            self::$tmpdir = sys_get_temp_dir();
        } elseif (getenv('TMP')) {
            self::$tmpdir = getenv('TMP');
        } elseif (getenv('TMPDIR')) {
            self::$tmpdir = getenv('TMPDIR');
        } else {
            self::$tmpdir = DIRECTORY_SEPARATOR . 'tmp';
        }

        self::$tmpdir .= DIRECTORY_SEPARATOR . 'gitlist_' . md5(time() . mt_rand()) . DIRECTORY_SEPARATOR;

        $fs = new Filesystem();
        $fs->mkdir(self::$tmpdir);

        if (!is_writable(self::$tmpdir)) {
            self::markTestSkipped('There are no write permissions in order to create test repositories.');
        }

        $options['path'] = getenv('GIT_CLIENT') ?: '/usr/bin/git';
        $options['hidden'] = array(self::$tmpdir . '/hiddenrepo');
        $options['default_branch'] = 'master';
        $options['ini.file'] = 'config.ini';
        $options['strip_dot_git'] = false;
        $options['projects'] = false;

        $cacheDir = self::$tmpdir . DIRECTORY_SEPARATOR . 'cache';
        $fs->mkdir($cacheDir);

        $git = new Client($options);

        self::$gitPath = $options['path'];

        // GitTest repository fixture
        $git->createRepository(self::$tmpdir . 'GitTest');
        $repository = $git->getRepository(self::$tmpdir . 'GitTest');
        file_put_contents(self::$tmpdir . 'GitTest/README.md', "## GitTest\nGitTest is a *test* repository!");
        file_put_contents(self::$tmpdir . 'GitTest/test.php', "<?php\necho 'Hello World'; // This is a test");
        $repository->setConfig('user.name', 'Luke Skywalker');
        $repository->setConfig('user.email', 'luke@rebel.org');
        $repository->addAll();
        $repository->commit('Initial commit');
        $repository->createBranch('issue12');
        $repository->createBranch('issue42');
        $repository->createBranch('branch/name/wiith/slashes');

        // foobar repository fixture
        $git->createRepository(self::$tmpdir . 'foobar');
        $repository = $git->getRepository(self::$tmpdir . 'foobar');

        file_put_contents(self::$tmpdir . 'foobar/bar.json', "{\n\"name\": \"foobar\"\n}");
        file_put_contents(self::$tmpdir . 'foobar/.git/description', 'This is a test repo!');
        $fs->mkdir(self::$tmpdir . 'foobar/myfolder');
        $fs->mkdir(self::$tmpdir . 'foobar/testfolder');
        file_put_contents(
            self::$tmpdir . 'foobar/myfolder/mytest.php',
                "<?php\necho 'Hello World'; // This is my test"
        );
        file_put_contents(
            self::$tmpdir . 'foobar/testfolder/test.php',
                "<?php\necho 'Hello World'; // This is a test"
        );
        $repository->setConfig('user.name', 'Luke Skywalker');
        $repository->setConfig('user.email', 'luke@rebel.org');
        $repository->addAll();
        $repository->commit('First commit');

        // Nested repository fixture
        $nested_dir = self::$tmpdir . 'nested/';
        $fs->mkdir($nested_dir);
        $git->createRepository($nested_dir . 'NestedRepo');
        $repository = $git->getRepository($nested_dir . 'NestedRepo');
        file_put_contents($nested_dir . 'NestedRepo/.git/description', 'This is a NESTED test repo!');
        file_put_contents($nested_dir . 'NestedRepo/README.txt', 'NESTED TEST REPO README');
        $repository->setConfig('user.name', 'Luke Skywalker');
        $repository->setConfig('user.email', 'luke@rebel.org');
        $repository->addAll();
        $repository->commit('First commit');
        $repository->createBranch('testing');
        $repository->checkout('testing');
        file_put_contents($nested_dir . 'NestedRepo/README.txt', 'NESTED TEST BRANCH README');
        $repository->addAll();
        $repository->commit('Changing branch');
        $repository->checkout('master');

        // master-less repository fixture
        $git->createRepository(self::$tmpdir . 'develop');
        $repository = $git->getRepository(self::$tmpdir . 'develop');
        $repository->setConfig('user.name', 'Luke Skywalker');
        $repository->setConfig('user.email', 'luke@rebel.org');
        file_put_contents(self::$tmpdir . 'develop/README.md', "## develop\ndevelop is a *test* repository!");
        $repository->addAll();
        $repository->commit('First commit');
        $repository->createBranch('develop');
        $repository = $repository->checkout('develop');

        file_put_contents(self::$tmpdir . 'develop/test.php', "<?php\necho 'Hello World'; // This is a test");
        $repository->setConfig('user.name', 'Luke Skywalker');
        $repository->setConfig('user.email', 'luke@rebel.org');
        $repository->addAll();
        $repository->commit('Initial commit');

        // Detached HEAD repository fixture
        $git->createRepository(self::$tmpdir . 'detached-head');
        $repository = $git->getRepository(self::$tmpdir . 'detached-head');
        $repository->setConfig('user.name', 'Luke Skywalker');
        $repository->setConfig('user.email', 'luke@rebel.org');
        file_put_contents(self::$tmpdir . 'detached-head/README.md', "## detached head\ndetached-head is a *test* repository!");
        $repository->addAll();
        $repository->commit('First commit');
        $repository->checkout('HEAD');

        // mailmap repository fixture
        $git->createRepository(self::$tmpdir . 'mailmap');
        $repository = $git->getRepository(self::$tmpdir . 'mailmap');
        $repository->setConfig('user.name', 'Luke Skywalker');
        $repository->setConfig('user.email', 'luke@rebel.org');
        file_put_contents(self::$tmpdir . 'mailmap/README.md', "## mailmap\nmailmap is a *test* repository!");
        file_put_contents(self::$tmpdir . 'mailmap/.mailmap', 'Anakin Skywalker <darth@empire.com> Luke Skywalker <luke@rebel.org>');
        $repository->addAll();
        $repository->commit('First commit');
    }

    public static function tearDownAfterClass(): void
    {
        $fs = new Filesystem();
        $fs->remove(self::$tmpdir);
    }

    public function createApplication()
    {
        $config = new GitList\Config();
        $config->set('app', 'debug', true);
        $config->set('app', 'debug', false);
        $config->set('git', 'client', self::$gitPath);
        $config->set('git', 'default_branch', 'master');
        $config->set('git', 'repositories', array(self::$tmpdir));

        $app = require 'boot.php';

        return $app;
    }

    /**
     * @covers \GitList\Controller\MainController::connect
     */
    public function testInitialPage()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/');

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('title:contains("GitList")'));

        $this->assertCount(1, $crawler->filter('div.repository-header a:contains("detached-head")'));
        $this->assertEquals('/detached-head/', $crawler->filter('.repository-header a')->eq(0)->attr('href'));
        $this->assertEquals('/detached-head/master/rss/', $crawler->filter('.repository-header a')->eq(1)->attr('href'));

        $this->assertCount(1, $crawler->filter('div.repository-header a:contains("develop")'));
        $this->assertEquals('/develop/', $crawler->filter('.repository-header a')->eq(2)->attr('href'));
        $this->assertEquals('/develop/master/rss/', $crawler->filter('.repository-header a')->eq(3)->attr('href'));

        $this->assertCount(1, $crawler->filter('div.repository-header:contains("foobar")'));
        $this->assertCount(1, $crawler->filter('div.repository-body:contains("This is a test repo!")'));
        $this->assertEquals('/foobar/', $crawler->filter('.repository-header a')->eq(4)->attr('href'));
        $this->assertEquals('/foobar/master/rss/', $crawler->filter('.repository-header a')->eq(5)->attr('href'));

        $this->assertCount(1, $crawler->filter('div.repository-header a:contains("GitTest")'));
        $this->assertEquals('/GitTest/', $crawler->filter('.repository-header a')->eq(6)->attr('href'));
        $this->assertEquals('/GitTest/master/rss/', $crawler->filter('.repository-header a')->eq(7)->attr('href'));

        $this->assertCount(1, $crawler->filter('div.repository-header a:contains("mailmap")'));
        $this->assertEquals('/mailmap/', $crawler->filter('.repository-header a')->eq(8)->attr('href'));
        $this->assertEquals('/mailmap/master/rss/', $crawler->filter('.repository-header a')->eq(9)->attr('href'));

        $this->assertCount(1, $crawler->filter('div.repository-header a:contains("nested/NestedRepo")'));
        $this->assertEquals('/nested/NestedRepo/', $crawler->filter('.repository-header a')->eq(10)->attr('href'));
        $this->assertEquals('/nested/NestedRepo/master/rss/', $crawler->filter('.repository-header a')->eq(11)->attr('href'));
        $this->assertCount(1, $crawler->filter('div.repository-body:contains("This is a NESTED test repo!")'));
    }

    /**
     * @covers \GitList\Controller\TreeController::connect
     */
    public function testRepositoryPage()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/GitTest/');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('.tree tr:contains("README.md")'));
        $this->assertCount(1, $crawler->filter('.tree tr:contains("test.php")'));
        $this->assertCount(1, $crawler->filter('.md-header:contains("README.md")'));
        $this->assertEquals("## GitTest\nGitTest is a *test* repository!", $crawler->filter('#md-content')->eq(0)->text());
        $this->assertEquals('/GitTest/blob/master/README.md', $crawler->filter('.tree tr td')->eq(0)->filter('a')->eq(0)->attr('href'));
        $this->assertEquals('/GitTest/blob/master/test.php', $crawler->filter('.tree tr td')->eq(3)->filter('a')->eq(0)->attr('href'));

        $this->assertEquals('branch/name/wiith/slashes', $crawler->filter('.dropdown-menu li')->eq(1)->text());
        $this->assertEquals('issue12', $crawler->filter('.dropdown-menu li')->eq(2)->text());
        $this->assertEquals('issue42', $crawler->filter('.dropdown-menu li')->eq(3)->text());
        $this->assertEquals('master', $crawler->filter('.dropdown-menu li')->eq(4)->text());

        $crawler = $client->request('GET', '/foobar/');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('.tree tr:contains("myfolder")'));
        $this->assertCount(1, $crawler->filter('.tree tr:contains("testfolder")'));
        $this->assertCount(1, $crawler->filter('.tree tr:contains("bar.json")'));
        $this->assertEquals('/foobar/tree/master/myfolder/', $crawler->filter('.tree tr td')->eq(0)->filter('a')->eq(0)->attr('href'));
        $this->assertEquals('/foobar/tree/master/testfolder/', $crawler->filter('.tree tr td')->eq(3)->filter('a')->eq(0)->attr('href'));
        $this->assertEquals('/foobar/blob/master/bar.json', $crawler->filter('.tree tr td')->eq(6)->filter('a')->eq(0)->attr('href'));
        $this->assertCount(0, $crawler->filter('.md-header'));
        $this->assertEquals('master', $crawler->filter('.dropdown-menu li')->eq(1)->text());
    }

    /**
     * @covers \GitList\Controller\BlobController::connect
     */
    public function testBlobPage()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/GitTest/blob/master/test.php');

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('.breadcrumb .active:contains("test.php")'));
        $this->assertEquals(
            '/GitTest/raw/master/test.php',
                $crawler->filter('.source-header .btn-group a')->eq(0)->attr('href')
        );
        $this->assertEquals(
            '/GitTest/blame/master/test.php',
                $crawler->filter('.source-header .btn-group a')->eq(1)->attr('href')
        );
        $this->assertEquals(
            '/GitTest/logpatch/master/test.php',
                $crawler->filter('.source-header .btn-group a')->eq(2)->attr('href')
        );
        $this->assertEquals(
            '/GitTest/commits/master/test.php',
                $crawler->filter('.source-header .btn-group a')->eq(3)->attr('href')
        );
    }

    /**
     * @covers \GitList\Controller\BlobController::connect
     */
    public function testRawPage()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/GitTest/raw/master/test.php');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals("<?php\necho 'Hello World'; // This is a test", $client->getResponse()->getContent());
    }

    /**
     * @covers \GitList\Controller\CommitController::connect
     */
    public function testBlamePage()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/GitTest/blame/master/test.php');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('.source-header .meta:contains("test.php")'));
        $this->assertRegexp(
            '/\/GitTest\/commit\/[a-zA-Z0-9%]+/',
                $crawler->filter('.blame-view .commit')->eq(0)->filter('a')->attr('href')
        );

        $crawler = $client->request('GET', '/foobar/blame/master/bar.json');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('.source-header .meta:contains("bar.json")'));
        $this->assertRegexp(
            '/\/foobar\/commit\/[a-zA-Z0-9%]+/',
                $crawler->filter('.blame-view .commit')->eq(0)->filter('a')->attr('href')
        );
    }

    /**
     * @covers \GitList\Controller\CommitController::connect
     */
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

        $crawler = $client->request('GET', '/mailmap/commits/master/README.md');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('Anakin Skywalker', $crawler->filter('.table tbody tr td span a')->eq(1)->text());
        $this->assertEquals('mailto:darth@empire.com', $crawler->filter('.table tbody tr td span a')->eq(1)->attr('href'));
    }

    /**
     * @covers \GitList\Controller\CommitController::connect
     */
    public function testCommitsPage()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/GitTest/commits');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('Initial commit', $crawler->filter('.table tbody tr td h4')->eq(0)->text());

        $crawler = $client->request('GET', '/foobar/commits');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('First commit', $crawler->filter('.table tbody tr td h4')->eq(0)->text());

        $crawler = $client->request('GET', '/mailmap/commits');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('Anakin Skywalker', $crawler->filter('.table tbody tr td span a')->eq(1)->text());
        $this->assertEquals('mailto:darth@empire.com', $crawler->filter('.table tbody tr td span a')->eq(1)->attr('href'));
    }

    public function testPatchLogPage()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/GitTest/logpatch/master/test.php');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('Initial commit', $crawler->filter('.commit-header h4')->eq(0)->text());

        $crawler = $client->request('GET', '/GitTest/logpatch/master/README.md');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('Initial commit', $crawler->filter('.commit-header h4')->eq(0)->text());

        $crawler = $client->request('GET', '/foobar/logpatch/master/bar.json');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('First commit', $crawler->filter('.commit-header h4')->eq(0)->text());
    }

    /**
     * @covers \GitList\Controller\MainController::connect
     */
    public function testStatsPage()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/GitTest/stats');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertRegexp('/.php: 1 files/', $crawler->filter('.table tbody')->eq(0)->text());
        $this->assertRegexp('/.md: 1 files/', $crawler->filter('.table tbody')->eq(0)->text());
        $this->assertRegexp('/Total files: 2/', $crawler->filter('.table tbody')->eq(0)->text());
        $this->assertRegexp('/Luke Skywalker: 1 commits/', $crawler->filter('.table tbody')->eq(0)->text());

        $crawler = $client->request('GET', '/mailmap/stats');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertRegexp('/Anakin Skywalker: 1 commits/', $crawler->filter('.table tbody')->eq(0)->text());
    }

    /**
     * @covers \GitList\Controller\MainController::connect
     */
    public function testRssPage()
    {
        $client = $this->createClient();

        $client->request('GET', '/GitTest/master/rss/');
        $response = $client->getResponse();

        $this->assertTrue($response->isOk());
        $this->assertRegexp('/Latest commits in GitTest:master/', $client->getResponse()->getContent());
        $this->assertRegexp('/Initial commit/', $client->getResponse()->getContent());
    }

    /**
     * @covers \GitList\Controller\TreeController::connect
     */
    public function testNestedRepoPage()
    {
        $client = $this->createClient();

        $client->request('GET', '/nested/NestedRepo/');
        $response = $client->getResponse();

        $this->assertTrue($response->isOk());
        $this->assertRegexp('/NESTED TEST REPO README/', $client->getResponse()->getContent());
    }

    /**
     * @covers \GitList\Controller\TreeController::connect
     */
    public function testDevelopRepo()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/develop/');
        $this->assertTrue($client->getResponse()->isOk());
    }

    /**
     * @covers \GitList\Controller\TreeController::connect
     */
    public function testNestedRepoBranch()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/nested/NestedRepo/testing/');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertRegexp('/NESTED TEST BRANCH README/', $client->getResponse()->getContent());
    }
}
