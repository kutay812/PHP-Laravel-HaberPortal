<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MarketDataService
{
    public function __construct(
        private readonly UrlSecurityService $urlSecurity,
    ) {}

    /**
     * Get market data (USD, EUR, GOLD, BIST 100) with verified TLS, SSRF check, and deterministic fallback.
     *
     * @return array<string, array{value: string, change: string}>
     */
    public function getMarketData(): array
    {
        return Cache::remember('market_data', 300, function (): array {
            $fallback = [
                'BIST 100' => ['value' => '9.854', 'change' => '+0.0%'],
                'DOLAR'    => ['value' => '34.20', 'change' => '+0.1%'],
                'EURO'     => ['value' => '37.10', 'change' => '+0.2%'],
                'ALTIN'    => ['value' => '2.950', 'change' => '-0.3%'],
            ];

            $endpoint = (string) config('services.market_data.url', '');
            $timeout = max(1, min(10, (int) config('services.market_data.timeout', 3)));

            if ($endpoint === '' || !$this->urlSecurity->isSafePublicUrl($endpoint)) {
                return $fallback;
            }

            try {
                $response = Http::timeout($timeout)
                    ->connectTimeout(2)
                    ->withoutRedirecting()
                    ->get($endpoint);

                if ($response->successful()) {
                    $json = $response->json();
                    if (is_array($json)) {
                        if (isset($json['USD']) && is_array($json['USD'])) {
                            $fallback['DOLAR']['value'] = (string) ($json['USD']['Satış'] ?? $fallback['DOLAR']['value']);
                            $fallback['DOLAR']['change'] = (string) ($json['USD']['Değişim'] ?? $fallback['DOLAR']['change']);
                        }
                        if (isset($json['EUR']) && is_array($json['EUR'])) {
                            $fallback['EURO']['value'] = (string) ($json['EUR']['Satış'] ?? $fallback['EURO']['value']);
                            $fallback['EURO']['change'] = (string) ($json['EUR']['Değişim'] ?? $fallback['EURO']['change']);
                        }
                        if (isset($json['gram-altin']) && is_array($json['gram-altin'])) {
                            $fallback['ALTIN']['value'] = (string) ($json['gram-altin']['Satış'] ?? $fallback['ALTIN']['value']);
                            $fallback['ALTIN']['change'] = (string) ($json['gram-altin']['Değişim'] ?? $fallback['ALTIN']['change']);
                        }
                        if (isset($json['XU100']) && is_array($json['XU100'])) {
                            $fallback['BIST 100']['value'] = (string) ($json['XU100']['Satış'] ?? $fallback['BIST 100']['value']);
                            $fallback['BIST 100']['change'] = (string) ($json['XU100']['Değişim'] ?? $fallback['BIST 100']['change']);
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Market data fetch timeout or failure: ' . $e->getMessage());
            }

            return $fallback;
        });
    }
}
