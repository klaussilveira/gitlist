<?php

declare(strict_types=1);

namespace GitList\E2E;

class DocumentsTest extends WebTestCase
{
    public function testIsShowingDocumentTabs(): void
    {
        $client = self::createHttpBrowserClient();
        $crawler = $client->request('GET', '/git-docs-repo');

        self::assertResponseIsSuccessful();
        self::assertSame(['README', 'Contributing', 'License', 'Security'], $crawler->filter('#documents .nav-link')->each(fn ($tab) => $tab->text(null, true)));
        self::assertSelectorTextSame('#documents .nav-link.active', 'README');
        self::assertSelectorTextSame('#doc-readme h1', 'Docs Repo');
        self::assertSelectorTextSame('#doc-contributing strong', 'master');
        self::assertSelectorTextSame('#doc-security h2', 'Reporting');
    }

    public function testIsShowingLicenseAsPlainText(): void
    {
        $client = self::createHttpBrowserClient();
        $crawler = $client->request('GET', '/git-docs-repo');

        self::assertSame("# MIT License\n\nPermission is hereby granted, <b>free of charge</b>.\n", $crawler->filter('#doc-license pre')->text(null, false));
        self::assertCount(0, $crawler->filter('#doc-license h1'));
        self::assertCount(0, $crawler->filter('#doc-license b'));
    }

    public function testIsSwitchingDocumentTabs(): void
    {
        $client = self::createPantherClient();
        $client->request('GET', '/git-docs-repo');

        self::assertSelectorIsVisible('#doc-readme h1');
        self::assertSelectorIsNotVisible('#doc-license pre');

        $client->waitForVisibility('#doc-license-tab')->filter('#doc-license-tab')->click();
        $client->waitForVisibility('#doc-license pre');

        self::assertSelectorIsVisible('#doc-license pre');
        self::assertSelectorIsNotVisible('#doc-readme h1');
    }

    public function testIsNotShowingDocumentsWhenAbsent(): void
    {
        $client = self::createHttpBrowserClient();
        $crawler = $client->request('GET', '/git-bare-repo');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('.tab-pane[id^="doc-"]'));
    }
}
