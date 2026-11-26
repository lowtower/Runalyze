<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Rechenspiele\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationUpdater;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleShapePlotMaximaUpdater;

#[Group('plugin')]
final class RechenspieleShapePlotMaximaUpdaterTest extends TestCase
{
    public function testWritesOnlyChangedMaximaForTheSuppliedAccount(): void
    {
        $account = new Account();
        $configuration = $this->createMock(ConfigurationManager::class);
        $configuration->expects($this->once())
            ->method('getList')
            ->with($account)
            ->willReturn(new RunalyzeConfigurationList([
                'data.MAX_ATL' => '70',
                'data.MAX_CTL' => '40',
            ]));
        $updater = $this->createMock(ConfigurationUpdater::class);
        $updater->expects($this->once())->method('updateMaximalAtl')->with($account, 25);
        $updater->expects($this->never())->method('updateMaximalCtl');

        new RechenspieleShapePlotMaximaUpdater($configuration, $updater)->update($account, [
            'atl' => 25,
            'ctl' => 40,
        ]);
    }

    public function testSkipsPersistenceWhenThereAreNoRecomputedMaxima(): void
    {
        $configuration = $this->createMock(ConfigurationManager::class);
        $configuration->expects($this->never())->method('getList');
        $updater = $this->createMock(ConfigurationUpdater::class);
        $updater->expects($this->never())->method('updateMaximalAtl');
        $updater->expects($this->never())->method('updateMaximalCtl');

        new RechenspieleShapePlotMaximaUpdater($configuration, $updater)->update(new Account(), null);
    }
}
