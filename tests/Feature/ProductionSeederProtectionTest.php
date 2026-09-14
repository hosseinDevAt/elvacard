<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionSeederProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_refuses_to_seed_development_credentials_in_production(): void
    {
        config(['app.env' => 'production']);

        $this->expectException(\RuntimeException::class);

        $this->seed(DatabaseSeeder::class);
    }

    public function test_seeder_runs_normally_outside_production(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', ['phone' => '09000000000', 'role' => 'admin']);
    }
}
