<?php

/**
 * Draw total time of training for each day of a week for the user
 * Include:   inc/draw/Plot.Weekday.php
 * @package Runalyze\Plugins\Stats
 */

/** @var RunalyzePluginStat_Trainingszeiten $this */

use Runalyze\Util\Time;

if (! ($this instanceof RunalyzePluginStat_Trainingszeiten)) {
    die('Not allowed.');
}

$titleCenter = __('Activity [in h] by weekday');
$yAxis = [];
$xAxis = [];

for ($w = 1; $w <= 7; $w++) {
    $xAxis[] = [$w - 1, Time::weekday($w, true)];
}

// TODO: refactor (see Plot.Bestzeit.php)
// @phpstan-ignore property.protected (called only per include from this class, $this is bound)
if ($this->sportid > 0) {
    // @phpstan-ignore property.protected (called only per include from this class, $this is bound)
    $Sports = [SportFactory::DataFor((int) $this->sportid)];
} else {
    $Sports = SportFactory::AllSports();
}

// @phpstan-ignore-next-line method.protected (called only per include from this class, $this is bound)
$YearDependence = $this->getYearDependenceForQuery();

$Query = DB::getInstance()->prepare(
    'SELECT
		SUM(`s`) as `value`,
		(DAYOFWEEK(FROM_UNIXTIME(`time`))-1) as `day`
	FROM `' . PREFIX . 'training`
	WHERE
		`accountid`=' . SessionAccountHandler::getId() . ' AND
		`sportid`=:id
		' . $YearDependence . '
	GROUP BY `day`
	ORDER BY ((`day`+6)%7) ASC'
);

// TODO: Should be possible with one query?
foreach ($Sports as $sport) {
    $id = $sport['name'];
    $yAxis[$id] = [0, 0, 0, 0, 0, 0, 0];

    $Query->execute([
        ':id' => $sport['id'],
    ]);
    $data = $Query->fetchAll();

    foreach ($data as $dat) {
        $yAxis[$id][($dat['day'] + 6) % 7] = $dat['value'] / 3600;
    }
}

$Plot = new Plot('weekday', 350, 190);

$max = 0;
foreach ($yAxis as $key => $data) {
    $Plot->Data[] = [
        'label' => $key,
        'data' => $data,
    ];
    $max += max($data);
}

$Plot->setLegendAsTable();
$Plot->setMarginForGrid(5);
$Plot->setXLabels($xAxis);
$Plot->addYAxis(1, 'left');
$Plot->addYUnit(1, 'h');
$Plot->setYTicks(1, 1, 0);
$Plot->setYLimits(1, 0, $max);

$Plot->showBars(true);
$Plot->stacked();

$error = true;
foreach ($yAxis as $t) {
    foreach ($t as $e) {
        if ($e != '0') {
            $error = false;
        }
    }
}

if ($error) {
    $Plot->raiseError(__('No data available.'));
}

$Plot->outputJavaScript();
