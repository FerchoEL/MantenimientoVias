<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;

abstract class DatabaseTestCase extends TestCase
{
    use LazilyRefreshDatabase;

    public function createApplication(): Application
    {
        $application = parent::createApplication();

        $this->requireInMemoryConfiguration();

        return $application;
    }

    private function requireInMemoryConfiguration(): void
    {
        if (! app()->environment('testing')
            || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
            || filled(config('database.connections.sqlite.url'))
            || config('database.connections.sqlite.foreign_key_constraints') !== true) {
            throw new RuntimeException('Database tests require testing / sqlite / :memory: with foreign keys enabled.');
        }
    }

    protected function beforeRefreshingDatabase(): void
    {
        $this->requireInMemoryConfiguration();

        $connection = DB::connection('sqlite');

        if ($connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite'
            || $connection->selectOne('PRAGMA database_list')->file !== ''
            || (int) $connection->selectOne('PRAGMA foreign_keys')->foreign_keys !== 1) {
            throw new RuntimeException('Refusing migrations: the effective database is not isolated SQLite memory.');
        }
    }
}
