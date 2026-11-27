<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->configureApiRateLimiters();
    }

    protected function configureApiRateLimiters(): void
    {
        $actorKey = function (Request $request): string {
            if ($request->user()?->id) {
                return 'uid:' . $request->user()->id;
            }
            $rawToken = $request->bearerToken() ?: $request->header('X-Auth-Token');
            if (is_string($rawToken) && trim($rawToken) !== '') {
                return 'tok:' . hash('sha256', trim($rawToken));
            }
            return 'ip:' . $request->ip();
        };

        RateLimiter::for('api-auth-public', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('api-visit-public', fn (Request $request) => Limit::perMinute(180)->by($actorKey($request)));
        RateLimiter::for('api-heartbeat', fn (Request $request) => Limit::perMinute(30)->by($actorKey($request)));
        RateLimiter::for('api-user-write', fn (Request $request) => Limit::perMinute(60)->by($actorKey($request)));
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
            @file_put_contents($cacheFile, json_encode(['ts' => time(), 'up' => $isUp]), LOCK_EX);
        }

        if (!$isUp) {
            config([
                'database.default' => 'sqlite',
                'database.connections.sqlite.database' => $sqlitePath,
                'database.connections.sqlite.foreign_key_constraints' => true,
                'database.connections.sqlite.busy_timeout' => 15000,
                'database.connections.sqlite.journal_mode' => 'WAL',
                'database.connections.sqlite.synchronous' => 'NORMAL',
                'database.connections.sqlite.transaction_mode' => 'IMMEDIATE',
            ]);
            DB::purge('pgsql');
        }
    }
}
