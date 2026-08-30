<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class ProxyClient
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('scraper.proxy_service_url'), '/');
        $this->timeout = (int) config('scraper.proxy_timeout', 3);
    }

    public function nextProxy(): ?ProxySelection
    {
        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->get("{$this->baseUrl}/proxy/next");
        } catch (\Throwable $e) {
            Log::warning('Proxy service unreachable; falling back to a direct request.', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        if (! $response->successful()) {
            Log::warning('Proxy service returned no usable proxy; falling back to a direct request.', [
                'status' => $response->status(),
            ]);
            return null;
        }

        $data = $response->json();
        if (empty($data['proxy']) || empty($data['user_agent'])) {
            Log::warning('Proxy service response was missing fields; falling back to a direct request.', [
                'body' => $data,
            ]);
            return null;
        }

        return new ProxySelection($data['proxy'], $data['user_agent']);
    }

    public function reportOutcome(string $proxy, bool $success): void
    {
        try {
            Http::timeout($this->timeout)->post("{$this->baseUrl}/proxy/report", [
                'proxy' => $proxy,
                'success' => $success,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to report proxy outcome.', [
                'proxy' => $proxy,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
