<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Testing\Fakes\QueueFake;

class QueueTriggerService
{
    /**
     * XAMPP / yerel geliştirme ortamında (supervisor çalışmıyorken) veritabanı kuyruğundaki
     * işleri tüketmek için arka planda tek seferlik (stop-when-empty) ayrık bir PHP işçisi başlatır.
     */
    public function triggerWorkerIfNeeded(): void
    {
        if (app()->runningUnitTests() || Queue::getFacadeRoot() instanceof QueueFake) {
            return;
        }

        if (config('queue.default') !== 'database') {
            return;
        }

        if (!Cache::add('background_queue_worker_spawn_lock', true, 10)) {
            return;
        }

        $phpBinary = $this->resolvePhpCliBinary();
        if ($phpBinary === null) {
            return;
        }

        $artisanPath = base_path('artisan');

        try {
            if (PHP_OS_FAMILY === 'Windows') {
                $cmd = sprintf(
                    'start "" /B "%s" "%s" queue:work --stop-when-empty --tries=1 --timeout=300 > NUL 2>&1',
                    $phpBinary,
                    $artisanPath
                );
                $handle = @popen($cmd, 'r');
                if (is_resource($handle)) {
                    pclose($handle);
                }
            } else {
                $cmd = sprintf(
                    'nohup %s %s queue:work --stop-when-empty --tries=1 --timeout=300 >/dev/null 2>&1 &',
                    escapeshellarg($phpBinary),
                    escapeshellarg($artisanPath)
                );
                @exec($cmd);
            }
        } catch (\Throwable $e) {
            Log::warning('Arka plan kuyruk işçisi başlatılamadı: ' . $e->getMessage());
        }
    }

    private function resolvePhpCliBinary(): ?string
    {
        $candidates = [
            PHP_BINDIR . DIRECTORY_SEPARATOR . (PHP_OS_FAMILY === 'Windows' ? 'php.exe' : 'php'),
            'C:\\xampp\\php\\php.exe',
            PHP_BINARY,
        ];

        foreach ($candidates as $candidate) {
            if (
                is_file($candidate)
                && !str_contains(strtolower(basename($candidate)), 'httpd')
                && !str_contains(strtolower(basename($candidate)), 'php-cgi')
            ) {
                return $candidate;
            }
        }

        return null;
    }
}
