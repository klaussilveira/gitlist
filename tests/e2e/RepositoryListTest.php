<?php

declare(strict_types=1);

namespace GitList\E2E;

class RepositoryListTest extends WebTestCase
{
    public function testIsListingRepositories(): void
    {
        $client = self::createHttpBrowserClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.card-header', 'git-bare-repo');
        self::assertSelectorTextContains('.card-body', 'foobar');
    }
}
