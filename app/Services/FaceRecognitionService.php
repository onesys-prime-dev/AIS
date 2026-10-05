<?php

namespace App\Services;

use App\Models\FaceEmbeddingModel;
use Config\FaceRecognition;
use RuntimeException;

/**
 * In-house replacement for CompreFaceService. Same public contract
 * (recognize/enrollFace/getHealthStatus) so FaceAuthController,
 * SettingsController, and FaceVerified only needed a reference swap.
 *
 * The Python microservice (python-face-service/) is a stateless image->embedding
 * extractor only — it never sees MySQL and never decides identity. All
 * enrollment storage and match-decision logic lives here, in PHP, so this app
 * is the sole source of truth for enrolled faces (unlike CompreFace, which
 * held everything in its own external store).
 */
class FaceRecognitionService
{
    private const HEALTH_CACHE_KEY = 'facerecognition.health_status';

    public function __construct(
        private readonly FaceRecognition $config = new FaceRecognition(),
        private readonly FaceEmbeddingModel $embeddings = new FaceEmbeddingModel(),
    ) {
    }

    /**
     * Extracts an embedding for the captured frame and finds the best-matching
     * enrolled user across ALL enrolled embeddings (identity is unknown at
     * verify time — same behavior as CompreFace's "recognize" call).
     *
     * @return array{subject: string, similarity: float, raw: array}|null
     */
    public function recognize(string $imageDataUrl): ?array
    {
        $this->assertConfigured();

        $embedding = $this->extractEmbedding($imageDataUrl);

        $candidates = $this->embeddings->getAll();

        $bestUserId = null;
        $bestSimilarity = -1.0;

        foreach ($candidates as $row) {
            if (empty($row['embedding'])) {
                continue;
            }

            $similarity = $this->cosineSimilarity($embedding['embedding'], $row['embedding']);

            if ($similarity > $bestSimilarity) {
                $bestSimilarity = $similarity;
                $bestUserId = (int) $row['user_id'];
            }
        }

        if ($bestUserId === null || $bestSimilarity < $this->config->similarityThreshold) {
            return null;
        }

        return [
            'subject' => (string) $bestUserId,
            'similarity' => $bestSimilarity,
            'raw' => $embedding,
        ];
    }

    /**
     * Extracts an embedding and stores it against the given user, evicting the
     * oldest stored embedding first if the per-user cap is already reached.
     */
    public function enrollFace(string $imageDataUrl, string $subject): array
    {
        $this->assertConfigured();

        $subject = trim($subject);
        if ($subject === '') {
            throw new RuntimeException('A user is required to enroll a face.');
        }

        $userId = (int) $subject;

        $embedding = $this->extractEmbedding($imageDataUrl);

        if ($this->embeddings->countForUser($userId) >= $this->config->maxEmbeddingsPerUser) {
            $this->embeddings->deleteOldestForUser($userId);
        }

        $this->embeddings->insert([
            'user_id' => $userId,
            'embedding' => json_encode($embedding['embedding']),
            'model_version' => (string) ($embedding['model_version'] ?? $this->config->modelVersion),
            'quality_score' => isset($embedding['quality_score']) ? (float) $embedding['quality_score'] : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'status' => 'success',
            'model_version' => $embedding['model_version'] ?? $this->config->modelVersion,
            'embedding_count' => $this->embeddings->countForUser($userId),
        ];
    }

    /**
     * Enrollment summary for the settings page — sample count, cap, and the
     * most recent capture's timestamp/model. No embedding floats included.
     */
    public function getEnrollmentStatus(int $userId): array
    {
        $rows = $this->embeddings->getMetaForUser($userId);

        if ($rows === []) {
            return [
                'enrolled' => false,
                'count' => 0,
                'max' => $this->config->maxEmbeddingsPerUser,
                'latest_at' => null,
                'model_version' => null,
            ];
        }

        $latest = end($rows);

        return [
            'enrolled' => true,
            'count' => count($rows),
            'max' => $this->config->maxEmbeddingsPerUser,
            'latest_at' => $latest['created_at'],
            'model_version' => $latest['model_version'],
        ];
    }

    /**
     * Wipes all enrolled embeddings for a user — backs the "Remove enrolled
     * face" action in the admin enrollment modal.
     */
    public function clearEnrollment(int $userId): bool
    {
        return $this->embeddings->deleteAllForUser($userId);
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

    /**
     * Calls the Python /embed endpoint. Returns the raw decoded payload
     * (embedding, model_version, quality_score, face_count).
     */
    private function extractEmbedding(string $imageDataUrl): array
    {
        $imageDataUrl = trim($imageDataUrl);
        if ($imageDataUrl === '') {
            throw new RuntimeException('Face image is empty.');
        }

        $curl = curl_init(rtrim($this->config->baseUrl, '/') . '/embed');
        if ($curl === false) {
            throw new RuntimeException('Unable to initialize the face recognition request.');
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'X-API-Key: ' . $this->config->apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode(['image' => $imageDataUrl]),
            CURLOPT_TIMEOUT => $this->config->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->config->connectTimeout,
        ]);

        $body = curl_exec($curl);
        if ($body === false) {
            $error = curl_error($curl) ?: 'Unknown cURL error.';
            curl_close($curl);
            throw new RuntimeException('Face recognition service request failed: ' . $error);
        }

        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $payload = json_decode((string) $body, true);

        if ($statusCode === 422 && is_array($payload) && isset($payload['error'])) {
            throw new RuntimeException(match ($payload['error']) {
                'no_face_detected' => 'No face was detected in the captured image. Please try again.',
                'multiple_faces_detected' => 'More than one face was detected. Please make sure only you are in frame.',
                default => 'Unable to process the captured face image.',
            });
        }

        if ($statusCode >= 400) {
            throw new RuntimeException('Face recognition service request failed with status ' . $statusCode . '.');
        }

        if (!is_array($payload) || !isset($payload['embedding']) || !is_array($payload['embedding'])) {
            throw new RuntimeException('Face recognition service returned an invalid response.');
        }

        return $payload;
    }

    private function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b) || $a === []) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $valueA) {
            $valueB = $b[$i];
            $dot += $valueA * $valueB;
            $normA += $valueA * $valueA;
            $normB += $valueB * $valueB;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }

    private function probeHealthStatus(): array
    {
        if ($this->config->baseUrl === '') {
            return [
                'ok' => false,
                'message' => 'Face recognition service URL is not configured.',
            ];
        }

        $curl = curl_init(rtrim($this->config->baseUrl, '/') . '/health');
        if ($curl === false) {
            return [
                'ok' => false,
                'message' => 'Unable to initialize the face recognition health check.',
            ];
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config->healthTimeout,
            CURLOPT_CONNECTTIMEOUT => $this->config->connectTimeout,
        ]);

        $body = curl_exec($curl);
        $error = curl_error($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($error !== '') {
            return [
                'ok' => false,
                'message' => 'Face recognition service is unreachable: ' . $error,
            ];
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            return [
                'ok' => false,
                'message' => 'Face recognition service health check returned HTTP ' . $statusCode . '.',
            ];
        }

        $payload = json_decode((string) $body, true);
        $modelVersion = is_array($payload) ? ($payload['model_version'] ?? '') : '';

        return [
            'ok' => true,
            'message' => 'Face recognition service is reachable.' . ($modelVersion !== '' ? ' Model: ' . $modelVersion : ''),
        ];
    }

    private function getCachedHealthStatus(): ?array
    {
        $cache = service('cache');
        if (!is_object($cache) || !method_exists($cache, 'get')) {
            return null;
        }

        $cachedStatus = $cache->get(self::HEALTH_CACHE_KEY);

        return is_array($cachedStatus) ? $cachedStatus : null;
    }

    private function storeCachedHealthStatus(array $status): void
    {
        $cache = service('cache');
        if (!is_object($cache) || !method_exists($cache, 'save')) {
            return;
        }

        $cache->save(self::HEALTH_CACHE_KEY, $status, 30);
    }

    private function assertConfigured(): void
    {
        if ($this->config->apiKey === '') {
            throw new RuntimeException('Face recognition API key is not configured.');
        }
    }
}
