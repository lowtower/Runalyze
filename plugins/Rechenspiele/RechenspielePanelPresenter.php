<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Rechenspiele;

use InvalidArgumentException;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelInterface;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\PluginConfigurationStorageInterface;
use Runalyze\Calculation\BasicEndurance;
use Symfony\Component\Clock\ClockInterface;

final readonly class RechenspielePanelPresenter implements ModernPanelInterface
{
    private const string PLUGIN_KEY = 'RunalyzePluginPanel_Rechenspiele';

    public function __construct(
        private RechenspieleTrimpDataProvider $trimpDataProvider,
        private RechenspieleTrainingLoadCalculator $calculator,
        private ConfigurationManager $configurationManager,
        private PluginConfigurationStorageInterface $configurationStorage,
        private ClockInterface $clock
    ) {}

    public function getKey(): string
    {
        return self::PLUGIN_KEY;
    }

    public function getTemplate(): string
    {
        return '@Rechenspiele/panel.html.twig';
    }

    public function shouldReloadAfterTraining(): bool
    {
        return true;
    }

    /**
     * @return array{
     *     rows: list<array{label: string, abbreviation: string, value: string, percent: int, tone: string, glossary: ?string}>,
     *     missing_vo2max: bool,
     *     insufficient_activities: bool
     * }
     *
     * @throws InvalidArgumentException if the installation is unavailable for this account
     */
    public function present(Account $account, Plugin $installation): array
    {
        $pluginId = $installation->getId();
        $accountId = $account->getId();

        if (
            $pluginId === null
            || $accountId === null
            || $installation->getAccount()->getId() !== $accountId
            || $installation->getKey() !== self::PLUGIN_KEY
            || ! $installation->isPanel()
            || $installation->isInactive()
        ) {
            throw new InvalidArgumentException('The Rechenspiele panel installation is not available for this account.');
        }

        $configuration = $this->configurationStorage->loadForPlugins([$pluginId])[$pluginId] ?? [];
        $accountConfiguration = $this->configurationManager->getList($account);
        $data = $accountConfiguration->getData();
        $vo2maxCategory = $accountConfiguration->getVO2max();

        $vo2max = $vo2maxCategory->useManualValue()
            ? $vo2maxCategory->getManualValue()
            : $data->getVO2maxShape();

        $trimpConfiguration = $accountConfiguration->getTrimp();
        $load = $this->calculator->calculate(
            $this->trimpDataProvider->getPerformanceModelData($account),
            $this->trimpDataProvider->getMonotonyData($account),
            (int) $accountConfiguration->get('trimp.CTL_DAYS'),
            (int) $accountConfiguration->get('trimp.ATL_DAYS'),
            (int) $accountConfiguration->get('data.MAX_ATL'),
            (int) $accountConfiguration->get('data.MAX_CTL')
        );

        $showLoad = ($configuration['show_trimpvalues'] ?? '1') === '1';
        $showExtra = ($configuration['show_trimpvalues_extra'] ?? '1') === '1';
        $showVO2max = ($configuration['show_vo2max'] ?? '1') === '1';
        $showEndurance = ($configuration['show_basicendurance'] ?? '1') === '1';
        $loadPercent = $accountConfiguration->get('trimp.TRIMP_MODEL_IN_PERCENT') === 'true';
        $balancePercent = $accountConfiguration->get('trimp.TSB_IN_PERCENT') === 'true';
        $positiveBalance = $load['tsb_percent'] > 0;

        $rows = [];

        if ($showVO2max) {
            $rows[] = $this->row('Effective VO2max', '', number_format($vo2max, 2), (int) (2 * round($vo2max - 30)), 'blue', 'vo2max');
        }

        if ($showEndurance) {
            $endurance = (int) $accountConfiguration->get('data.BASIC_ENDURANCE');
            if ($endurance === 0) {
                $endurance = (int) BasicEndurance::getConst();
            }

            $rows[] = $this->row('Marathon shape', '', $endurance . ' %', $endurance, 'blue', 'marathon-shape');
        }

        if ($showLoad) {
            $rows[] = $this->row('Fatigue', 'ATL', (string) ($loadPercent ? $load['atl_percent'] . ' %' : $load['atl']), $load['atl_percent'], 'blue', 'atl');
            $rows[] = $this->row('Fitness level', 'CTL', (string) ($loadPercent ? $load['ctl_percent'] . ' %' : $load['ctl']), $load['ctl_percent'], 'blue', 'ctl');
            $rows[] = $this->row('Stress Balance', 'TSB', sprintf('%+d', $balancePercent ? $load['tsb_percent'] : $load['tsb']) . ($balancePercent ? ' %' : ''), (int) (abs($load['tsb_percent']) / 2), $positiveBalance ? 'green' : 'red', 'tsb');

            if ($positiveBalance) {
                $rows[] = $this->row('Easy TRIMP', '', (string) $load['easy_trimp'], (int) ($load['easy_trimp'] / 10), 'blue', 'easy-trimp');
            } else {
                $rows[] = $this->row('Rest days', '', (string) $load['rest_days'], (int) (100 * $load['rest_days'] / 7), 'blue', null);
            }
        }

        if ($showExtra) {
            $monotonyTone = $load['monotony'] > 2.0 ? 'red' : ($load['monotony'] > 1.5 ? 'orange' : 'green');
            $strainTone = $load['training_strain_percent'] >= 75 ? 'red' : ($load['training_strain_percent'] >= 50 ? 'orange' : 'green');

            $rows[] = $this->row('Monotony', '', number_format($load['monotony'], 2), (int) $load['monotony_percent'], $monotonyTone, 'monotony');
            $rows[] = $this->row('Training strain', '', (string) $load['training_strain'], (int) $load['training_strain_percent'], $strainTone, 'training-strain');
        }

        $startTime = $data->getStartTime();

        return [
            'rows' => $rows,
            'missing_vo2max' => $showVO2max && $vo2max == 0.0,
            'insufficient_activities' => $startTime > 0
                && $this->clock->now()->getTimestamp() - $startTime < 70 * 86400,
        ];
    }

    /**
     * @return array{label: string, abbreviation: string, value: string, percent: int, tone: string, glossary: ?string}
     */
    private function row(string $label, string $abbreviation, string $value, int $percent, string $tone, ?string $glossary): array
    {
        return [
            'label' => $label,
            'abbreviation' => $abbreviation,
            'value' => $value,
            'percent' => max(0, min(100, $percent)),
            'tone' => $tone,
            'glossary' => $glossary,
        ];
    }
}
