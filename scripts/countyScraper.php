#!/usr/bin/env php
<?php

include_once('vendor/autoload.php');

use Balsama\CountyScraper;

$countyScraper = new CountyScraper();
$countiesByState = $countyScraper->getCounties();

$countiesJson = json_encode($countiesByState, JSON_PRETTY_PRINT);
file_put_contents('data/counties_by_state.json', $countiesJson);

$headers = [
    'County',
    'State',
    'Population',
    'Area',
    'Density',
];

$allCounties = [];
foreach ($countiesByState as $statesCounties) {
    $allCounties = array_merge($allCounties, $statesCounties);
}
$allCountiesJson = json_encode($allCounties, JSON_PRETTY_PRINT);
file_put_contents('data/counties.json', $allCountiesJson);

$fp = fopen('data/counties.csv', 'w');
fputcsv($fp, $headers);
foreach ($allCounties as $county) {
    fputcsv($fp, $county);
}
fclose($fp);
