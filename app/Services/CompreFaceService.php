<?php

namespace App\Services;

use Config\CompreFace;
use RuntimeException;

class CompreFaceService
{
    private const HEALTH_CACHE_KEY = 'compreface.health_status';

    public function __construct(
        private readonly CompreFace $config = new CompreFace(),
    ) {
    }

    public function recognize(string $imageDataUrl): ?array
    {
        $this->assertConfigured();

        $payload = $this->sendMultipartRequest('/api/v1/recognition/recognize', [
            'file' => $imageDataUrl,
        ]);

        if (! is_array($payload)) {
            throw new RuntimeException('CompreFace returned an invalid response body.');
        }

        $subjects = $payload['result'][0]['subjects'] ?? null;
        if (! is_array($subjects) || $subjects === []) {
            return null;
        }

        $match = $subjects[0];
        $similarity = (float) ($match['similarity'] ?? 0);

        if ($similarity < $this->config->similarityThreshold) {
            return null;
        }

        return [
            'subject' => (string) ($match['subject'] ?? ''),
            'similarity' => $similarity,
            'raw' => $payload,
        ];
    }

    public function enrollFace(string $imageDataUrl, string $subject): array
    {
        $subject = trim($subject);
        if ($subject === '') {
            throw new RuntimeException('CompreFace subject is required.');
        }

        $payload = $this->sendMultipartRequest('/api/v1/recognition/faces?subject=' . rawurlencode($subject), [
            'file' => $imageDataUrl,
        ]);

        if (! is_array($payload)) {
            throw new RuntimeException('CompreFace returned an invalid enrollment response body.');
        }

        return $payload;
    }

    public function getHealthStatus(): array
    {
        $cachedStatus = $this->getCachedHealthStatus();
        if ($cachedStatus !== null) {
            return $cachedStatus;
        }

        $status = $this->probeHealthStatus();
        $this->storeCachedHealthStatus($status);

        return $status;
    }

    private function probeHealthStatus(): array
    {
        if ($this->config->baseUrl === '') {
            return [
                'ok' => false,
                'message' => 'CompreFace base URL is not configured.',
            ];
        }

        if ($this->config->apiKey === '') {
            return [
                'ok' => false,
                'message' => 'CompreFace API key is not configured.',
            ];
        }

        $curl = curl_init($this->config->baseUrl);
        if ($curl === false) {
            return [
                'ok' => false,
                'message' => 'Unable to initialize CompreFace health check.',
            ];
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => $this->config->healthTimeout,
            CURLOPT_CONNECTTIMEOUT => $this->config->connectTimeout,
        ]);

        curl_exec($curl);
        $error = curl_error($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($error !== '') {
            return [
                'ok' => false,
                'message' => 'CompreFace is unreachable: ' . $error,
            ];
        }

        if ($statusCode < 200 || $statusCode >= 500) {
            return [
                'ok' => false,
                'message' => 'CompreFace health check returned HTTP ' . $statusCode . '.',
            ];
        }

        return [
            'ok' => true,
            'message' => 'CompreFace is reachable.',
        ];
    }

    private function getCachedHealthStatus(): ?array
    {
        $cache = service('cache');
        if (! is_object($cache) || ! method_exists($cache, 'get')) {
            return null;
        }

        $cachedStatus = $cache->get(self::HEALTH_CACHE_KEY);

        return is_array($cachedStatus) ? $cachedStatus : null;
    }

    private function storeCachedHealthStatus(array $status): void
    {
        $cache = service('cache');
        if (! is_object($cache) || ! method_exists($cache, 'save')) {
            return;
        }

        $cache->save(self::HEALTH_CACHE_KEY, $status, 30);
    }

    private function sendMultipartRequest(string $path, array $fields): ?array
    {
        $tempFile = null;

        try {
            $multipartFields = [];

            foreach ($fields as $key => $value) {
                if ($key === 'file') {
                    $tempFile = $this->createTemporaryImageFile((string) $value);
                    $multipartFields[$key] = curl_file_create($tempFile, 'image/jpeg', basename($tempFile));
                    continue;
                }

                $multipartFields[$key] = (string) $value;
            }

            $curl = curl_init(rtrim($this->config->baseUrl, '/') . $path);
            if ($curl === false) {
                throw new RuntimeException('Unable to initialize the CompreFace request.');
            }

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'x-api-key: ' . $this->config->apiKey,
                    'Accept: application/json',
                ],
                CURLOPT_POSTFIELDS => $multipartFields,
                CURLOPT_TIMEOUT => $this->config->timeout,
                CURLOPT_CONNECTTIMEOUT => $this->config->connectTimeout,
            ]);

            $body = curl_exec($curl);
            if ($body === false) {
                $error = curl_error($curl) ?: 'Unknown cURL error.';
                curl_close($curl);
                throw new RuntimeException('CompreFace request failed: ' . $error);
            }

            $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($statusCode >= 400) {
                throw new RuntimeException('CompreFace request failed with status ' . $statusCode . '. Response: ' . $body);
            }

            $payload = json_decode($body, true);
            if ($payload === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('CompreFace returned malformed JSON.');
            }

            return $payload;
        } finally {
            if ($tempFile !== null && is_file($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    private function createTemporaryImageFile(string $imageDataUrl): string
    {
        $imageDataUrl = trim($imageDataUrl);

        if ($imageDataUrl === '') {
            throw new RuntimeException('Face image is empty.');
        }

        if (str_contains($imageDataUrl, ',')) {
            [, $imageDataUrl] = explode(',', $imageDataUrl, 2);
        }

        $imageDataUrl = preg_replace('/\s+/', '', $imageDataUrl) ?? '';

        if ($imageDataUrl === '') {
            throw new RuntimeException('Face image could not be decoded.');
        }

        $decoded = base64_decode($imageDataUrl, true);
        if ($decoded === false) {
            throw new RuntimeException('Face image payload is not valid base64.');
        }

        $tempBase = tempnam(sys_get_temp_dir(), 'face_');
        if ($tempBase === false) {
            throw new RuntimeException('Unable to prepare a temporary image file.');
        }

        $tempFile = $tempBase . '.jpg';

        if (! @rename($tempBase, $tempFile)) {
            @unlink($tempBase);
            throw new RuntimeException('Unable to prepare a temporary JPEG file.');
        }

        if (file_put_contents($tempFile, $decoded) === false) {
            throw new RuntimeException('Unable to write the temporary face image.');
        }

        return $tempFile;
    }

    private function assertConfigured(): void
    {
        if ($this->config->apiKey === '') {
            throw new RuntimeException('CompreFace API key is not configured.');
        }
    }
}
