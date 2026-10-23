<?php

declare(strict_types=1);

namespace GitList\SCM;

use PHPUnit\Framework\TestCase;

class AttributeRuleTest extends TestCase
{
    public function testIsParsingAttributeStates(): void
    {
        $rules = AttributeRule::parse('docs/.gitattributes', "# comment\n\n*.md  text -diff !eol linguist-language=Markdown\n  [attr]docs linguist-documentation\n");

        $this->assertCount(2, $rules);

        $this->assertSame('docs/.gitattributes', $rules[0]->getSource());
        $this->assertSame(3, $rules[0]->getLine());
        $this->assertSame('*.md', $rules[0]->getPattern());
        $this->assertSame(['text' => true, 'diff' => false, 'eol' => null, 'linguist-language' => 'Markdown'], $rules[0]->getAttributes());
        $this->assertFalse($rules[0]->isMacro());

        $this->assertSame(4, $rules[1]->getLine());
        $this->assertSame('[attr]docs', $rules[1]->getPattern());
        $this->assertTrue($rules[1]->isMacro());
    }

    public function testIsLocatingSourceDirectory(): void
    {
        $this->assertSame('', AttributeRule::parse('.gitattributes', '* text')[0]->getDirectory());
        $this->assertSame('docs/api', AttributeRule::parse('docs/api/.gitattributes', '* text')[0]->getDirectory());
        $this->assertSame('', AttributeRule::parse('info/attributes', '* text')[0]->getDirectory());
        $this->assertTrue(AttributeRule::parse('docs/.gitattributes', '* text')[0]->isInTree());
        $this->assertFalse(AttributeRule::parse('info/attributes', '* text')[0]->isInTree());
    }
}
