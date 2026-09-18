<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Config for the in-house face recognition service (replaces Config\CompreFace).
 * baseUrl points at the local Python embedding-extraction microservice — see
 * python-face-service/. Everything stays on 127.0.0.1; no external calls.
 */
class FaceRecognition extends BaseConfig
{
    public bool $enabled = false;
    public string $baseUrl = '';
    public string $apiKey = '';
    public float $similarityThreshold = 0.5;
    public int $timeout = 60;
    public int $connectTimeout = 10;
    public int $healthTimeout = 10;
    public int $maxEmbeddingsPerUser = 5;
    public string $modelVersion = 'insightface-buffalo_l';

    public function __construct()
    {
        parent::__construct();

        $this->enabled = filter_var(env('FaceRecognition.enabled', false), FILTER_VALIDATE_BOOL);
        $this->baseUrl = rtrim((string) env('FaceRecognition.baseUrl', 'http://127.0.0.1:8100'), '/');
        $this->apiKey = (string) env('FaceRecognition.apiKey', '');
        $this->similarityThreshold = (float) env('FaceRecognition.similarityThreshold', 0.5);
        $this->timeout = (int) env('FaceRecognition.timeout', 60);
        $this->connectTimeout = (int) env('FaceRecognition.connectTimeout', 10);
        $this->healthTimeout = (int) env('FaceRecognition.healthTimeout', 10);
        $this->maxEmbeddingsPerUser = (int) env('FaceRecognition.maxEmbeddingsPerUser', 5);
        $this->modelVersion = (string) env('FaceRecognition.modelVersion', 'insightface-buffalo_l');
    }
}
