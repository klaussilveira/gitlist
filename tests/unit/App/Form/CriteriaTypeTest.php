<?php

declare(strict_types=1);

namespace GitList\App\Form;

use DateTime;
use GitList\SCM\Commit\Criteria;
use Symfony\Component\Form\Test\TypeTestCase;

class CriteriaTypeTest extends TypeTestCase
{
    public function testIsSubmittingValidData(): void
    {
        $input = [
            'from' => '2021-12-28T00:00',
            'to' => '2021-12-28T00:00',
            'author' => 'Foo',
            'message' => 'foobar',
        ];

        $actual = new Criteria();

        $expected = new Criteria();
        $expected->setFrom(new DateTime('2021-12-28T00:00'));
        $expected->setTo(new DateTime('2021-12-28T00:00'));
        $expected->setAuthor('Foo');
        $expected->setMessage('foobar');

        $form = $this->factory->create(CriteriaType::class, $actual);
        $form->submit($input);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertEquals($expected, $actual);
    }

    public function testIsValidatingDates(): void
    {
        $input = [
            'from' => '88888-12-2800:00',
            'author' => 'Foo',
        ];

        $actual = new Criteria();

        $form = $this->factory->create(CriteriaType::class, $actual);
        $form->submit($input);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
    }
}
