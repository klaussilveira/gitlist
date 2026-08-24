<?php

declare(strict_types=1);

namespace GitList\E2E;

class BlobTest extends WebTestCase
{
    public function testIsShowingFileContentsInEditor(): void
    {
        $client = self::createPantherClient();
        $client->request('GET', '/git-bare-repo/blob/HEAD/mm/cma.c');

        self::assertSelectorTextSame('.breadcrumb .active', 'cma.c');

        $crawler = $client->waitForElementToContain('.ace_content', 'Contiguous Memory Allocator');

        self::assertStringContainsString('* Contiguous Memory Allocator', $crawler->filter('.ace_comment')->eq(1)->text());
    }

    public function testIsShowingRawFile(): void
    {
        $client = self::createHttpBrowserClient();
        $client->request('GET', '/git-bare-repo/raw/HEAD/mm/cma.c');

        self::assertResponseStatusCodeSame(200);
        self::assertStringContainsString(' * Contiguous Memory Allocator', $client->getResponse()->getContent());
    }
}
