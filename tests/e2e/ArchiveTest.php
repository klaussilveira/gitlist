<?php

declare(strict_types=1);

namespace GitList\E2E;

class ArchiveTest extends WebTestCase
{
    public function testIsServingZipArchive(): void
    {
        $client = self::createHttpBrowserClient();
        $client->request('GET', '/git-bare-repo/archive/master.zip');

        self::assertResponseStatusCodeSame(200);

        $body = $client->getResponse()->getContent();
        self::assertStringStartsWith("PK\x03\x04", $body);
    }

    public function testIsServingGzippedTarArchive(): void
    {
        $client = self::createHttpBrowserClient();
        $client->request('GET', '/git-bare-repo/archive/master.tar.gz');

        self::assertResponseStatusCodeSame(200);

        $body = $client->getResponse()->getContent();
        self::assertStringStartsWith("\x1f\x8b", $body);
        self::assertStringContainsString('mm/cma.c', (string) gzdecode($body));
    }

    public function testIsServingArchiveOfBranchWithSlash(): void
    {
        $client = self::createHttpBrowserClient();
        $client->request('GET', '/git-bare-repo/archive/feature/1.2-dev.zip');

        self::assertResponseStatusCodeSame(200);

        $body = $client->getResponse()->getContent();
        self::assertStringStartsWith("PK\x03\x04", $body);
    }

    public function testIsRejectingCommitishStartingWithHyphen(): void
    {
        $target = sys_get_temp_dir().'/gitlist-archive-injection-'.uniqid();

        $client = self::createHttpBrowserClient();
        $client->request('GET', '/git-bare-repo/archive/-o'.$target.'.tar');

        self::assertResponseStatusCodeSame(404);
        self::assertFileDoesNotExist($target);
    }
}
