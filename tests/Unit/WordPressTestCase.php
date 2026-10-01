<?php

declare(strict_types=1);

namespace SymPress\StarterTheme\Tests\Unit;

use Brain\Monkey;
use PHPUnit\Framework\TestCase;

abstract class WordPressTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }
}
