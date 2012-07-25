<?php

require 'vendor/autoload.php';

use Silex\WebTestCase;

class InterfaceTest extends WebTestCase
{
    public function createApplication()
    {
        $app = require 'index.php';
        $app['debug'] = true;
        return $app;
    }

    public function testInitialPage()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/');

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertCount(1, $crawler->filter('title:contains("Gitlist")'));
    }
}