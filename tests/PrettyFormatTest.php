<?php

class PrettyFormatTest extends PHPUnit_Framework_TestCase
{
    public function testIsParsingPrettyXMLFormat()
    {
        $format = new Gitter\PrettyFormat;
        $output = $format->parse('<item><a>1</a><b>2</b><c>3</c></item><item><a>4</a><b>5</b><c>6</c></item>');
        $this->assertEquals($output[0]['a'], '1');
        $this->assertEquals($output[0]['b'], '2');
        $this->assertEquals($output[0]['c'], '3');
        $this->assertEquals($output[1]['a'], '4');
        $this->assertEquals($output[1]['b'], '5');
        $this->assertEquals($output[1]['c'], '6');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIsNotParsingWithoutData()
    {
        $format = new Gitter\PrettyFormat;
        $format->parse('');
    }
}
