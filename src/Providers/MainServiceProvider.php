<?php

namespace MainSys\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class MainServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Merge default config
        $this->mergeConfigFrom(__DIR__ . '/../../config/main.php', 'main');
    }

    public function boot()
    {
        // Publish the configuration file
        $this->publishes([
            __DIR__ . '/../../config/main.php' => config_path('main.php'),
        ]);

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->autoPingServer();
    }

    /**
     * Automatically ping the monitoring server.
     */
    protected function autoPingServer()
    {
        // Use cache to ensure the ping is sent only once within the timeframe
        $cacheKey = 'main_sys_last_ping';
        $pingInterval = 1; // Interval in minutes (e.g., once per hour)

        if (!Cache::has($cacheKey)) {
            try {
                $monitoringUrl = config('main.server_endpoint');
                $projectKey = config('main.token');

                // Collect data to send
                $data = [
                    'project_key' => $projectKey,
                    'domain' => request()->getHost(),
                    'ip_address' => request()->ip(),
                    'server_ip' => gethostbyname(gethostname()),
                    'server_details' => [
                        'php_version' => PHP_VERSION,
                        'os' => php_uname(),
                        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                    ],
                    'timestamp' => now()->toDateTimeString(),
                ];

                // Send the HTTP request
                $response = Http::post($monitoringUrl, $data);

                // Log success or failure
                if ($response->successful()) {
                    logger('Ping sent successfully.', ['response' => $response->json()]);
                } else {
                    logger('Ping failed.', ['response' => $response->body(), 'status' => $response->status()]);
                }

                // Cache the timestamp to avoid sending too frequently
                Cache::put($cacheKey, now(), $pingInterval * 60);

            } catch (\Exception $e) {

            }
        }
    }
}