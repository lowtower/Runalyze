<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Prognose;

use InvalidArgumentException;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Model\Prognosis\PrognosisData;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelConfigurationInterface;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelInterface;
use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\PluginConfigurationStorageInterface;
use Runalyze\Calculation\BasicEndurance;

final readonly class PrognosePanelPresenter implements ModernPanelInterface, ModernPanelConfigurationInterface
{
    private const string PLUGIN_KEY = 'RunalyzePluginPanel_Prognose';

    private const array DEFAULT_DISTANCES = [1, 3, 5, 10, 21.1, 42.2];

    public function __construct(
        private TrainingRepository $trainingRepository,
        private RaceresultRepository $raceResultRepository,
        private ConfigurationManager $configurationManager,
        private PluginConfigurationStorageInterface $configurationStorage
    ) {}

    public function getKey(): string
    {
        return self::PLUGIN_KEY;
    }

    public function getName(): string
    {
        return __('Prognosis');
    }

    public function getDescription(): string
    {
        return __('Predict your race performance on various distances.');
    }

    public function getConfigurationTemplate(): string
    {
        return '@Prognose/configuration.html.twig';
    }

    /**
     * @return array{
     *     distances: string,
     *     distance_unit: string,
     *     model: string,
     *     use_be: bool,
     *     active: int
     * }
     *
     * @throws InvalidArgumentException if the installation is not available for the account
     */
    public function getConfigurationData(Account $account, Plugin $installation): array
    {
        self::validateInstallation($account, $installation, true);

        $id = $installation->getId();
        assert($id !== null);

        $stored = $this->configurationStorage->loadForPlugins([$id])[$id] ?? [];
        $distanceUnit = $this->configurationManager->getList($account)->getUnitSystem()->getDistanceUnit();
        $distancesInKm = isset($stored['distances'])
            ? array_map(floatval(...), explode(',', $stored['distances']))
            : self::DEFAULT_DISTANCES;

        return [
            'distances' => implode(', ', array_map(
                static fn (float $km): string => (string) round((float) $distanceUnit->fromBaseUnit($km), 2),
                $distancesInKm
            )),
            'distance_unit' => $distanceUnit->getAppendix(),
            'model' => in_array($stored['model'] ?? '', ['vo2max', 'cpp', 'steffny', 'cameron'], true)
                ? $stored['model']
                : 'vo2max',
            'use_be' => ($stored['use_be'] ?? '1') === '1',
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
        self::validateInstallation($account, $installation, true);

        $id = $installation->getId();
        assert($id !== null);

        $stored = $this->configurationStorage->loadForPlugins([$id])[$id] ?? [];
        $settings = [];

        if (isset($data['distances']) && is_string($data['distances'])) {
            $values = array_map(trim(...), explode(',', $data['distances']));

            if ($values !== [] && count($values) <= 30 && array_reduce(
                $values,
                static fn (bool $valid, string $value): bool => $valid
                    && is_numeric($value) && (float) $value > 0 && is_finite((float) $value),
                true
            )) {
                $unit = $this->configurationManager->getList($account)->getUnitSystem()->getDistanceUnit();
                $settings['distances'] = implode(',', array_map(
                    static fn (string $value): string => (string) round((float) $unit->toBaseUnit((float) $value), 2),
                    $values
                ));
            }
        }

        if (isset($data['model']) && is_string($data['model'])
            && in_array($data['model'], ['vo2max', 'cpp', 'steffny', 'cameron'], true)
        ) {
            $settings['model'] = $data['model'];
        }

        if (isset($data['edit']) && $data['edit'] === 'true') {
            $settings['use_be'] = isset($data['use_be']) && $data['use_be'] === '1' ? '1' : '0';
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
    public static function validateInstallation(Account $account, Plugin $installation, bool $allowInactive = false): void
    {
        $accountId = $account->getId();

        if (
            $installation->getId() === null
            || $accountId === null
            || $installation->getAccount()->getId() !== $accountId
            || $installation->getKey() !== self::PLUGIN_KEY
            || ! $installation->isPanel()
            || (! $allowInactive && $installation->isInactive())
        ) {
            throw new InvalidArgumentException('The Prognose panel installation is not available for this account.');
        }
    }

    /**
     * @return list<float>
     *
     * @throws InvalidArgumentException if the installation is not available for the account
     */
    public function getPredictionDistances(Account $account, Plugin $installation): array
    {
        self::validateInstallation($account, $installation, true);

        $pluginId = $installation->getId();
        assert($pluginId !== null);

        $configuration = $this->configurationStorage->loadForPlugins([$pluginId])[$pluginId] ?? [];

        if (! isset($configuration['distances'])) {
            return self::DEFAULT_DISTANCES;
        }

        $storedDistances = array_filter(
            array_map(trim(...), explode(',', $configuration['distances'])),
            static fn (string $distance): bool => is_numeric($distance) && (float) $distance > 0
        );

        return array_values(array_map(floatval(...), $storedDistances));
    }

    public function getTemplate(): string
    {
        return '@Prognose/panel.html.twig';
    }

    public function shouldReloadAfterTraining(): bool
    {
        return true;
    }

    /**
     * @return array{prognosis_data: PrognosisData}
     *
     * @throws InvalidArgumentException if the installation does not belong to the account,
     *                                  has a different stable key, is not a panel, or is inactive
     */
    public function present(Account $account, Plugin $installation): array
    {
        self::validateInstallation($account, $installation);

        $pluginId = $installation->getId();
        assert($pluginId !== null);

        $configuration = $this->configurationStorage->loadForPlugins([$pluginId])[$pluginId] ?? [];
        $accountConfiguration = $this->configurationManager->getList($account);

        $distances = $this->getPredictionDistances($account, $installation);

        $vo2maxCategory = $accountConfiguration->getVO2max();
        $vo2maxShape = $vo2maxCategory->useManualValue()
            ? $vo2maxCategory->getManualValue()
            : $accountConfiguration->getData()->getVO2maxShape();

        $basicEndurance = (int) $accountConfiguration->get('data.BASIC_ENDURANCE');

        if ($basicEndurance === 0) {
            $basicEndurance = (int) BasicEndurance::getConst();
        }

        return [
            'prognosis_data' => new PrognosisData(
                $this->trainingRepository,
                $this->raceResultRepository,
                $account,
                $accountConfiguration->getGeneral()->getRunningSport(),
                array_values($distances),
                $configuration['model'] ?? 'vo2max',
                ($configuration['use_be'] ?? '1') === '1',
                $vo2maxShape,
                $basicEndurance
            ),
        ];
    }
}
