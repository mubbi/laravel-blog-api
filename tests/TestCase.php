<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Event;

abstract class TestCase extends BaseTestCase
{
    // Auto seed the test database with DatabaseSeeder
    protected $seed = true;

    /**
     * Specify which seeder to use
     */
    protected string $seeder = \Database\Seeders\DatabaseSeeder::class;

    /**
     * Setup the test environment.
     * Fake all events by default to prevent listeners from running during tests,
     * which improves performance. Tests that need to verify event dispatching
     * can use Event::fake([SpecificEvent::class]) to fake specific events.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Fake all events globally to prevent listeners from executing
        // This significantly improves test performance since listeners are queued
        // and run synchronously with QUEUE_CONNECTION=sync
        Event::fake();
    }
}
