<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Rechenspiele\Controller;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Persistence\ManagerRegistry;
use Frontend;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin as PluginInstallation;
use Runalyze\Bundle\CoreBundle\Repository\PluginRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\LegacyContentResponseFactory;
use Runalyze\Parameter\Application\Timezone as TimezoneEnum;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleExplanationWindowProvider;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspielePlotSelection;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleShapePlotDataProvider;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleShapePlotMaximaUpdater;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleShapePlotOutputAdapter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Throwable;
use Twig\Environment;

final class RechenspieleWindowController extends AbstractController
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
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if the Rechenspiele panel is not installed
     */
    #[Route(path: '/plugin/RunalyzePluginPanel_Rechenspiele/window.php', name: 'rechenspiele-explanation-window', methods: ['GET', 'POST'], priority: 10)]
    public function rechenspieleWindowAction(
        Request $request,
        Environment $twig,
        RechenspieleExplanationWindowProvider $explanationWindowProvider,
        ?Account $account = null
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (! $account instanceof Account) {
            throw $this->createAccessDeniedException();
        }

        /** @var PluginRepository $pluginRepository */
        $pluginRepository = $this->doctrine->getRepository(PluginInstallation::class);

        if ($pluginRepository->findForAccountAndKey($account, 'RunalyzePluginPanel_Rechenspiele') === null) {
            throw $this->createNotFoundException('The Rechenspiele panel is not installed.');
        }

        new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);

        $content = $twig->render('@Rechenspiele/explanation-window.html.twig', $explanationWindowProvider->getWindowData($account));

        return $this->legacyContentResponseFactory->create($content, $request);
    }

    /**
     * @throws Throwable if the calculations plot throws
     */
    #[Route(path: '/plugin/RunalyzePluginPanel_Rechenspiele/window.plot.php', name: 'rechenspiele-shape-window', methods: ['GET'], priority: 10)]
    public function rechenspielePlotWindowAction(
        Request $request,
        Environment $twig,
        ClockInterface $clock,
        RechenspieleShapePlotDataProvider $dataProvider,
        RechenspieleShapePlotOutputAdapter $outputAdapter,
        RechenspieleShapePlotMaximaUpdater $maximaUpdater,
        ?Account $account = null
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (! $account instanceof Account) {
            throw $this->createAccessDeniedException();
        }

        /** @var PluginRepository $pluginRepository */
        $pluginRepository = $this->doctrine->getRepository(PluginInstallation::class);

        if ($pluginRepository->findForAccountAndKey($account, 'RunalyzePluginPanel_Rechenspiele') === null) {
            throw $this->createNotFoundException('The Rechenspiele panel is not installed.');
        }

        $timezone = new DateTimeZone(TimezoneEnum::getFullNameByEnum($account->getTimezone()));
        $today = $clock->now()->setTimezone($timezone);
        $startTimestamp = $this->configurationManager->getList($account)->getData()->getStartTime();
        $firstActivity = new DateTimeImmutable('@' . $startTimestamp)->setTimezone($timezone);
        $hasHistory = $startTimestamp > 0 && $firstActivity->setTime(0, 0) <= $today->setTime(0, 0);
        $currentYear = (int) $today->format('Y');
        $startYear = $hasHistory ? (int) $firstActivity->format('Y') : $currentYear;
        $selection = RechenspielePlotSelection::fromQuery($request->query->all(), $startYear, $currentYear);

        new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);

        $data = $hasHistory ? $dataProvider->getPlotData($account, $selection->range, $selection->model) : null;
        $plot = $outputAdapter->render($selection->range, $selection->model, $data);
        $content = $twig->render('@Rechenspiele/shape-window.html.twig', [
            'range' => $selection->range,
            'model' => $selection->model,
            'ranges' => RechenspielePlotSelection::availableRanges($startYear, $currentYear),
            'models' => RechenspielePlotSelection::availableModels(),
            'plot' => $plot,
        ]);

        $maximaUpdater->update($account, $data['recomputed_maxima'] ?? null);

        return $this->legacyContentResponseFactory->create($content, $request);
    }
}
