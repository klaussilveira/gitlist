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
}
