<?php

declare(strict_types=1);

namespace GitList\SCM;

use PHPUnit\Framework\TestCase;

class BlobTest extends TestCase
{
    public function testIsGettingFileName(): void
    {
        $blob = new Blob(new Repository('/my/repo'), sha1((string) random_int(0, mt_getrandmax())));
        $blob->setName('/var/foo/bar.c');
        $this->assertEquals('/var/foo/bar.c', $blob->getName());
        $this->assertEquals('bar.c', $blob->getFileName());
    }

    public function testIsDetectingReadme(): void
    {
        $blob = new Blob(new Repository('/my/repo'), sha1((string) random_int(0, mt_getrandmax())));

        $blob->setName('/var/foo/README.md');
        $this->assertTrue($blob->isReadme());

        $blob->setName('/var/foo/ReaDME.MD');
        $this->assertTrue($blob->isReadme());

        $blob->setName('/var/foo/README.c');
        $this->assertFalse($blob->isReadme());
    }

    public function testIsDetectingDocumentType(): void
    {
        $blob = new Blob(new Repository('/my/repo'), sha1((string) random_int(0, mt_getrandmax())));

        $blob->setName('/var/foo/README');
        $this->assertSame('readme', $blob->getDocumentType());

        $blob->setName('/var/foo/CONTRIBUTING.txt');
        $this->assertSame('contributing', $blob->getDocumentType());

        $blob->setName('/var/foo/LICENSE');
        $this->assertSame('license', $blob->getDocumentType());

        $blob->setName('/var/foo/license.md');
        $this->assertSame('license', $blob->getDocumentType());

        $blob->setName('/var/foo/COPYING');
        $this->assertSame('license', $blob->getDocumentType());

        $blob->setName('/var/foo/Security.MD');
        $this->assertSame('security', $blob->getDocumentType());

        $blob->setName('/var/foo/LICENSE.c');
        $this->assertNull($blob->getDocumentType());

        $blob->setName('/var/foo/LICENSES');
        $this->assertNull($blob->getDocumentType());

        $blob->setName('/var/foo/.md');
        $this->assertNull($blob->getDocumentType());
    }

    public function testIsDetectingMarkdown(): void
    {
        $blob = new Blob(new Repository('/my/repo'), sha1((string) random_int(0, mt_getrandmax())));

        $blob->setName('/var/foo/SECURITY.md');
        $this->assertTrue($blob->isMarkdown());

        $blob->setName('/var/foo/README.TXT');
        $this->assertTrue($blob->isMarkdown());

        $blob->setName('/var/foo/README');
        $this->assertFalse($blob->isMarkdown());

        $blob->setName('/var/foo/LICENSE.txt');
        $this->assertFalse($blob->isMarkdown());

        $blob->setName('/var/foo/LICENSE');
        $this->assertFalse($blob->isMarkdown());
    }
}
