<?php

/**
 * Draw prognosis as function of time
 * Call:   include Plot.form.php
 * @package Runalyze\Plugins\Panels
 */

use Runalyze\Activity\Distance;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Plugins\Prognose\PrognoseTrendDataProvider;
use Runalyze\Util\LocalTime;

/**
 * @var list<array{time: int, id: int, official_time: float}> $raceResultsData
 * @var Account $account
 * @var int $runningSportId
 * @var PrognoseTrendDataProvider $trendDataProvider
 */
$WKplugin ??= null;

$distance ??= 10;

$DataFailed = false;
$Prognosis = [];
$PrognosisWithBasicEndurance = [];
$BasicEndurance = [];
$Results = [];

$trendData = $trendDataProvider->getSeries($account, $runningSportId, (float) $distance);

if ($trendData['has_history']) {
    $Prognosis = $trendData['prognosis'];
    $PrognosisWithBasicEndurance = $trendData['prognosis_with_basic_endurance'];

        foreach ($raceResultsData as $dat) {
            if ($WKplugin === null || ! $WKplugin->isFunCompetition($dat['id'])) {
                $Results[new LocalTime($dat['time'])->toServerTimestamp() . '000'] = $dat['official_time'] * 1000;
            }
        }
} else {
    $DataFailed = true;
}

$Plot = new Plot('formverlauf_' . str_replace('.', '_', $distance), 800, 450);

$Plot->Data[] = [
    'label' => __('Prognosis'),
    'color' => '#880000',
    'data' => $Prognosis,
    'lines' => [
        'show' => true,
    ],
    'points' => [
        'show' => false,
    ],
    'curvedLines' => [
        'apply' => false,
    ],
];
$Plot->Data[] = [
    'label' => __('Prognosis with marathon shape'),
    'color' => '#000088',
    'data' => $PrognosisWithBasicEndurance,
    'lines' => [
        'show' => true,
    ],
    'points' => [
        'show' => false,
    ],
    'curvedLines' => [
        'apply' => false,
    ],
];
$Plot->Data[] = [
    'label' => __('Result'),
    'color' => '#000000',
    'data' => $Results,
    'lines' => [
        'show' => false,
    ],
    'points' => [
        'show' => true,
    ],
    'curvedLines' => [
        'apply' => false,
    ],
];

$Plot->setZeroPointsToNull();

$Plot->setMarginForGrid(5);
$Plot->setXAxisAsTime();
$Plot->addYAxis(1, 'left');

if ($Prognosis !== [] && max($Prognosis) > 1000 * 3600) {
    $Plot->setYAxisTimeFormat('%H:%M:%S');
} else {
    $Plot->setYAxisTimeFormat('%M:%S');
}

# $Plot->setTitle(__('Prognosis trend') . ' ' . Distance::format($distance));

if ($DataFailed) {
    $Plot->raiseError(__('No data available.'));
}

$Plot->outputJavaScript();
