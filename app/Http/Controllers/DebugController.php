<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DebugController extends Controller
{
    public function index()
    {
        return $this->success('debug', [
            'time' => $this->time(),
            'app' => $this->app(),
            'music' => config('music'),
            'runtime' => $this->runtime(),
            'counts' => $this->counts(),
            'config' => $this->mask(config()->all()),
        ]);
    }

    private function time()
    {
        return [
            'now' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get(),
            'app_timezone' => config('app.timezone'),
            'unix' => time(),
        ];
    }

    private function app()
    {
        return [
            'name' => config('app.name'),
            'env' => config('app.env'),
            'debug' => config('app.debug'),
            'url' => config('app.url'),
            'asset_url' => config('app.asset_url'),
            'locale' => config('app.locale'),
            'fallback_locale' => config('app.fallback_locale'),
            'timezone' => config('app.timezone'),
            'laravel' => app()->version(),
            'php' => PHP_VERSION,
        ];
    }

    private function runtime()
    {
        $default = config('database.default');

        return [
            'php_sapi' => PHP_SAPI,
            'php_os' => PHP_OS,
            'extensions' => get_loaded_extensions(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_file_uploads' => ini_get('max_file_uploads'),
            'cache' => config('cache.default'),
            'session' => config('session.driver'),
            'queue' => config('queue.default'),
            'filesystem' => config('filesystems.default'),
            'db_default' => $default,
            'db_database' => config('database.connections.' . $default . '.database'),
            'db_version' => $this->dbVersion(),
            'storage_writable' => is_writable(storage_path()),
            'debug_auth' => config('music.debugAuth'),
        ];
    }

    private function dbVersion()
    {
        try {
            $row = DB::selectOne('select version() as version');
            return $row->version ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function counts()
    {
        $counts = [];
        foreach (['songs', 'files', 'users', 'votes', 'reports', 'orders'] as $table) {
            try {
                $counts[$table] = DB::table($table)->count();
            } catch (\Throwable $e) {
                $counts[$table] = null;
            }
        }
        return $counts;
    }

    private function mask(array $config)
    {
        $masked = [];
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $masked[$key] = $this->mask($value);
            } elseif (preg_match('/password|secret|salt|token|apikey/i', (string) $key) || $key === 'key') {
                $masked[$key] = '***';
            } else {
                $masked[$key] = $value;
            }
        }
        return $masked;
    }
}
