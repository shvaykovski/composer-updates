<?php

use shvaykovski\ComposerUpdates\ComposerReport;

require 'vendor/autoload.php';

if (count($argv) < 2) {
    exit("No path for composer.json was provided." . PHP_EOL);
}

$currentDate = date('d.m.Y H:i:s', time());
$projectFolder = basename($argv[1]);

$composerReport = new ComposerReport($argv[1]);
$report = $composerReport->getReport();

$tableHeaderHtml = <<<TABLEHEADER
        <table class="table table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Package name</th>
                    <th>Version from Composer</th>
                    <th>The real version</th>
                    <th>The latest version</th>
                    <th>Upgrade steps</th>
                    <th>Is abandoned</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>

TABLEHEADER;

$tableFooterHtml = <<<TABLEFOOTER
            </tbody>
        </table>

TABLEFOOTER;

$rowHtml = <<<ROW
            <tr class="%s">
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
            </tr>

ROW;

echo <<<HEAD
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <title>Composer packages updates report</title>
        <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
        <style>
            .container-fluid {margin: 15px 0;}
            .abandoned {
                background-image: linear-gradient(45deg, rgba(255, 0, 0, 0.1) 25%, rgba(255, 255, 255, 0.1) 25%, rgba(255, 255, 255, 0.1) 50%, rgba(255, 0, 0, 0.1) 50%, rgba(255, 0, 0, 0.1) 75%, rgba(255, 255, 255, 0.1) 75%, rgba(255, 255, 255, 0.1) 100%);
                background-size: 56.57px 56.57px;
            }
            .major {background-color: #e3a7a7;}
            .minor {background-color: #fffbc7;}
            .patch {background-color: #95be85;}
        </style>
    </head>
    <body>
    <div class="container-fluid">
        <header class="blog-header py-3">
            <div class="row flex-nowrap justify-content-between align-items-center">
                <div class="col-12 text-center">
                    <h1>Composer packages updates report</h1>
                </div>
            </div>
        </header>
        <main role="main">

HEAD;

echo "<h2>Required packages</h2>";
echo $tableHeaderHtml;
foreach ($report->require as $row) {
    $class =  ($row->abandoned !== null) ? 'abandoned' : $row->semanticVersioning;

    echo sprintf($rowHtml,
        $class,
        $row->name,
        $row->composerRequirement,
        $row->currentVersion,
        $row->latestVersion,
        upgradeStepsHelper($row->upgradeSteps),
        abandonedDataHelper($row->abandoned),
        $row->description
    );
}
echo $tableFooterHtml;

echo "<h2>Dev packages</h2>";
echo $tableHeaderHtml;
foreach ($report->requireDev as $row) {
    $class =  ($row->abandoned !== null) ? 'abandoned' : $row->semanticVersioning;

    echo sprintf($rowHtml,
        $class,
        $row->name,
        $row->composerRequirement,
        $row->currentVersion,
        $row->latestVersion,
        upgradeStepsHelper($row->upgradeSteps),
        abandonedDataHelper($row->abandoned),
        $row->description
    );
}
echo $tableFooterHtml;

echo <<<BOTTOM
            </main>

            <footer class="footer mt-auto py-3">
              <div class="container">
                <span class="text-muted">Composer packages updates report for "$projectFolder" folder. Generated $currentDate</span>
              </div>
            </footer>
        </div>
    </body>
</html>
BOTTOM;

function abandonedDataHelper(?string $abandoned): string
{
    if ($abandoned === null) {
        return 'no';
    } else {
        if (empty($abandoned)) {
            return 'No replacement provided';
        } else {
            return 'Replacement - ' . $abandoned;
        }
    }
}

function upgradeStepsHelper(array $upgradeSteps): string
{
    return implode(' -> ', $upgradeSteps);
}
