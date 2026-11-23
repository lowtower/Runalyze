<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Goals\Tests;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\Category\General;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\PluginConfigurationStorageInterface;
use Runalyze\Plugins\Goals\GoalsPanelPresenter;
use Runalyze\Plugins\Goals\GoalsProgressCalculator;
use Runalyze\Plugins\Goals\GoalsTimeRangeProvider;
use Runalyze\Plugins\Goals\GoalsTrainingDataProvider;
use Symfony\Component\Clock\ClockInterface;

#[Group('plugin')]
final class GoalsPanelPresenterTest extends TestCase
{
    public function testBuildsAllFiveRangesWithDefaultSettings(): void
    {
        $account = $this->account(12);
        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->exactly(5))
            ->method('findTrainingTotalsSince')
            ->willReturn(['activity_count' => 2, 'distance_sum' => 10.0]);

        $result = $this->presenter($repository)->present($account, $this->installation($account));

        $this->assertSame(['woche', 'mon', 'hj', 'saison', 'jahr'], array_column($result['ranges'], 'key'));
        $this->assertTrue($result['show_details']);
        $this->assertSame('Week', $result['ranges'][0]['name']);
        $this->assertSame('16.11.2026', $result['ranges'][0]['start']);
        $this->assertSame('22.11.2026', $result['ranges'][0]['end']);
        $this->assertSame('Current', $result['ranges'][0]['rows'][0]['name']);
        $this->assertNotContains('Goal', array_column($result['ranges'][0]['rows'], 'name'));
    }

    public function testHonorsVisibilityGoalAndDetailsPreferences(): void
    {
        $account = $this->account(12);
        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->once())
            ->method('findTrainingTotalsSince')
            ->with($account, 7, strtotime('2026-11-22 00:00:00 UTC'))
            ->willReturn(['activity_count' => 0, 'distance_sum' => 0.0]);

        $settings = [
            'ziel_show_woche' => '1',
            'ziel_woche' => '100',
            'ziel_show_mon' => '0',
            'ziel_show_hj' => '0',
            'ziel_show_saison' => '0',
            'ziel_show_jahr' => '0',
            'details' => '0',
        ];

        $result = $this->presenter($repository, $settings, false)
            ->present($account, $this->installation($account));

        $this->assertFalse($result['show_details']);
        $this->assertSame(['woche'], array_column($result['ranges'], 'key'));
        $this->assertSame('22.11.2026', $result['ranges'][0]['start']);
        $this->assertSame(['Goal', '1 days left', 'Me', 'Pace&nbsp;Bunny'], array_column($result['ranges'][0]['rows'], 'name'));
    }

    public function testRejectsInstallationFromAnotherAccountWithoutReadingConfiguration(): void
    {
        $storage = $this->createMock(PluginConfigurationStorageInterface::class);
        $storage->expects($this->never())->method('loadForPlugins');

        $this->expectException(InvalidArgumentException::class);

        $this->presenter(null, [], true, $storage)
            ->present($this->account(12), $this->installation($this->account(13)));
    }

    public function testRejectsInactivePanelWithoutQueryingTraining(): void
    {
        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->never())->method('findTrainingTotalsSince');

        $this->expectException(InvalidArgumentException::class);

        $account = $this->account(12);
        $this->presenter($repository)->present($account, $this->installation($account, true));
    }

    public function testExposesTheStableKeyAndTemplate(): void
    {
        $presenter = $this->presenter();

        $this->assertSame('RunalyzePluginPanel_Ziele', $presenter->getKey());
        $this->assertSame('@Goals/panel.html.twig', $presenter->getTemplate());
        $this->assertTrue($presenter->shouldReloadAfterTraining());
    }

    /**
     * @param array<string, string> $settings
     */
    private function presenter(
        ?TrainingRepository $repository = null,
        array $settings = [],
        bool $mondayStart = true,
        ?PluginConfigurationStorageInterface $storage = null
    ): GoalsPanelPresenter {
        $repository ??= $this->createStub(TrainingRepository::class);
        $storage ??= $this->createStub(PluginConfigurationStorageInterface::class);
        $storage->method('loadForPlugins')->willReturn([5 => $settings]);

        $general = $this->createStub(General::class);
        $general->method('getRunningSport')->willReturn(7);

        $configurationList = $this->createStub(RunalyzeConfigurationList::class);
        $configurationList->method('getGeneral')->willReturn($general);
        $configurationList->method('get')->willReturn($mondayStart ? '1' : '0');

        $configuration = $this->createStub(ConfigurationManager::class);
        $configuration->method('getList')->willReturn($configurationList);

        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new DateTimeImmutable('2026-11-22 12:00:00 UTC'));

        return new GoalsPanelPresenter(
            new GoalsTimeRangeProvider(),
            new GoalsTrainingDataProvider($repository),
            new GoalsProgressCalculator(),
            $configuration,
            $storage,
            $clock
        );
    }

    private function account(int $id): Account
    {
        $account = $this->createStub(Account::class);
        $account->method('getId')->willReturn($id);

        return $account;
    }

    private function installation(Account $account, bool $inactive = false): Plugin
    {
        $installation = $this->createStub(Plugin::class);
        $installation->method('getId')->willReturn(5);
        $installation->method('getAccount')->willReturn($account);
        $installation->method('getKey')->willReturn('RunalyzePluginPanel_Ziele');
        $installation->method('isPanel')->willReturn(true);
        $installation->method('isInactive')->willReturn($inactive);

        return $installation;
    }
}
