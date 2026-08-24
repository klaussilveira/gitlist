<?php

declare(strict_types=1);

namespace GitList\E2E;

use Symfony\Component\Panther\PantherTestCase;

abstract class WebTestCase extends PantherTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $handler = set_exception_handler(null);
        restore_exception_handler();

        if (null !== $handler) {
            restore_exception_handler();
        }
    }
}
