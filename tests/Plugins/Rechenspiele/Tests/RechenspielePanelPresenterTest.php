<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Rechenspiele\Tests;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\PluginConfigurationStorageInterface;
use Runalyze\Parameter\Application\Timezone;
use Runalyze\Plugins\Rechenspiele\RechenspielePanelPresenter;
use Runalyze\Plugins\Rechenspiele\RechenspieleTrainingLoadCalculator;
use Runalyze\Plugins\Rechenspiele\RechenspieleTrimpDataProvider;
use Symfony\Component\Clock\ClockInterface;

#[Group('plugin')]
final class RechenspielePanelPresenterTest extends TestCase
{
    public function testPresentsTrainingLoadAndRespectsDisplaySettings(): void
    {
        $account = $this->account(12);
        $repository = $this->createStub(TrainingRepository::class);
        $repository->method('findDailyTrimpForAccount')->willReturn([
            ['day' => '2026-11-17', 'trimp' => 100.0],
            ['day' => '2026-11-19', 'trimp' => 50.0],
        ]);

        $result = $this->presenter($repository, [
            'show_vo2max' => '0',
            'show_basicendurance' => '0',
            'show_trimpvalues_extra' => '0',
        ])->present($account, $this->installation($account));

        $this->assertSame(['Fatigue', 'Fitness level', 'Stress Balance', 'Rest days'], array_column($result['rows'], 'label'));
        $this->assertSame('27 %', $result['rows'][0]['value']);
        $this->assertSame('7 %', $result['rows'][1]['value']);
        $this->assertSame('-20', $result['rows'][2]['value']);
        $this->assertSame('red', $result['rows'][2]['tone']);
        $this->assertFalse($result['missing_vo2max']);
        $this->assertFalse($result['insufficient_activities']);
    }

    public function testRendersConfiguredVo2maxAndMarathonShapeWithoutModelRows(): void
    {
        $account = $this->account(12);
        $result = $this->presenter(null, [
            'show_trimpvalues' => '0',
            'show_trimpvalues_extra' => '0',
        ], [
            'data.VO2MAX_FORM' => '48.25',
            'data.BASIC_ENDURANCE' => '82',
        ])->present($account, $this->installation($account));

        $this->assertSame(['Effective VO2max', 'Marathon shape'], array_column($result['rows'], 'label'));
        $this->assertSame('48.25', $result['rows'][0]['value']);
        $this->assertSame('82 %', $result['rows'][1]['value']);
        $this->assertFalse($result['missing_vo2max']);
    }

    public function testAllowsAnEmptyTrainingHistoryWithoutErrors(): void
    {
        $account = $this->account(12);
        $result = $this->presenter(null, [
            'show_vo2max' => '0',
            'show_basicendurance' => '0',
        ])->present($account, $this->installation($account));

        $this->assertSame('0', $result['rows'][0]['value']);
        $this->assertSame('0', $result['rows'][1]['value']);
        $this->assertSame('0', $result['rows'][2]['value']);
    }

    public function testRejectsAnotherAccountsInstallationBeforeReadingTraining(): void
    {
        $account = $this->account(12);
        $repository = $this->createMock(TrainingRepository::class);
        $repository->expects($this->never())->method('findDailyTrimpForAccount');

        $this->expectException(InvalidArgumentException::class);

        $this->presenter($repository)->present($account, $this->installation($this->account(13)));
    }

    public function testDeclaresModernPanelMetadata(): void
    {
        $presenter = $this->presenter();

        $this->assertSame('RunalyzePluginPanel_Rechenspiele', $presenter->getKey());
        $this->assertSame('@Rechenspiele/panel.html.twig', $presenter->getTemplate());
        $this->assertTrue($presenter->shouldReloadAfterTraining());
    }

    /**
     * @param array<string, string> $pluginOptions
     * @param array<string, string> $configurationOptions
     */
    private function presenter(
        ?TrainingRepository $repository = null,
        array $pluginOptions = [],
        array $configurationOptions = []
    ): RechenspielePanelPresenter {
        $repository ??= $this->createStub(TrainingRepository::class);
        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new DateTimeImmutable('2026-11-19 12:00:00 UTC'));

        $manager = $this->createStub(ConfigurationManager::class);
        $manager->method('getList')->willReturn(new RunalyzeConfigurationList($configurationOptions));

        $storage = $this->createStub(PluginConfigurationStorageInterface::class);
        $storage->method('loadForPlugins')->willReturn([5 => $pluginOptions]);

        return new RechenspielePanelPresenter(
            new RechenspieleTrimpDataProvider($repository, $clock),
            new RechenspieleTrainingLoadCalculator(),
            $manager,
            $storage,
            $clock
        );
    }

    private function account(int $id): Account
    {
        $account = $this->createStub(Account::class);
        $account->method('getId')->willReturn($id);
        $account->method('getTimezone')->willReturn(Timezone::UTC);

        return $account;
    }

    private function installation(Account $account): Plugin
    {
        $installation = $this->createStub(Plugin::class);
        $installation->method('getId')->willReturn(5);
        $installation->method('getAccount')->willReturn($account);
        $installation->method('getKey')->willReturn('RunalyzePluginPanel_Rechenspiele');
        $installation->method('isPanel')->willReturn(true);
        $installation->method('isInactive')->willReturn(false);

        return $installation;
    }
}
