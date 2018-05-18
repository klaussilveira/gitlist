<?php

use GitList\Git\Client;
use GitList\Git\Repository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class RepositoryTest extends TestCase
{
    public function testIsSanitizingSearchWithPager()
    {
        $client = $this->prophesize('GitList\Git\Client');
        $client->run(Argument::type('GitList\Git\Repository'), "grep -i --line-number -- '=sleep 5;' master")->shouldBeCalled();

        $repository = new Repository('/tmp', $client->reveal());
        $repository->searchTree('--open-files-in-pager=sleep 5;', 'master');
        $repository->searchTree('-O=sleep 5;', 'master');
    }

    public function testIsSanitizingSearchWithAnyOption()
    {
        $client = $this->prophesize('GitList\Git\Client');
        $client->run(Argument::type('GitList\Git\Repository'), "grep -i --line-number -- 'foobar  =bar;' foo")->shouldBeCalled();

        $repository = new Repository('/tmp', $client->reveal());
        $repository->searchTree('foobar --bar --foo=bar;', 'foo');
    }
}
