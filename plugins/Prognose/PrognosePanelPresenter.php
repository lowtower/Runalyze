<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Prognose;

use InvalidArgumentException;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin;
use Runalyze\Bundle\CoreBundle\Model\Prognosis\PrognosisData;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelInterface;
use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\PluginConfigurationStorageInterface;
use Runalyze\Calculation\BasicEndurance;

final readonly class PrognosePanelPresenter implements ModernPanelInterface
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
            throw new InvalidArgumentException('The Prognose panel installation is not available for this account.');
        }

        $configuration = $this->configurationStorage->loadForPlugins([$pluginId])[$pluginId] ?? [];
        $distances = array_filter(
            array_map(trim(...), explode(',', $configuration['distances'] ?? implode(', ', self::DEFAULT_DISTANCES))),
            is_numeric(...)
        );

        $accountConfiguration = $this->configurationManager->getList($account);
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
