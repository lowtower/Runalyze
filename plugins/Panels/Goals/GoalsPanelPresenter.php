<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Goals;

use DateTimeImmutable;
use InvalidArgumentException;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\PanelConfigurationInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\PanelInterface;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\PluginConfigurationStorageInterface;
use Symfony\Component\Clock\ClockInterface;

/**
 * Prepares the Goals panel view model without changing the active legacy renderer.
 */
final readonly class GoalsPanelPresenter implements PanelInterface, PanelConfigurationInterface
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

    public function getName(): string
    {
        return __('Goals');
    }

    public function getDescription(): string
    {
        return __('Set your own goals for different time ranges and compare your current performance with them.');
    }

    public function getConfigurationTemplate(): string
    {
        return '@Goals/configuration.html.twig';
    }

    /**
     * @return array{
     *     ranges: list<array{key: string, name: string, start: string, end: string, note: ?string, show: bool, goal: int}>,
     *     show_details: bool,
     *     active: int
     * }
     *
     * @throws InvalidArgumentException if the installation is not available for the account
     */
    public function getConfigurationData(Account $account, Plugin $installation): array
    {
        $this->validateInstallation($account, $installation, true);

        $id = $installation->getId();
        assert($id !== null);
        $stored = $this->configurationStorage->loadForPlugins([$id])[$id] ?? [];
        $configuration = $this->configurationManager->getList($account);
        $weekStartsOnMonday = (int) $configuration->get('general.WEEK_START') === 1;
        $now = DateTimeImmutable::createFromInterface($this->clock->now());
        $ranges = [];

        foreach ($this->timeRanges->getRanges($now, $weekStartsOnMonday) as $key => $range) {
            $rawGoal = $stored['ziel_' . $key] ?? '0';

            $ranges[] = [
                'key' => $key,
                'name' => $range['name'],
                'start' => $range['start']->format('d.m.Y'),
                'end' => $range['end']->format('d.m.Y'),
                'note' => $range['note'] ?? null,
                'show' => ($stored['ziel_show_' . $key] ?? '1') === '1',
                'goal' => ctype_digit($rawGoal) ? (int) $rawGoal : 0,
            ];
        }

        return [
            'ranges' => $ranges,
            'show_details' => ($stored['details'] ?? '1') === '1',
            'active' => $installation->getActive(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws InvalidArgumentException if the installation is not available for the account
     */
    public function updateConfiguration(Account $account, Plugin $installation, array $data): void
    {
        $this->validateInstallation($account, $installation, true);

        if (($data['edit'] ?? null) !== 'true') {
            return;
        }

        $id = $installation->getId();
        assert($id !== null);
        $stored = $this->configurationStorage->loadForPlugins([$id])[$id] ?? [];
        $configuration = $this->configurationManager->getList($account);
        $weekStartsOnMonday = (int) $configuration->get('general.WEEK_START') === 1;
        $now = DateTimeImmutable::createFromInterface($this->clock->now());

        $settings = [
            'details' => ($data['details'] ?? null) === '1' ? '1' : '0',
        ];

        foreach ($this->timeRanges->getRanges($now, $weekStartsOnMonday) as $key => $range) {
            $settings['ziel_show_' . $key] = ($data['ziel_show_' . $key] ?? null) === '1' ? '1' : '0';
            $goalKey = 'ziel_' . $key;
            $goal = $data[$goalKey] ?? null;

            if (is_string($goal) && ctype_digit($goal) && filter_var($goal, FILTER_VALIDATE_INT) !== false) {
                $settings[$goalKey] = (string) (int) $goal;
            }
        }

        foreach ($settings as $key => $value) {
            if (array_key_exists($key, $stored)) {
                $this->configurationStorage->update($id, $key, $value);
            } else {
                $this->configurationStorage->insert($id, $key, $value);
            }
        }
    }

    /**
     * @throws InvalidArgumentException if the installation is not available for the account
     */
    private function validateInstallation(Account $account, Plugin $installation, bool $allowInactive = false): void
    {
        $id = $installation->getId();
        $accountId = $account->getId();

        if (
            $id === null
            || $accountId === null
            || $installation->getAccount()->getId() !== $accountId
            || $installation->getKey() !== $this->getKey()
            || ! $installation->isPanel()
            || (! $allowInactive && $installation->isInactive())
        ) {
            throw new InvalidArgumentException('The Goals panel installation is not available for this account.');
        }
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
        $this->validateInstallation($account, $installation);

        $id = $installation->getId();
        assert($id !== null);
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

        return [
            'ranges' => $ranges,
            'show_details' => $showDetails,
        ];
    }
}
