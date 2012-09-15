<?php

namespace Gitter\Tests;

use Gitter\PrettyFormat;

class PrettyFormatTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataForTestIsParsingPrettyXMLFormat
     */
    public function testIsParsingPrettyXMLFormat($xml, $expected)
    {
        $format = new PrettyFormat();

        $this->assertEquals($expected, $format->parse($xml));
    }

    public function dataForTestIsParsingPrettyXMLFormat()
    {
        return array(
            array(
                '<item><tag>value</tag><tag2>value2</tag2></item>',
                array(array('tag' => 'value', 'tag2' => 'value2')),
            ),
            array(
                '<item><empty_tag></empty_tag></item>',
                array(array('empty_tag' => '')),
            ),
            array(
                '<item><tag>item 1</tag></item><item><tag>item 2</tag></item>',
                array(array('tag' => 'item 1'), array('tag' => 'item 2')),
            ),
            array(
                '<item><tag><inner_tag>value</inner_tag></tag></item>',
                array(array('tag' => array(array('inner_tag' => 'value')))),
            )
        );
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIsNotParsingWithoutData()
    {
        $format = new PrettyFormat;
        $format->parse('');
    }
}
