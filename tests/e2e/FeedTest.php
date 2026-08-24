<?php

declare(strict_types=1);

namespace GitList\E2E;

class FeedTest extends WebTestCase
{
    public function testIsServingRssFeed(): void
    {
        $client = self::createHttpBrowserClient();
        $client->request('GET', '/git-bare-repo/feed/master.rss');

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('Content-Type', 'application/rss+xml');

        $body = $client->getResponse()->getContent();
        self::assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $body);
        self::assertStringContainsString('<title>Recent commits to git-bare-repo (master)</title>', $body);
        self::assertStringContainsString('Fixed mm.', $body);
    }

    public function testIsServingAtomFeed(): void
    {
        $client = self::createHttpBrowserClient();
        $client->request('GET', '/git-bare-repo/feed/master.atom');

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('Content-Type', 'application/atom+xml');

        $body = $client->getResponse()->getContent();
        self::assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $body);
        self::assertStringContainsString('Fixed mm.', $body);
    }
}
