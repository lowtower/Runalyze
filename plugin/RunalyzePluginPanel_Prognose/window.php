<?php

declare(strict_types=1);

/**
 * Window: prognosis
 * @package Runalyze\Plugins\Panels
 */

require_once __DIR__ . '/class.Prognose_PrognosisWindow.php';

$Window = new Prognose_PrognosisWindow();
$Window->display();
