<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseConnectionSafetyTest extends TestCase
{
    public function test_verifies_test_database_is_in_memory_sqlite_and_never_mysql(): void
    {
        $connectionName = DB::getDefaultConnection();
        $databaseName = DB::connection()->getDatabaseName();
        $driverName = DB::connection()->getDriverName();

        $this->assertSame('sqlite', $connectionName, 'DB connection must be sqlite in test mode');
        $this->assertSame('sqlite', $driverName, 'DB driver must be sqlite in test mode');
        $this->assertSame(':memory:', $databaseName, 'Database name must be :memory: in test mode');
        $this->assertNotSame('mysql', $driverName, 'DB driver must never be mysql during test suite');
        $this->assertNotSame('elva_card_shop', $databaseName, 'Database name must never be elva_card_shop during test suite');
    }
}
