<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

/**
 * CDN Service
 *
 * Handles CDN operations for static assets and translation exports
 * Supports multiple CDN providers (CloudFront, Cloudflare, etc.)
 *
 * @author Syed Asad
 */
class CdnService
{
    /**
     * CDN configuration
     */
    private array $config;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->config = Config::get('cdn', []);
    }

    /**
     * Generate CDN URL for asset
     */
    public function assetUrl(string $path): string
    {
        if (!$this->isEnabled()) {
            return url($path);
        }

        $baseUrl = rtrim($this->config['base_url'], '/');
        $path = ltrim($path, '/');

        return sprintf('%s/%s', $baseUrl, $path);
    }

    /**
     * Generate CDN URL for translation export
     */
    public function translationExportUrl(string $locale, array $params = []): string
    {
        $path = sprintf('exports/%s.json', $locale);

        if (!empty($params)) {
            $path .= '?' . http_build_query($params);
        }

        return $this->assetUrl($path);
    }

    /**
     * Upload file to CDN
     */
    public function uploadFile(string $localPath, string $remotePath): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            $disk = Storage::disk($this->config['disk'] ?? 'cdn');
            $content = file_get_contents($localPath);

            return $disk->put($remotePath, $content);
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Cache translation export to CDN
     */
    public function cacheTranslationExport(string $locale, array $data): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $path = sprintf('exports/%s.json', $locale);

        try {
            $disk = Storage::disk($this->config['disk'] ?? 'cdn');
            return $disk->put($path, $content);
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Invalidate CDN cache for path
     */
    public function invalidateCache(array $paths): bool
    {
        if (!$this->isEnabled() || !isset($this->config['invalidation_endpoint'])) {
            return false;
        }

        // Implementation depends on CDN provider
        // Example for CloudFront/Cloudflare
        try {
            // Add your CDN invalidation logic here
            return true;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Check if CDN is enabled
     */
    public function isEnabled(): bool
    {
        return !empty($this->config['enabled']) && !empty($this->config['base_url']);
    }

    /**
     * Get CDN configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
