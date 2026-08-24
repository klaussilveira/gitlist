<?php

declare(strict_types=1);

namespace GitList\E2E;

class BranchListTest extends WebTestCase
{
    public function testIsListingBranches(): void
    {
        $client = self::createHttpBrowserClient();
        $client->request('GET', '/git-bare-repo/branches');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('.card-header', 'Remote branches');
        self::assertSelectorTextSame('.list-group-item h5 a[href="/git-bare-repo/tree/master"]', 'master');
        self::assertSelectorTextSame('.list-group-item h5 a[href="/git-bare-repo/tree/feature/1.2-dev"]', 'feature/1.2-dev');
    }
}
