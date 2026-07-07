<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    // NOTE: RefreshDatabase is NOT used here because some migrations contain
    // MySQL-specific syntax (MODIFY COLUMN) that fails on SQLite :memory:.
    // Tests that need DB isolation should manage their own transactions/cleanup.
}
