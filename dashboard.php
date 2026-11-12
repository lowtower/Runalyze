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
                <?php echo $statisticsNavigationContent; ?>

            </ul>
            <div id="statistics-inner">
                <?php echo $statisticsContent; ?>

            </div>
        </div>

    </div>

    <div id="panels">
        <?php echo $panelsContent; ?>
    </div>
</div>
