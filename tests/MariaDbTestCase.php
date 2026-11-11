<?php

namespace Tests;

use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class MariaDbTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $socket = getenv('MV030_MARIADB_SOCKET');
        if (! $socket) {
            $this->markTestSkipped('Requires an explicitly provisioned isolated MV-030 MariaDB socket.');
        }
        self::configureIsolatedDatabase($socket);
        $this->artisan('migrate', ['--database' => 'mv030_test', '--force' => true])->assertExitCode(0);
    }

    public static function configureIsolatedDatabase(string $socket): void
    {
        if (! app()->environment('testing') || ! preg_match('#^/tmp/mv030-mariadb-[a-zA-Z0-9]+/mysql\.sock$#D', $socket)
            || realpath(dirname($socket)) !== dirname($socket)) {
            throw new RuntimeException('Refusing non-isolated MariaDB configuration.');
        }
        config(['database.default' => 'mv030_test', 'database.connections.mv030_test' => [
            'driver' => 'mariadb', 'url' => null, 'host' => 'localhost', 'unix_socket' => $socket,
            'database' => 'mv030_test', 'username' => 'root', 'password' => '',
            'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => true,
        ], 'cache.default' => 'array', 'session.driver' => 'array']);
        DB::purge('mv030_test');
        $server = DB::selectOne('SELECT @@datadir AS directory, DATABASE() AS db, @@skip_networking AS offline, VERSION() AS version');
        if (rtrim($server->directory, '/') !== dirname($socket).'/data'
            || $server->db !== 'mv030_test' || (int) $server->offline !== 1 || ! str_contains($server->version, 'MariaDB')) {
            throw new RuntimeException('Refusing migrations: effective server is not the isolated offline MariaDB instance.');
        }
    }
}
