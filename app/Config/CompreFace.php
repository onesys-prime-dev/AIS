<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class CompreFace extends BaseConfig
{
    public bool $enabled = false;
    public string $baseUrl = '';
    public string $apiKey = '';
    public float $similarityThreshold = 0.85;
    public int $timeout = 60;
    public int $connectTimeout = 10;
    public int $healthTimeout = 10;

    public function __construct()
    {
        parent::__construct();

        $this->enabled = filter_var(env('CompreFace.enabled', false), FILTER_VALIDATE_BOOL);
        $this->baseUrl = rtrim((string) env('CompreFace.baseUrl', 'http://localhost:8000'), '/');
        $this->apiKey = (string) env('CompreFace.apiKey', '');
        $this->similarityThreshold = (float) env('CompreFace.similarityThreshold', 0.85);
        $this->timeout = (int) env('CompreFace.timeout', 60);
        $this->connectTimeout = (int) env('CompreFace.connectTimeout', 10);
        $this->healthTimeout = (int) env('CompreFace.healthTimeout', 10);
    }
}
