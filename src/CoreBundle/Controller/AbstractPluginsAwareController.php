<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Exception;
use HTML;
use Plugin as LegacyPlugin;
use PluginFactory;
use PluginPanel;
use Runalyze\Bundle\CoreBundle\Component\Statistics\MonthlyStats\AnalysisData;
use Runalyze\Bundle\CoreBundle\Component\Statistics\MonthlyStats\AnalysisSelection;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\SportRepository;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Selection\SportSelectionFactory;
use Runalyze\Bundle\CoreBundle\Twig\ValueExtension;
use Runalyze\Util\LocalTime;
use RunalyzePluginPanel_Sports;
use RunalyzePluginStat_MonthlyStats;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Throwable;

abstract class AbstractPluginsAwareController extends AbstractController
{
    use AppServiceSubscriberTrait;
    use LegacyOutputCaptureTrait;

    protected bool $IsShowingAllPanels = false;

    public function __construct(
        protected ManagerRegistry $doctrine,
        protected ConfigurationManager $configurationManager,
        protected SportSelectionFactory $sportSelectionFactory,
        protected TokenStorageInterface $tokenStorage,
        protected ParameterBagInterface $parameterBag
    ) {
    }

    /**
     * @throws Throwable if a legacy plugin throws
     */
    protected function getResponseForAllEnabledPanels(Request $request, Account $account): Response
    {
        $this->IsShowingAllPanels = true;
        $factory = new PluginFactory();
        $content = '';

        foreach ($factory->enabledPanels() as $key) {
            $panel = $factory->newInstance($key);
            $panelContent = $this->getResponseFor($panel->id(), $request, $account)
                ->getContent();

            if ($panel instanceof RunalyzePluginPanel_Sports) {
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
        $factory = new PluginFactory();
        $content = '';

        try {
            $plugin = $factory->newInstanceFor($pluginId);
        } catch (Exception) {
            $plugin = null;

            $content = HTML::error(__('The plugin could not be found.'));
        }

        if (! $plugin instanceof LegacyPlugin) {
            return (new Response())->setContent($content);
        }

        if ($plugin instanceof RunalyzePluginPanel_Sports) {
            return $this->getResponseForSportsPanel($account, $plugin);
        }

        if ($plugin instanceof RunalyzePluginStat_MonthlyStats) {
            return $this->getResponseForMonthlyStats($request, $account, $pluginId);
        }

        if ($plugin instanceof PluginPanel) {
            $plugin->setSurroundingDivVisible($this->IsShowingAllPanels);
        }

        return $this->captureLegacyOutput(function () use ($plugin): void {
            $plugin->display();
        });
    }

    protected function getResponseForSportsPanel(Account $account, LegacyPlugin $plugin): Response
    {
        /** @var SportRepository $sportRepository */
        $sportRepository = $this->doctrine->getRepository(Sport::class);
        $today = (new LocalTime())->setTime(0, 0, 0);

        return $this->render('my/panels/sports/base.html.twig', [
            'isHidden' => $plugin->isHidden(),
            'pluginId' => $plugin->id(),
            'weekStatistics' => $sportRepository->getSportStatisticsSince($today->weekstart(), $account),
            'monthStatistics' => $sportRepository->getSportStatisticsSince($today->monthStart(), $account),
            'yearStatistics' => $sportRepository->getSportStatisticsSince($today->yearStart(), $account),
            'totalStatistics' => $sportRepository->getSportStatisticsSince(null, $account),
        ]);
    }

    protected function getResponseForMonthlyStats(Request $request, Account $account, int $pluginId): Response
    {
        $valueExtension = new ValueExtension($this->configurationManager);
        $sportSelection = $this->sportSelectionFactory->getSelection($request->get('sport'));
        $analysisList = new AnalysisSelection($request->get('dat'));

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
            'pluginId' => $pluginId,
            'analysisData' => $analysisData,
        ]);
    }
}
