<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Exception;
use HTML;
use InvalidArgumentException;
use Plugin as LegacyPlugin;
use PluginPanel;
use PluginType;
use Runalyze\Bundle\CoreBundle\Component\Statistics\MonthlyStats\AnalysisData;
use Runalyze\Bundle\CoreBundle\Component\Statistics\MonthlyStats\AnalysisSelection;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin as PluginInstallation;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Model\Prognosis\PrognosisData;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelRegistry;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\LegacyContentResponseFactory;
use Runalyze\Bundle\CoreBundle\Services\LegacyPluginOutputRenderer;
use Runalyze\Bundle\CoreBundle\Services\PluginInstallationManager;
use Runalyze\Bundle\CoreBundle\Services\PluginSettingsProvider;
use Runalyze\Bundle\CoreBundle\Services\Selection\SportSelectionFactory;
use Runalyze\Bundle\CoreBundle\Twig\ValueExtension;
use RunalyzePluginPanel_Prognose;
use RunalyzePluginStat_MonthlyStats;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Throwable;

abstract class AbstractPluginsAwareController extends AbstractController
{
    use AppServiceSubscriberTrait;

    protected bool $IsShowingAllPanels = false;

    public function __construct(
        protected ManagerRegistry $doctrine,
        protected PluginInstallationManager $pluginInstallationManager,
        protected LegacyPluginOutputRenderer $legacyPluginOutputRenderer,
        protected LegacyContentResponseFactory $legacyContentResponseFactory,
        protected PluginSettingsProvider $pluginSettingsProvider,
        protected ModernPanelRegistry $modernPanelRegistry,
        protected ConfigurationManager $configurationManager,
        protected SportSelectionFactory $sportSelectionFactory,
        protected TokenStorageInterface $tokenStorage,
        protected ParameterBagInterface $parameterBag,
        protected RequestStack $requestStack
    ) {}

    /**
     * @throws Throwable if a legacy plugin throws
     */
    protected function getResponseForAllEnabledPanels(Request $request, Account $account): Response
    {
        $this->IsShowingAllPanels = true;
        $content = '';

        foreach ($this->pluginSettingsProvider->getInstalledPluginData() as $pluginData) {
            if ($pluginData['type'] !== PluginType::string(PluginType::PANEL) || $pluginData['active'] === 0) {
                continue;
            }

            if ($this->modernPanelRegistry->has($pluginData['key'])) {
                $installation = $this->pluginInstallationManager->getInstallationForAccount($account, $pluginData['id']);

                if ($installation instanceof PluginInstallation) {
                    $panel = $this->modernPanelRegistry->get($pluginData['key']);
                    $panelContent = $this->getResponseForModernPanel($account, $installation)->getContent();
                    $panelClass = $panel->shouldReloadAfterTraining() ? 'panel' : 'panel dont-reload-for-training';
                    $content .= '<div class="' . $panelClass . '" id="panel-' . $pluginData['id'] . '">' . $panelContent . '</div>';

                    continue;
                }
            }

            $panel = $this->pluginSettingsProvider->getInstalledPluginById($pluginData['id']);
            $panelContent = $this->getResponseForPlugin($panel, $request, $account)->getContent();

            if ($panel instanceof RunalyzePluginPanel_Prognose) {
                $panelContent = '<div class="panel" id="panel-' . $panel->id() . '">' . $panelContent . '</div>';
            }

            $content .= $panelContent;
        }

        return new Response($content);
    }

    /**
     * @throws Throwable if a legacy plugin throws
     */
    protected function getResponseFor(int $pluginId, Request $request, Account $account): Response
    {
        $installation = $this->pluginInstallationManager->getInstallationForAccount($account, $pluginId);

        if (
            $installation instanceof PluginInstallation
            && $installation->isPanel()
            && ! $installation->isInactive()
            && $this->modernPanelRegistry->has($installation->getKey())
        ) {
            return $this->getResponseForModernPanel($account, $installation);
        }

        $content = '';

        try {
            $plugin = $this->instantiatePluginById($pluginId);
        } catch (Exception) {
            $plugin = null;
            $content = HTML::error(__('The plugin could not be found.'));
        }

        if (! $plugin instanceof LegacyPlugin) {
            return new Response()->setContent($content);
        }

        return $this->getResponseForPlugin($plugin, $request, $account);
    }

    private function getResponseForModernPanel(Account $account, PluginInstallation $installation): Response
    {
        $panel = $this->modernPanelRegistry->get($installation->getKey());

        return $this->render($panel->getTemplate(), [
            'plugin_id' => $installation->getId(),
            'is_hidden' => $installation->isHidden(),
            'panel_data' => $panel->present($account, $installation),
        ]);
    }

    /**
     * @throws Throwable if a legacy plugin throws
     */
    protected function getResponseForPlugin(LegacyPlugin $plugin, Request $request, Account $account): Response
    {
        if ($plugin instanceof RunalyzePluginPanel_Prognose) {
            return $this->getResponseForPrognosePanel($account, $plugin);
        }

        if ($plugin instanceof RunalyzePluginStat_MonthlyStats) {
            return $this->getResponseForMonthlyStats($request, $account, $plugin->id());
        }

        if ($plugin instanceof PluginPanel) {
            $plugin->setSurroundingDivVisible($this->IsShowingAllPanels);
        }

        return $this->legacyPluginOutputRenderer->render(function () use ($plugin): void {
            $plugin->display();
        });
    }

    /**
     * @throws InvalidArgumentException if no plugin is installed with the given id
     */
    protected function instantiatePluginById(int $pluginId): LegacyPlugin
    {
        return $this->pluginSettingsProvider->getInstalledPluginById($pluginId);
    }

    /**
     * @throws InvalidArgumentException if no plugin is installed with the given key
     */
    protected function instantiatePluginByKey(string $pluginKey): LegacyPlugin
    {
        return $this->pluginSettingsProvider->getInstalledPluginByKey($pluginKey);
    }

    protected function getResponseForMonthlyStats(Request $request, Account $account, int $pluginId): Response
    {
        $valueExtension = new ValueExtension($this->configurationManager);
        $sportSelection = $this->sportSelectionFactory->getSelection($request->query->get('sport'));
        $analysisList = new AnalysisSelection($request->query->get('dat'));

        if (! $analysisList->hasCurrentKey()) {
            $analysisList->setCurrentKey(AnalysisSelection::DISTANCE);
        }

        $analysisData = new AnalysisData(
            $sportSelection,
            $analysisList,
            $this->doctrine->getRepository(Training::class),
            $account
        );
        $analysisData->setValueExtension($valueExtension);

        return $this->render('my/statistics/monthly-stats/base.html.twig', [
            'plugin_id' => $pluginId,
            'analysisData' => $analysisData,
        ]);
    }

    private function vo2maxForPrognosis(): float
    {
        $list = $this->configurationManager->getList();
        $vo2maxCategory = $list->getVO2max();

        if ($vo2maxCategory->useManualValue()) {
            return $vo2maxCategory->getManualValue();
        }

        return $list->getData()->getVO2maxShape();
    }

    protected function getResponseForPrognosePanel(Account $account, LegacyPlugin $plugin): Response
    {
        /** @var RunalyzePluginPanel_Prognose $prognosePlugin */
        $prognosePlugin = $plugin;

        /** @var TrainingRepository $trainingRepository */
        $trainingRepository = $this->doctrine->getRepository(Training::class);

        $prognosisData = new PrognosisData(
            $trainingRepository,
            $account,
            $this->configurationManager->getList()->getGeneral()->getRunningSport(),
            $prognosePlugin->getDistances(),
            (string) $prognosePlugin->Configuration()->value('model'),
            (bool) $prognosePlugin->Configuration()->value('use_be'),
            $this->vo2maxForPrognosis(),
            (int) \Runalyze\Calculation\BasicEndurance::getConst()
        );

        return $this->render('my/panels/prognose/base.html.twig', [
            'is_hidden' => $plugin->isHidden(),
            'plugin_id' => $plugin->id(),
            'prognosisData' => $prognosisData,
        ]);
    }
}
