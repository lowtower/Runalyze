<?php
/**
 * RUNALYZE
 *
 * Legacy dashboard body. Full page requests get the twig shell
 * (templates/dashboard.html.twig), XHR requests receive this container
 * as-is (see DefaultController::dashboardAction).
 *
 * @author Hannes Christiansen
 * @copyright http://www.runalyze.com/
 */

use Runalyze\View\Activity\Context;

?>

<div id="container">
    <div id="main">
        <div id="data-browser" class="panel">
            <div id="data-browser-inner">
                <?php echo $dataBrowserContent; ?>
            </div>
        </div>

        <div id="statistics" class="panel">
            <!-- #TSC: needed to scroll to statistics
                 same as runalyze.lib.training.js#load() of "scrollTop" -->
            <div id="statistics-scrolltag" style="position: relative; top: -43px;"></div>
            <ul id="statistics-nav">
                <?php
$Factory = new PluginFactory();
$Stats = $Factory->activePlugins(PluginType::STAT);
foreach ($Stats as $i => $key) {
    $Plugin = $Factory->newInstance($key);

    echo '<li' . ($i == 0 ? ' class="active"' : '') . '>' . $Plugin->getLink() . '</li>';
}

if (PluginStat::hasVariousStats()) {
    echo '<li class="with-submenu">';
    echo '<a href="#">' . __('Miscellaneous') . '</a>';
    echo '<ul class="submenu">';

    $VariousStats = $Factory->variousPlugins();
    foreach ($VariousStats as $key) {
        $Plugin = $Factory->newInstance($key);

        echo '<li>' . $Plugin->getLink() . '</li>';
    }

    echo '</ul>';
    echo '</li>';
}
?>
            </ul>
            <div id="statistics-inner">
                <?php
if (isset($_GET['id'])) {
    $Context = new Context(Request::sendId(), SessionAccountHandler::getId());
    $View = new TrainingView($Context);
    $View->display();
} else {
    $showFirstPlugin = true;

    if (isset($_GET['pluginid'])) {
        $Plugin = $Factory->newInstanceFor((int) $_GET['pluginid']);

        if (! $Plugin->isInActive()) {
            $showFirstPlugin = false;
            $Plugin->display();
        }
    }

    if ($showFirstPlugin) {
        if ($Stats === []) {
            echo __('<em>There are no statistics available. Activate a plugin in your configuration.</em>');
        } else {
            $Factory->newInstance($Stats[0])->display();
        }
    }
}
?>
            </div>
        </div>

    </div>

    <div id="panels">
        <?php echo $panelsContent; ?>
    </div>
</div>
