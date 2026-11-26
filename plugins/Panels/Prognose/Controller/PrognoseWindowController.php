<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Prognose\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Frontend;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin as PluginInstallation;
use Runalyze\Bundle\CoreBundle\Entity\PluginConf;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Repository\PluginConfRepository;
use Runalyze\Bundle\CoreBundle\Repository\PluginRepository;
use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\LegacyContentResponseFactory;
use Runalyze\Calculation\BasicEndurance;
use Runalyze\Configuration;
use Runalyze\Context;
use Runalyze\Plugins\Panels\Prognose\PrognoseCalculatorWindowRenderer;
use Runalyze\Plugins\Panels\Prognose\PrognosePanelPresenter;
use Runalyze\Plugins\Panels\Prognose\PrognoseTrendDataProvider;
use Runalyze\Plugins\Panels\Prognose\PrognoseTrendPlotRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Throwable;

final class PrognoseWindowController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly ConfigurationManager $configurationManager,
        private readonly LegacyContentResponseFactory $legacyContentResponseFactory,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly ParameterBagInterface $parameterBag,
        private readonly RequestStack $requestStack
    ) {}

    /**
     * @throws Throwable if prognosis calculator rendering fails
     */
    #[Route(path: '/plugin/RunalyzePluginPanel_Prognose/window.php', name: 'prognosis-calculator-window', methods: ['GET', 'POST'], priority: 10)]
    public function prognoseCalculatorWindowAction(
        Request $request,
        PrognosePanelPresenter $prognosePanelPresenter,
        PrognoseCalculatorWindowRenderer $windowRenderer,
        ?Account $account = null
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (! $account instanceof Account) {
            throw $this->createAccessDeniedException();
        }

        /** @var PluginRepository $pluginRepository */
        $pluginRepository = $this->doctrine->getRepository(PluginInstallation::class);
        $installation = $pluginRepository->findForAccountAndKey($account, $prognosePanelPresenter->getKey());

        if ($installation === null) {
            throw $this->createNotFoundException('The Prognose panel is not installed.');
        }

        $distances = $prognosePanelPresenter->getPredictionDistances($account, $installation);

        new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);

        /** @var TrainingRepository $trainingRepository */
        $trainingRepository = $this->doctrine->getRepository(Training::class);
        $runningSportId = $this->configurationManager->getList($account)->getGeneral()->getRunningSport();
        $topResults = $trainingRepository->findTopResults($account, $runningSportId, 2);

        $submittedInput = $request->isMethod('POST') ? $request->request->all() : [];

        $paceUnit = Context::Factory()
            ->sport(Configuration::General()->runningSport())
            ->getLegacyPaceUnitEnum();

        return $this->legacyContentResponseFactory->create(
            $windowRenderer->render(
                $account,
                $runningSportId,
                $topResults,
                $distances,
                $submittedInput,
                Configuration::Data()->vo2maxShape(),
                (int) BasicEndurance::getConst(),
                Configuration::General()->distanceUnitSystem(),
                $paceUnit
            ),
            $request
        );
    }

    /**
     * @throws Throwable if the plot renderer fails
     */
    #[Route(path: '/plugin/RunalyzePluginPanel_Prognose/window.plot.php', name: 'prognosis-trend-window', methods: ['GET'], priority: 10)]
    public function prognosisPlotWindowAction(
        Request $request,
        PrognosePanelPresenter $prognosePanelPresenter,
        RaceresultRepository $raceResultRepository,
        PrognoseTrendDataProvider $trendDataProvider,
        PrognoseTrendPlotRenderer $trendPlotRenderer,
        ?Account $account = null
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (! $account instanceof Account) {
            throw $this->createAccessDeniedException();
        }

        /** @var PluginRepository $pluginRepository */
        $pluginRepository = $this->doctrine->getRepository(PluginInstallation::class);
        $installation = $pluginRepository->findForAccountAndKey($account, $prognosePanelPresenter->getKey());

        if ($installation === null) {
            throw $this->createNotFoundException('The Prognose panel is not installed.');
        }

        $distances = $prognosePanelPresenter->getPredictionDistances($account, $installation);
        $defaultDistance = in_array(10, $distances) ? 10.0 : (float) $distances[0];
        $requestedDistance = $request->query->get('distance');
        $distance = is_numeric($requestedDistance) && is_finite((float) $requestedDistance) && (float) $requestedDistance > 0
            ? (float) $requestedDistance
            : $defaultDistance;

        $runningSportId = $this->configurationManager->getList($account)->getGeneral()->getRunningSport();

        new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);

        /** @var PluginConfRepository $configurationRepository */
        $configurationRepository = $this->doctrine->getRepository(PluginConf::class);
        $funRaceIds = array_fill_keys($configurationRepository->getAllActivityIdsOfFunRaces($account), true);
        $trendData = $trendDataProvider->getSeries($account, $runningSportId, $distance);
        $races = $raceResultRepository->findTrendResultsForDistance($account, $runningSportId, $distance);

        $plot = $trendPlotRenderer->render(
            $distance,
            $trendData['prognosis'],
            $trendData['prognosis_with_basic_endurance'],
            $races,
            $trendData['has_history'],
            static fn (int $id): bool => isset($funRaceIds[$id])
        );

        $content = $this->renderView('@Prognose/trend_window.html.twig', [
            'distances' => $distances,
            'selected_distance' => $distance,
            'plot_html' => $plot['html'],
            'plot_javascript' => $plot['javascript'],
        ]);

        return $this->legacyContentResponseFactory->create($content, $request);
    }
}
