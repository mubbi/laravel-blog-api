<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Auto seed the test database with DatabaseSeeder
    protected $seed = true;

    /**
     * Specify which seeder to use
     */
    protected string $seeder = \Database\Seeders\DatabaseSeeder::class;
}
