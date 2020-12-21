<?php

namespace shvaykovski\ComposerUpdates\Objects;

class ReportRowObject
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $version;

    /**
     * @var string|null
     */
    public $description;

    /**
     * @var string
     */
    public $composerRequirement;

    /**
     * @var string
     */
    public $currentVersion;

    /**
     * @var string
     */
    public $latestVersion;

    /**
     * @var array
     */
    public $upgradeSteps;

    /**
     * @var string|null
     */
    public $abandoned;

    /**
     * @var string|null
     */
    public $semanticVersioning;
}
