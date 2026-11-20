<?php
/**
 * Window: prognosis plot
 * @package Runalyze\Plugins\Panels
 */

use Runalyze\Activity\Distance;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;

/**
 * @var list<float> $distances
 * @var Account $account
 * @var RaceresultRepository $raceResultRepository
 * @var int $runningSportId
 */
if (! isset($_GET['distance'])) {
    $distance = in_array(10, $distances) ? 10 : $distances[0];
} else {
    $distance = (float) $_GET['distance'];
}

$raceResultsData = $raceResultRepository->findTrendResultsForDistance($account, $runningSportId, (float) $distance);

$Submenu = '';
foreach ($distances as $km) {
    $link = 'plugin/RunalyzePluginPanel_Prognose/window.plot.php?distance=' . $km;
    $Submenu .= '<li' . ($km == $distance ? ' class="active"' : '') . '>' . Ajax::window('<a href="' . $link . '">' . (new Distance($km)->stringAuto()) . '</a>') . '</li>';
}
?>
<div class="panel-heading">
    <div class="panel-menu">
        <ul>
            <li class="with-submenu"><span class="link"><?php echo new Distance($distance)->stringAuto(); ?></span><ul class="submenu"><?php echo $Submenu; ?></ul></li>
        </ul>
    </div>
    <h1><?php _e('Prognosis calculator: form trend'); ?></h1>
</div>

<div class="panel-content">
    <?php
    echo Plot::getDivFor('formverlauf_' . str_replace('.', '_', (string) $distance), 800, 450);
    include FRONTEND_PATH . '../plugin/RunalyzePluginPanel_Prognose/Plot.Form.php';
    ?>
</div>
