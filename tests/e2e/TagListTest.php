<?php

declare(strict_types=1);

namespace GitList\E2E;

class TagListTest extends WebTestCase
{
    public function testIsListingTags(): void
    {
        $client = self::createHttpBrowserClient();
        $client->request('GET', '/git-bare-repo/tags');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextSame('.card-header', 'Remote tags');
        self::assertSelectorTextSame('.list-group-item h5 a[href="/git-bare-repo/tree/1.2"]', '1.2');
    }
}
