<?php

declare(strict_types=1);

namespace GitList\SCM;

use PHPUnit\Framework\TestCase;

class LanguageTest extends TestCase
{
    public function testIsCreatingFromExtension(): void
    {
        $language = new Language('php');
        $this->assertEquals('PHP', $language->getName());
        $this->assertNull($language->getGroup());
        $this->assertEquals('#4F5D95', $language->getColor());
        $this->assertEquals('php', $language->getAceMode());
        $this->assertEquals('php', $language->getCodeMirrorMode());
    }

    public function testIsCreatingUnknown(): void
    {
        $language = new Language('foobarbaz');
        $this->assertEquals('Unknown', $language->getName());
        $this->assertNull($language->getGroup());
        $this->assertNull($language->getColor());
        $this->assertEquals('text', $language->getAceMode());
        $this->assertNull($language->getCodeMirrorMode());
    }
}
