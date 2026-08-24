<?php

declare(strict_types=1);

namespace GitList\E2E;

class NotFoundTest extends WebTestCase
{
    public function testIsRejectingUnknownRepository(): void
    {
        $client = self::createHttpBrowserClient();
        $client->request('GET', '/does-not-exist');

        self::assertResponseStatusCodeSame(404);
    }

    public function testIsRejectingUnknownBlob(): void
    {
        $client = self::createHttpBrowserClient();
        $client->request('GET', '/git-bare-repo/blob/master/does-not-exist.txt');

        self::assertResponseStatusCodeSame(404);
    }

    public function testIsRejectingUnknownCommit(): void
    {
        $client = self::createHttpBrowserClient();
        $client->request('GET', '/git-bare-repo/commit/deadbeefdeadbeefdeadbeefdeadbeefdeadbeef');

        self::assertResponseStatusCodeSame(404);
    }

    public function testIsRejectingUnknownCommitish(): void
    {
        $client = self::createHttpBrowserClient();
        $client->request('GET', '/git-bare-repo/commits/deadbeefdeadbeefdeadbeefdeadbeefdeadbeef');

        self::assertResponseStatusCodeSame(404);
    }
}
