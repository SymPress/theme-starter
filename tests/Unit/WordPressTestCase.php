<?php

declare(strict_types=1);

namespace SymPress\StarterTheme\Tests\Unit;

use Brain\Monkey;
use PHPUnit\Framework\TestCase;
use SymPress\Assets\IO\RequestFiles;

abstract class WordPressTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RequestFiles::reset();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        RequestFiles::reset();
        parent::tearDown();
    }
}
