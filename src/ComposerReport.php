<?php

namespace shvaykovski\ComposerUpdates;

use shvaykovski\ComposerUpdates\Objects\ReportObject;
use shvaykovski\ComposerUpdates\Objects\ReportRowObject;

class ComposerReport
{
    public const KEY_REQUIRE = 'require';
    public const KEY_REQUIRE_DEV = 'require-dev';

    protected const KEY_NAME = 'name';
    protected const KEY_VERSION = 'version';
    protected const KEY_DESCRIPTION = 'description';

    protected const KEY_PACKAGES = 'packages';
    protected const KEY_PACKAGES_DEV = 'packages-dev';

    protected const SEMANTIC_VERSIONING_MAJOR = 'major';
    protected const SEMANTIC_VERSIONING_MINOR = 'minor';
    protected const SEMANTIC_VERSIONING_PATCH = 'patch';
    protected const SEMANTIC_VERSIONING_UNKNOWN = 'unknown';

    /**
     * @var array
     */
    protected $composerJsonData = [];

    /**
     * @var array
     */
    protected $composerLockData = [];

    /**
     * ComposerData constructor.
     *
     * @param string $composerPath
     */
    public function __construct(string $composerPath)
    {
        $composerJsonPath = $composerPath . '/composer.json';

        if (!file_exists($composerJsonPath)) {
            exit("No composer.json was found." . PHP_EOL);
        }

        $composerLockPath = $composerPath . '/composer.lock';

        if (!file_exists($composerLockPath)) {
            exit("No composer.lock was found." . PHP_EOL);
        }

        $this->composerJsonData = $this->readJsonData($composerJsonPath);
        $this->composerLockData = $this->readJsonData($composerLockPath);
    }

    /**
     * @return ReportObject
     */
    public function getReport(): ReportObject
    {
        $report = new ReportObject();

        $requiredPackages = $this->getRequirePackages();
        $requiredDevPackages = $this->getRequirePackages(true);
        $lockData = $this->getLockPackages();

        $report->require = $this->generateBLock($requiredPackages, $lockData);
        $report->requireDev = $this->generateBLock($requiredDevPackages, $lockData);

        return $report;
    }

    /**
     * @param array $packages
     * @param array $lockData
     *
     * @return ReportRowObject[]
     */
    protected function generateBLock(array $packages, array $lockData): array
    {
        $report = [];

        foreach ($packages as $packageName => $packageRequiredVersion) {
            if (isset($lockData[$packageName])) {
                $lockRowData = $lockData[$packageName];
                $packagistData = new PackagistData($packageName);

                $currentVersion = $lockRowData[static::KEY_VERSION];
                $latestVersion = $packagistData->getLatestVersion();
                $versionsCheck = version_compare($currentVersion, $latestVersion, '>=');

                if ($versionsCheck) {
                    continue;
                }

                $upgradeSteps = $packagistData->getUpgradeStepsToLatest($lockRowData[static::KEY_VERSION]);

                $row = new ReportRowObject();
                $row->name = $packageName;
                $row->composerRequirement = $packageRequiredVersion;
                $row->currentVersion = $currentVersion;
                $row->latestVersion = $latestVersion;
                $row->upgradeSteps = $upgradeSteps;
                $row->description = $lockRowData[static::KEY_DESCRIPTION];
                $row->abandoned = $packagistData->abandonedData();
                $row->semanticVersioning = $this->getSemanticVersioning($packagistData, $currentVersion);

                $report[] = $row;
            }
        }

        return $report;
    }

    /**
     * @param PackagistData $packagistData
     * @param string $currentVersion
     *
     * @return string
     */
    protected function getSemanticVersioning(PackagistData $packagistData, string $currentVersion): string
    {
        $currentVersion = $packagistData->getCurrentNormalizedVersion($currentVersion);
        $latestVersion = $packagistData->getLatestVersion(true);

        // For dev to major release cases
        if (!$currentVersion || !$latestVersion) {
            return static::SEMANTIC_VERSIONING_MAJOR;
        }

        $currentVersionArr = explode('.', $currentVersion);
        $latestVersionArr = explode('.', $latestVersion);

        if (count($currentVersionArr) === 2 && count($latestVersionArr) === 2
            && (int)$currentVersionArr[0] < (int)$latestVersionArr[0]
        ) {
            return static::SEMANTIC_VERSIONING_MAJOR;
        }

        if (count($currentVersionArr) < 3 || count($latestVersionArr) < 3) {
            return static::SEMANTIC_VERSIONING_UNKNOWN;
        }

        if ((int)$currentVersionArr[0] < (int)$latestVersionArr[0]) {
            return static::SEMANTIC_VERSIONING_MAJOR;
        }

        if ((int)$currentVersionArr[1] < (int)$latestVersionArr[1]) {
            return static::SEMANTIC_VERSIONING_MINOR;
        }

        return static::SEMANTIC_VERSIONING_PATCH;
    }

    /**
     * @return array
     */
    protected function getLockPackages(): array
    {
        $packages = [];

        if (isset($this->composerLockData[static::KEY_PACKAGES])) {
            $packages += $this->transformData($this->composerLockData[static::KEY_PACKAGES]);
        }

        if (isset($this->composerLockData[ static::KEY_PACKAGES_DEV])) {
            $packages += $this->transformData($this->composerLockData[static::KEY_PACKAGES_DEV]);
        }

        return $packages;
    }

    /**
     * @param bool $isDev
     *
     * @return array
     */
    protected function getRequirePackages(bool $isDev = false): array
    {
        $key = $isDev ? static::KEY_REQUIRE_DEV : static::KEY_REQUIRE;

        if (isset($this->composerJsonData[$key])) {
            return $this->composerJsonData[$key];
        }

        return [];
    }

    /**
     * Fetch only a name as a key and a version and a description as a value.
     *
     * @param array $data
     *
     * @return array
     */
    protected function transformData(array $data): array
    {
        $out = [];

        foreach ($data as $item) {
            $out[$item[static::KEY_NAME]] = [
                static::KEY_VERSION => $item[static::KEY_VERSION],
                static::KEY_DESCRIPTION => isset($item[static::KEY_DESCRIPTION]) ? $item[static::KEY_DESCRIPTION] : null,
            ];
        }

        return $out;
    }

    /**
     * @param string $filePath
     *
     * @return array
     */
    protected function readJsonData(string $filePath): array
    {
        $dataJson = file_get_contents($filePath);
        if ($dataJson) {
            return json_decode($dataJson, true);
        }

        return [];
    }
}
