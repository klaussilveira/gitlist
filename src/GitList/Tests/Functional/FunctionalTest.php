<?php

namespace GitList\Tests\Functional;

use GitList\Application;
use GitList\Component\Git\Client;

use Silex\WebTestCase;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

class FunctionalTest extends WebTestCase
{
    public static $path;

    public static function setUpBeforeClass()
    {
        self::$path = sprintf('%s/gitlist/%s', sys_get_temp_dir(), time());

        $fs = new Filesystem();
        try {
            $fs->mkdir(self::$path);
        } catch (IOException $e) {
            $this->markTestSkipped('There are no write permissions in order to create test repositories.');
        }

        $options['path'] = getenv('GIT_CLIENT') ?: '/usr/bin/git';
        $options['hidden'] = array(self::$path . '/hiddenrepo');
        $git = new Client($options);

        // GitTest repository fixture
        $git->createRepository(self::$path . '/GitTest');
        $repository = $git->getRepository(self::$path . '/GitTest');
        file_put_contents(self::$path . '/GitTest/README.md', "## GitTest\nGitTest is a *test* repository!");
        file_put_contents(self::$path . '/GitTest/test.php', "<?php\necho 'Hello World'; // This is a test");
        $repository->addAll();
        $repository->commit("Initial commit");
        $repository->createBranch('issue12');
        $repository->createBranch('issue42');

        // foobar repository fixture
        $git->createRepository(self::$path . '/foobar');
        $repository = $git->getRepository(self::$path . '/foobar');
        file_put_contents(self::$path . '/foobar/bar.json', "{\n\"name\": \"foobar\"\n}");
        file_put_contents(self::$path . '/foobar/.git/description', 'This is a test repo!');
        $fs->mkdir(self::$path . '/foobar/myfolder');
        $fs->mkdir(self::$path . '/foobar/testfolder');
        file_put_contents(self::$path . '/foobar/myfolder/mytest.php', "<?php\necho 'Hello World'; // This is my test");
        file_put_contents(self::$path . '/foobar/testfolder/test.php', "<?php\necho 'Hello World'; // This is a test");
        $repository->addAll();
        $repository->commit("First commit");
    }

    public function createApplication()
    {
        $app = new Application();
        $app['debug'] = true;
        $app['git.client'] = '/usr/bin/git';
        $app['git.hidden'] = array();
        $app['git.repos'] = self::$path.'/';

        require __DIR__.'/../../../controllers.php';

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
        $fs->remove(self::$path);
    }
}
