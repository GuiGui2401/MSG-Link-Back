<?php

namespace App\Services\Payment;

use App\Models\ServiceConfiguration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FreeMoPayClient
{
    protected ?ServiceConfiguration $config = null;

    public function __construct()
    {
        $this->config = ServiceConfiguration::getFreeMoPayConfig();
    }

    /**
     * Make a POST request to FreeMoPay API
     *
     * @param string $endpoint
     * @param array $data
     * @param string|null $bearerToken
     * @param bool $useBasicAuth
     * @param int|null $timeout
     * @return array
     * @throws \Exception
     */
    public function post(
        string $endpoint,
        array $data,
        ?string $bearerToken = null,
        bool $useBasicAuth = false,
        ?int $timeout = null
    ): array {
        if (!$this->config || !$this->config->isConfigured()) {
            throw new \Exception('FreeMoPay service is not configured properly');
        }

        $url = $this->buildUrl($endpoint);
        $timeout = $timeout ?? $this->config->freemopay_init_payment_timeout;

        // Prepare headers
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'Estuaire-Emploie-Backend/1.0'
        ];

        // Log request (mask sensitive data)
        $this->logRequest('POST', $url, $headers, $data);

        $startTime = microtime(true);

        try {
            $http = Http::withHeaders($headers)->timeout($timeout);

            if ($bearerToken) {
                $http = $http->withToken($bearerToken);
            } elseif ($useBasicAuth) {
                $http = $http->withBasicAuth(
                    $this->config->freemopay_app_key,
                    $this->config->freemopay_secret_key
                );
            }

            $response = $http->post($url, $data);

            $duration = microtime(true) - $startTime;

            // Log response
            $this->logResponse($response->status(), $response->body(), $duration);

            // DETAILED LOGS FOR DEBUG
            Log::info("[FreeMoPay Client] Status Code: {$response->status()}");
            Log::info("[FreeMoPay Client] Response Headers: " . json_encode($response->headers()));
            Log::info("[FreeMoPay Client] Response Body (raw): {$response->body()}");

            // Handle response
            return $this->handleResponse($response);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("[FreeMoPay] Connection error: " . $e->getMessage());
            throw new \Exception("Connection error: {$e->getMessage()}");

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error("[FreeMoPay] Request error: " . $e->getMessage());
            throw new \Exception("Request failed: {$e->getMessage()}");

        } catch (\Exception $e) {
            Log::error("[FreeMoPay] Unexpected error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Make a GET request to FreeMoPay API
     *
     * @param string $endpoint
     * @param string|null $bearerToken
     * @param bool $useBasicAuth
     * @param int|null $timeout
     * @return array
     * @throws \Exception
     */
    public function get(
        string $endpoint,
        ?string $bearerToken = null,
        bool $useBasicAuth = false,
        ?int $timeout = null
    ): array {
        if (!$this->config || !$this->config->isConfigured()) {
            throw new \Exception('FreeMoPay service is not configured properly');
        }

        $url = $this->buildUrl($endpoint);
        $timeout = $timeout ?? $this->config->freemopay_status_check_timeout;

        // Prepare headers
        $headers = [
            'Accept' => 'application/json',
            'User-Agent' => 'Estuaire-Emploie-Backend/1.0'
        ];

        // Log request
        $this->logRequest('GET', $url, $headers);

        $startTime = microtime(true);

        try {
            $http = Http::withHeaders($headers)->timeout($timeout);

            if ($bearerToken) {
                $http = $http->withToken($bearerToken);
            } elseif ($useBasicAuth) {
                $http = $http->withBasicAuth(
                    $this->config->freemopay_app_key,
                    $this->config->freemopay_secret_key
                );
            }

            $response = $http->get($url);

            $duration = microtime(true) - $startTime;

            // Log response
            $this->logResponse($response->status(), $response->body(), $duration);

            // Handle response
            return $this->handleResponse($response);

        } catch (\Exception $e) {
            Log::error("[FreeMoPay] Request error: " . $e->getMessage());
            throw new \Exception("Request failed: {$e->getMessage()}");
        }
    }

    /**
     * Build full URL from endpoint
     *
     * @param string $endpoint
     * @return string
     */
    protected function buildUrl(string $endpoint): string
    {
        // If endpoint is already a full URL, return it
        if (str_starts_with($endpoint, 'http://') || str_starts_with($endpoint, 'https://')) {
            return $endpoint;
        }

        // Otherwise, build with base_url
        $base = rtrim($this->config->freemopay_base_url, '/');
        $endpoint = ltrim($endpoint, '/');
        return "{$base}/{$endpoint}";
    }

    /**
     * Log request details (without sensitive data)
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param array|null $data
     * @return void
     */
    protected function logRequest(string $method, string $url, array $headers, ?array $data = null): void
    {
        // Mask sensitive headers
        $safeHeaders = $headers;
        if (isset($safeHeaders['Authorization'])) {
            $authType = explode(' ', $safeHeaders['Authorization'])[0] ?? '';
            $safeHeaders['Authorization'] = "{$authType} [HIDDEN]";
        }

        Log::debug("[FreeMoPay] {$method} {$url}");
        Log::debug("[FreeMoPay] Headers: " . json_encode($safeHeaders));

        if ($data) {
            // Mask sensitive data
            $safeData = $this->maskSensitiveData($data);
            Log::debug("[FreeMoPay] Body: " . json_encode($safeData));
        }
    }

    /**
     * Mask sensitive data for logging
     *
     * @param array $data
     * @return array
     */
    protected function maskSensitiveData(array $data): array
    {
        $safeData = $data;
        $sensitiveKeys = ['secretKey', 'secret_key', 'password', 'token'];

        foreach ($sensitiveKeys as $key) {
            if (isset($safeData[$key])) {
                $safeData[$key] = '[HIDDEN]';
            }
        }

        return $safeData;
    }

    /**
     * Log response details
     *
     * @param int $statusCode
     * @param string $responseBody
     * @param float $duration
     * @return void
     */
    protected function logResponse(int $statusCode, string $responseBody, float $duration): void
    {
        // Truncate body if too long
        $bodyPreview = strlen($responseBody) > 500
            ? substr($responseBody, 0, 500) . '...'
            : $responseBody;

        Log::debug("[FreeMoPay] Response {$statusCode} in " . number_format($duration, 2) . "s");
        Log::debug("[FreeMoPay] Body: {$bodyPreview}");

        // Warning if slow request
        if ($duration > 3.0) {
            Log::warning("[FreeMoPay] Slow request: " . number_format($duration, 2) . "s");
        }
    }

    /**
     * Handle HTTP response
     *
     * @param \Illuminate\Http\Client\Response $response
     * @return array
     * @throws \Exception
     */
    protected function handleResponse($response): array
    {
        try {
            $data = $response->json();
        } catch (\Exception $e) {
            // Response is not JSON
            Log::error("[FreeMoPay] Non-JSON response: " . substr($response->body(), 0, 200));
            throw new \Exception("Invalid API response (not JSON)");
        }

        // If status code indicates error (4xx, 5xx)
        if ($response->failed()) {
            Log::error("[FreeMoPay] API Error {$response->status()}: " . json_encode($data));
            throw new \Exception("API error: {$response->status()} - " . ($data['message'] ?? 'Unknown error'));
        }

        return $data;
    }
}
