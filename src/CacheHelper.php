<?php

namespace shvaykovski\ComposerUpdates;

class CacheHelper
{
    protected const TTL = 3600;

    /**
     * @var string
     */
    protected $cacheFolder = '.cache';

    /**
     * Cache constructor.
     */
    public function __construct()
    {
        $this->cacheFolder = sprintf('%s/%s', realpath(__DIR__ . '/..'), $this->cacheFolder);

        if (!is_dir($this->cacheFolder)) {
            mkdir($this->cacheFolder);
        }
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function get(string $key): ?string
    {
        $filePath = $this->getCachedFilePath($key);
        if (!file_exists($filePath)) {
            return null;
        }

        $ttl = $this->getFileLifeTime($filePath);
        if ($ttl > self::TTL) {
            unlink($filePath);
            return null;
        }

        $content = file_get_contents($filePath);

        return $content ?: null;
    }

    /**
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function set(string $key, string $value): bool
    {
        $filePath = $this->getCachedFilePath($key);
        $result = file_put_contents($filePath, $value);

        return ($result !== false);
    }

    /**
     * @param string $key
     * @return string
     */
    protected function getCachedFilePath(string $key): string
    {
        return sprintf('%s/%s.json', $this->cacheFolder, md5($key));
    }

    /**
     * @param string $path
     * @return int
     */
    private function getFileLifeTime(string $path): int
    {
        return time() - filemtime($path);
    }
}
