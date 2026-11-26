<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->ensureDatabaseHighAvailability();
    }

    /**
     * Si el servidor PostgreSQL remoto (tecnoweb.org.bo) sufre caída temporal de red,
     * conmuta transparentemente a la réplica transaccional local SQLite sin arrojar errores 500,
     * y vuelve automáticamente a PostgreSQL apenas el servidor remoto responde.
     */
    protected function ensureDatabaseHighAvailability(): void
    {
        if (config('database.default') !== 'pgsql') {
            return;
        }

        $host = (string) config('database.connections.pgsql.host', '127.0.0.1');
        if (in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
            return;
        }

        $sqlitePath = database_path('database.sqlite');
        if (!is_file($sqlitePath)) {
            return;
        }

        $port = (int) config('database.connections.pgsql.port', 5432);
        $cacheFile = storage_path('framework/cache/pgsql_probe.json');
        $isUp = null;

        if (is_file($cacheFile)) {
            $cached = @json_decode((string) @file_get_contents($cacheFile), true);
            if (is_array($cached) && isset($cached['ts'], $cached['up']) && (time() - (int) $cached['ts']) < 20) {
                $isUp = (bool) $cached['up'];
            }
        }

        if ($isUp === null) {
            $fp = @fsockopen($host, $port, $errno, $errstr, 0.5);
            $isUp = is_resource($fp);
            if ($isUp) {
                @fclose($fp);
            }
            @file_put_contents($cacheFile, json_encode(['ts' => time(), 'up' => $isUp]));
        }

        if (!$isUp) {
            config([
                'database.default' => 'sqlite',
                'database.connections.sqlite.database' => $sqlitePath,
                'database.connections.sqlite.foreign_key_constraints' => true,
            ]);
            DB::purge('pgsql');
        }
    }
}
