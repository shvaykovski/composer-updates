<?php

use shvaykovski\ComposerUpdates\ComposerReport;

error_reporting(E_ERROR | E_PARSE);
require 'vendor/autoload.php';

if (count($argv) < 3) {
    exit("Pls run like in this example: /usr/bin/php -f compare.php reports/report1.html reports/report2.html" . PHP_EOL);
}

$currentDate = date('d.m.Y H:i:s', time());
$report1 = new DOMDocument();
$report1->loadHTMLFile($argv[1]);
$report2 = new DOMDocument();
$report2->loadHTMLFile($argv[2]);

$reportXpath1 = new DOMXpath($report1);
$reportXpath2 = new DOMXpath($report2);

$tables1 = $reportXpath1->query('//table');
$tables2 = $reportXpath2->query('//table');

//Required packages
$requiredPackages1 = [];
$requiredPackages2 = [];
$tds1 = $reportXpath1->query('.//tbody/tr/td[position()=1]', $tables1[0]);
foreach ($tds1 as $td) {
    $requiredPackages1[] = $td->nodeValue;
}

$tds2 = $reportXpath1->query('.//tbody/tr/td[position()=1]', $tables2[0]);
foreach ($tds2 as $td) {
    $requiredPackages2[] = $td->nodeValue;
}

//Dev packages
$devPackages1 = [];
$devPackages2 = [];
$tds1 = $reportXpath1->query('.//tbody/tr/td[position()=1]', $tables1[1]);
foreach ($tds1 as $td) {
    $devPackages1[] = $td->nodeValue;
}

$tds2 = $reportXpath1->query('.//tbody/tr/td[position()=1]', $tables2[1]);
foreach ($tds2 as $td) {
    $devPackages2[] = $td->nodeValue;
}

$tableHeaderHtml = <<<TABLEHEADER
        <table class="table table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Package name</th>
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
$packages = array_diff($requiredPackages1, $requiredPackages2);
foreach ($packages as $item) {
    $class =  '';

    echo sprintf($rowHtml,
        $class,
        $item
    );
}
echo $tableFooterHtml;

echo "<h2>Dev packages</h2>";
echo $tableHeaderHtml;
$packages = array_diff($devPackages1, $devPackages2);
foreach ($packages as $item) {
    $class =  '';

    echo sprintf($rowHtml,
        $class,
        $item
    );
}
echo $tableFooterHtml;

echo <<<BOTTOM
            </main>

            <footer class="footer mt-auto py-3">
              <div class="container">
                <span class="text-muted">Generated $currentDate</span>
              </div>
            </footer>
        </div>
    </body>
</html>
BOTTOM;
