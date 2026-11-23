<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Goals;

use DateTimeImmutable;
use InvalidArgumentException;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelInterface;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\PluginConfigurationStorageInterface;
use Symfony\Component\Clock\ClockInterface;

/**
 * Prepares the Goals panel view model without changing the active legacy renderer.
 */
final readonly class GoalsPanelPresenter implements ModernPanelInterface
{
    public function __construct(
        private GoalsTimeRangeProvider $timeRanges,
        private GoalsTrainingDataProvider $trainingData,
        private GoalsProgressCalculator $calculator,
        private ConfigurationManager $configurationManager,
        private PluginConfigurationStorageInterface $configurationStorage,
        private ClockInterface $clock
    ) {}

    public function getKey(): string
    {
        return 'RunalyzePluginPanel_Ziele';
    }

    public function getTemplate(): string
    {
        return '@Goals/panel.html.twig';
    }

    public function shouldReloadAfterTraining(): bool
    {
        return true;
    }

    /**
     * @return array{ranges: list<array{key: string, name: string, start: string, end: string, duration_days: int, rows: list<array<string, int|float|string|bool|null>>}>, show_details: bool}
     *
     * @throws InvalidArgumentException if the panel installation is not available for the account
     */
    public function present(Account $account, Plugin $installation): array
    {
        $id = $installation->getId();
        $accountId = $account->getId();

        if (
            $id === null
            || $accountId === null
            || $installation->getAccount()->getId() !== $accountId
            || $installation->getKey() !== $this->getKey()
            || ! $installation->isPanel()
            || $installation->isInactive()
        ) {
            throw new InvalidArgumentException('The Goals panel installation is not available for this account.');
        }

        $configuration = $this->configurationStorage->loadForPlugins([$id])[$id] ?? [];
        $general = $this->configurationManager->getList($account);
        $runningSportId = $general->getGeneral()->getRunningSport();
        $weekStartsOnMonday = (int) $general->get('general.WEEK_START') === 1;
        $now = $this->clock->now();
        $today = DateTimeImmutable::createFromInterface($now)->setTime(0, 0);
        $showDetails = ($configuration['details'] ?? '1') === '1';
        $ranges = [];

        foreach ($this->timeRanges->getRanges($today, $weekStartsOnMonday) as $key => $range) {
            if (($configuration['ziel_show_' . $key] ?? '1') !== '1') {
                continue;
            }

            $rawGoal = $configuration['ziel_' . $key] ?? '0';
            $goal = is_numeric($rawGoal) && is_finite((float) $rawGoal)
                ? max(0.0, (float) $rawGoal)
                : 0.0;
            $totals = $this->trainingData->getTotalsSince($account, $runningSportId, $range['start']);

            $ranges[] = [
                'key' => $key,
                'name' => $range['name'],
                'start' => $range['start']->format('d.m.Y'),
                'end' => $range['end']->format('d.m.Y'),
                'duration_days' => 1 + (int) $range['start']->diff($range['end'])->format('%a'),
                'rows' => $this->calculator->calculate(
                    $range['start'],
                    $range['end'],
                    $today,
                    $totals['distance_sum'],
                    $totals['activity_count'],
                    $goal,
                    $showDetails
                ),
            ];
        }

        return ['ranges' => $ranges, 'show_details' => $showDetails];
    }
}
