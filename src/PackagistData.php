<?php

namespace shvaykovski\ComposerUpdates;

class PackagistData
{
    protected const KEY_PACKAGE = 'package';
    protected const KEY_VERSIONS = 'versions';
    protected const KEY_VERSION = 'version';
    protected const KEY_VERSION_NORMALIZED = 'version_normalized';
    protected const KEY_ABANDONED = 'abandoned';

    /**
     * @var array|null
     */
    protected $versions;

    /**
     * @var string
     */
    protected $packagistDataUrl = 'https://packagist.org/packages/%s.json';

    /**
     * @var string
     */
    protected $devTagsRegex = '/alpha|beta|dev|dev|dev|master|rc|untagged|wip/i';

    /**
     * @var CacheHelper|null
     */
    protected $cache;

    /**
     * PackagistData constructor.
     *
     * @param string $packageName
     * @param bool $useCache
     */
    public function __construct(string $packageName, bool $useCache = true)
    {
        if ($useCache) {
            $this->cache = new CacheHelper();
        }

        $packagistUrl = sprintf($this->packagistDataUrl, $packageName);
        $this->versions = $this->getVersions($packagistUrl);
    }

    /**
     * @param bool $useNormalized
     *
     * @return string|null
     */
    public function getLatestVersion(bool $useNormalized = false): ?string
    {
        if (count($this->versions) > 0) {
            $allVersions = $this->getAllVersions($useNormalized);
            return end($allVersions);
        }

        return null;
    }

    /**
     * @param string $currentVersion
     *
     * @return string|null
     */
    public function getCurrentNormalizedVersion(string $currentVersion): ?string
    {
        $allVersions = $this->getAllVersions();
        $currentVersionPosition = array_search($currentVersion, $allVersions);
        $allNormalizedVersions = $this->getAllVersions(true);

        if ($currentVersionPosition && isset($allNormalizedVersions[$currentVersionPosition])) {
            return $allNormalizedVersions[$currentVersionPosition];
        }

        return null;
    }

    /**
     * @param string $currentVersion
     *
     * @return array
     */
    public function getUpgradeStepsToLatest(string $currentVersion): array
    {
        $steps = [];
        $allVersions = $this->getAllVersions();
        $currentVersionPosition = array_search($currentVersion, $allVersions);

        if ($currentVersionPosition) {
            $steps = array_slice($allVersions, $currentVersionPosition);
        }

        if (empty($steps)) {
            $steps = [$currentVersion] + $allVersions;
        }

        return $steps;
    }

    /**
     * @param bool $useNormalized
     *
     * @return array
     */
    public function getAllVersions(bool $useNormalized = false): array
    {
        if ($useNormalized) {
            return array_column($this->versions, self::KEY_VERSION_NORMALIZED);
        }

        return array_column($this->versions, self::KEY_VERSION);
    }

    /**
     * @return string|null
     */
    public function abandonedData(): ?string
    {
        if (count($this->versions) > 0) {
            if (isset($this->versions[0][self::KEY_ABANDONED])) {
                $value = $this->versions[0][self::KEY_ABANDONED];
                return ($value == 'true') ? '': $value;
            }
        }

        return null;
    }

    /**
     * @param string $url
     *
     * @return array
     */
    protected function getVersions(string $url): array
    {
        $versionsAll = [];

        $dataJson = $this->getPackagistData($url);
        if ($dataJson) {
            $data = json_decode($dataJson, true);
            if (!empty($data[self::KEY_PACKAGE][self::KEY_VERSIONS])) {
                $versionsAll = $data[self::KEY_PACKAGE][self::KEY_VERSIONS];
            }
        }

        $versions = $this->filterDevVersions($versionsAll);
        $versions = (!empty($versions)) ? $versions : $versionsAll;

        usort($versions, function ($a, $b) {
            return version_compare($a[self::KEY_VERSION_NORMALIZED], $b[self::KEY_VERSION_NORMALIZED], '>');
        });

        return $versions;
    }

    /**
     * @param string $url
     *
     * @return string|null
     */
    protected function getPackagistData(string $url): ?string
    {
        if ($this->cache) {
            $dataJson = $this->cache->get($url);

            if ($dataJson !== null) {
                return $dataJson;
            }
        }

        $dataJson = file_get_contents($url);
        if ($dataJson) {
            $this->cache->set($url, $dataJson);
            return $dataJson;
        }

        return null;
    }

    /**
     * @param array $versions
     *
     * @return array
     */
    protected function filterDevVersions(array $versions): array
    {
        return array_filter($versions, function ($item) {
            return !preg_match($this->devTagsRegex, $item[self::KEY_VERSION_NORMALIZED]);
        });
    }
}
