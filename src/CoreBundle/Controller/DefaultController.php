<?php

namespace Runalyze\Bundle\CoreBundle\Controller;

use DataBrowser;
use Doctrine\Persistence\ManagerRegistry;
use Frontend;
use Plugin;
use Runalyze\Activity\Distance;
use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Plugin as PluginInstallation;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Form\FeedbackType;
use Runalyze\Bundle\CoreBundle\Form\RegistrationType;
use Runalyze\Bundle\CoreBundle\Plugin\Panel\ModernPanelRegistry;
use Runalyze\Bundle\CoreBundle\Repository\AccountRepository;
use Runalyze\Bundle\CoreBundle\Repository\PluginRepository;
use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\AccountMailer;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Dashboard\DashboardStatisticsProvider;
use Runalyze\Bundle\CoreBundle\Services\LegacyContentResponseFactory;
use Runalyze\Bundle\CoreBundle\Services\LegacyPluginOutputRenderer;
use Runalyze\Bundle\CoreBundle\Services\PluginDiscovery;
use Runalyze\Bundle\CoreBundle\Services\PluginInstallationManager;
use Runalyze\Bundle\CoreBundle\Services\PluginSettingsProvider;
use Runalyze\Bundle\CoreBundle\Services\Selection\SportSelectionFactory;
use Runalyze\Bundle\CoreBundle\Services\UnsubscribeHashGenerator;
use Runalyze\Plugins\Prognose\PrognoseCalculatorFormInput;
use Runalyze\Plugins\Prognose\PrognoseCalculatorModelFactory;
use Runalyze\Plugins\Prognose\PrognoseCalculatorPersonalBestProvider;
use Runalyze\Plugins\Prognose\PrognosePanelPresenter;
use Runalyze\Plugins\Prognose\PrognoseTrendDataProvider;
use Runalyze\Plugins\Prognose\PrognoseTrendPlotRenderer;
use Runalyze\View\Activity\Context as ActivityContext;
use RunalyzePluginPanel_Rechenspiele;
use RunalyzePluginStat_Wettkampf;
use SessionAccountHandler;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use TrainingView;
use Twig\Environment;

/**
 * @package Runalyze\Bundle\CoreBundle\Controller
 */
class DefaultController extends AbstractPluginsAwareController
{
    public function __construct(
        ManagerRegistry $doctrine,
        PluginInstallationManager $pluginInstallationManager,
        private readonly PluginDiscovery $pluginDiscovery,
        LegacyPluginOutputRenderer $legacyPluginOutputRenderer,
        LegacyContentResponseFactory $legacyContentResponseFactory,
        PluginSettingsProvider $pluginSettingsProvider,
        ModernPanelRegistry $modernPanelRegistry,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly AuthenticationUtils $authenticationUtils,
        TokenStorageInterface $tokenStorage,
        ParameterBagInterface $parameterBag,
        private readonly AccountMailer $accountMailer,
        ConfigurationManager $configurationManager,
        SportSelectionFactory $sportSelectionFactory,
        RequestStack $requestStack
    ) {
        parent::__construct(
            $doctrine,
            $pluginInstallationManager,
            $legacyPluginOutputRenderer,
            $legacyContentResponseFactory,
            $pluginSettingsProvider,
            $modernPanelRegistry,
            $configurationManager,
            $sportSelectionFactory,
            $tokenStorage,
            $parameterBag,
            $requestStack
        );
    }

    protected function getAccountRepository(): AccountRepository
    {
        return $this->doctrine
            ->getRepository(Account::class);
    }

    /**
     * @throws Throwable if the included legacy dashboard throws
     */
    #[Route(path: '/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboardAction(Request $request, DashboardStatisticsProvider $dashboardStatisticsProvider, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);

        $dataBrowserContent = $this->renderView('my/data_browser.html.twig', new DataBrowser()->templateData());

        $panelsContent = $this->getResponseForAllEnabledPanels($request, $account)
            ->getContent();
        $dashboardStatistics = $this->getDashboardStatisticsContent($dashboardStatisticsProvider, $request, $account);
        $statisticsNavigationContent = $dashboardStatistics['navigation'];
        $statisticsContent = $dashboardStatistics['content'];

        return $this->render('dashboard/base.html.twig', [
            'dataBrowserContent' => $dataBrowserContent,
            'panelsContent' => $panelsContent,
            'statisticsNavigationContent' => $statisticsNavigationContent,
            'statisticsContent' => $statisticsContent,
        ]);
    }

    /**
     * @return array{navigation: string, content: string}
     */
    private function getDashboardStatisticsContent(DashboardStatisticsProvider $provider, Request $request, ?Account $account): array
    {
        $statistics = $provider->getStatistics($request);

        if ($statistics->activityId !== null) {
            $trainingView = new TrainingView(new ActivityContext($statistics->activityId, SessionAccountHandler::getId()));
            $content = $this->renderView('activity/training_view.html.twig', $trainingView->templateData());
        } elseif ($statistics->plugin instanceof Plugin) {
            $fragmentRequest = $request->duplicate();
            $fragmentRequest->headers->set('X-Requested-With', 'XMLHttpRequest');

            $this->requestStack->push($fragmentRequest);

            try {
                $content = $this->getResponseForPlugin($statistics->plugin, $fragmentRequest, $account)->getContent() ?: '';
            } finally {
                $this->requestStack->pop();
            }
        } else {
            $content = __('<em>There are no statistics available. Activate a plugin in your configuration.</em>');
        }

        return [
            'navigation' => $this->renderView('dashboard/statistics_navigation.html.twig', [
                'navigation' => $statistics->navigation,
            ]),
            'content' => $content,
        ];
    }

    /**
     * @throws Throwable if a dashboard plugin throws
     */
    #[Route(path: '/', name: 'base_url', methods: ['GET'])]
    public function indexAction(Request $request, DashboardStatisticsProvider $dashboardStatisticsProvider, ?Account $account = null): Response
    {
        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') || $this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            if ($request->isXmlHttpRequest()) {
                return $this->renderDashboardFragment($request, $dashboardStatisticsProvider, $account);
            }

            return $this->redirectToRoute('dashboard');
        }

        return $this->redirectToRoute('login');
    }

    #[Route(path: '/{_locale}/register', name: 'register', methods: ['GET', 'POST'])]
    public function registerAction(Request $request, UserPasswordHasherInterface $passwordEncoder, Environment $twig, TranslatorInterface $translator): Response
    {
        if (! $this->parameterBag->get('app.user_can_register')) {
            return $this->render('register/disabled.html.twig');
        }

        $account = new Account();
        $form = $this->createForm(RegistrationType::class, $account);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $registration = new Registration($this->doctrine->getManager(), $passwordEncoder);
            $registration->setAccount($account);
            $formdata = $request->request->all($form->getName());

            $registration->setLocale($request->getLocale());
            $registration->setTimezoneByName($formdata['textTimezone']);

            if (! $this->parameterBag->get('app.user_disable_account_activation')) {
                $registration->requireAccountActivation();
            }

            $registration->setPassword($account->getPlainPassword());
            $account = $registration->registerAccount();

            if ($this->parameterBag->get('app.user_disable_account_activation')) {
                return $this->render('account/activate/success.html.twig', [
                    'username' => $account->getUsername(),
                    'alreadyActivated' => false,
                ]);
            }

            $this->accountMailer->sendActivationLinkTo($account);

            return $this->render('register/mail_delivered.html.twig', [
                'num' => $this->collectStatistics(),
            ]);
        }

        return $this->render('register/form.html.twig', [
            'form' => $form->createView(),
            'num' => $this->collectStatistics(),
        ]);
    }

    #[Route(path: '/{_locale}/login', name: 'login', methods: ['GET', 'POST'])]
    public function loginAction(Request $request): RedirectResponse|Response
    {
        if ($this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('dashboard');
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('login/ajax_not_logged_in.html.twig');
        }

        $error = $this->authenticationUtils->getLastAuthenticationError();

        return $this->render('login/form.html.twig', [
            'error' => $error,
            'num' => $this->collectStatistics(),
        ]);
    }

    #[Route(path: '/{_locale}/login_check', name: 'login_check', methods: ['POST'])]
    public function loginCheckAction(): void {}

    #[Route(path: '/logout', name: 'logout', methods: ['GET'])]
    public function logoutAction(): RedirectResponse
    {
        return $this->redirectToRoute('login');
    }

    /**
     * @return array<string, int|string> ['user' => (int)..., 'distance' => (string)...]
     */
    protected function collectStatistics(): array
    {
        $repository = $this->getAccountRepository();
        $numUser = $repository->getAmountOfActivatedUsers();

        /** @var TrainingRepository $repository */
        $repository = $this->doctrine
            ->getRepository(Training::class);
        $numDistance = (float) $repository->getAmountOfLoggedKilometers();

        return [
            'user' => (int) $numUser,
            'distance' => Distance::format($numDistance),
        ];
    }

    /**
     * @throws Throwable if the legacy calculations window throws
     */
    #[Route(path: '/plugin/RunalyzePluginPanel_Rechenspiele/window.php', methods: ['GET', 'POST'], priority: 10)]
    public function rechenspieleWindowAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);

        /** @var RunalyzePluginPanel_Rechenspiele $Plugin */
        $Plugin = $this->instantiatePluginByKey('RunalyzePluginPanel_Rechenspiele');

        return $this->legacyContentResponseFactory->create(
            $this->legacyPluginOutputRenderer->render(function () use ($Plugin): void {
                include FRONTEND_PATH . '../plugin/RunalyzePluginPanel_Rechenspiele/window.php';
            })->getContent(),
            $this->requestStack->getCurrentRequest()
        );
    }

    /**
     * @throws Throwable if the legacy calculations plot throws
     */
    #[Route(path: '/plugin/RunalyzePluginPanel_Rechenspiele/window.plot.php', methods: ['GET'], priority: 10)]
    public function rechenspielePlotWindowAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);

        /** @var RunalyzePluginPanel_Rechenspiele $Plugin */
        $Plugin = $this->instantiatePluginByKey('RunalyzePluginPanel_Rechenspiele');

        return $this->legacyContentResponseFactory->create(
            $this->legacyPluginOutputRenderer->render(function () use ($Plugin): void {
                include FRONTEND_PATH . '../plugin/RunalyzePluginPanel_Rechenspiele/window.plot.php';
            })->getContent(),
            $this->requestStack->getCurrentRequest()
        );
    }

    /**
     * @throws Throwable if the legacy prognosis calculator window throws
     */
    #[Route(path: '/plugin/RunalyzePluginPanel_Prognose/window.php', methods: ['GET', 'POST'], priority: 10)]
    public function prognoseCalculatorWindowAction(
        Request $request,
        PrognosePanelPresenter $prognosePanelPresenter,
        PrognoseCalculatorPersonalBestProvider $personalBestProvider,
        PrognoseCalculatorModelFactory $modelFactory,
        PrognoseCalculatorFormInput $formInput,
        ?Account $account = null
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (! $account instanceof \Runalyze\Bundle\CoreBundle\Entity\Account) {
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

        return $this->legacyContentResponseFactory->create(
            $this->legacyPluginOutputRenderer->render(function () use ($topResults, $distances, $account, $runningSportId, $personalBestProvider, $modelFactory, $formInput, $submittedInput): void {
                // Legacy Formular fields may inspect POST during construction.
                $previousPost = $_POST;
                $_POST = $submittedInput;

                try {
                    new \Prognose_PrognosisWindow($topResults, $distances, $account, $runningSportId, $personalBestProvider, $modelFactory, $formInput, $submittedInput)->display();
                } finally {
                    $_POST = $previousPost;
                }
            })->getContent(),
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

        $WKplugin = null;
        if (
            in_array('RunalyzePluginStat_Wettkampf', $this->pluginDiscovery->findAvailablePluginKeys(), true)
            && $this->pluginInstallationManager->isInstalled('RunalyzePluginStat_Wettkampf')
        ) {
            /** @var RunalyzePluginStat_Wettkampf $WKplugin */
            $WKplugin = $this->instantiatePluginByKey('RunalyzePluginStat_Wettkampf');
        }

        $trendData = $trendDataProvider->getSeries($account, $runningSportId, $distance);
        $races = $raceResultRepository->findTrendResultsForDistance($account, $runningSportId, $distance);
        $isFunCompetition = null;

        if ($WKplugin !== null) {
            $isFunCompetition = $WKplugin->isFunCompetition(...);
        }

        $plot = $trendPlotRenderer->render(
            $distance,
            $trendData['prognosis'],
            $trendData['prognosis_with_basic_endurance'],
            $races,
            $trendData['has_history'],
            $isFunCompetition
        );

        $content = $this->renderView('@Prognose/trend_window.html.twig', [
            'distances' => $distances,
            'selected_distance' => $distance,
            'plot_html' => $plot['html'],
            'plot_javascript' => $plot['javascript'],
        ]);

        return $this->legacyContentResponseFactory->create($content, $request);
    }

    /**
     * @throws Throwable if a dashboard plugin throws
     */
    private function renderDashboardFragment(Request $request, DashboardStatisticsProvider $dashboardStatisticsProvider, ?Account $account): Response
    {
        new Frontend($this->tokenStorage, $this->parameterBag, $this->requestStack);

        $dataBrowserContent = $this->renderView('my/data_browser.html.twig', new DataBrowser()->templateData());

        $panelsContent = $this->getResponseForAllEnabledPanels($request, $account)
            ->getContent();
        $dashboardStatistics = $this->getDashboardStatisticsContent($dashboardStatisticsProvider, $request, $account);

        return $this->render('dashboard/base.html.twig', [
            'dataBrowserContent' => $dataBrowserContent,
            'panelsContent' => $panelsContent,
            'statisticsNavigationContent' => $dashboardStatistics['navigation'],
            'statisticsContent' => $dashboardStatistics['content'],
        ]);
    }

    /**
     * @throws Throwable if a dashboard plugin throws
     */
    #[Route(path: '/index.php', methods: ['GET'])]
    public function indexPhpAction(Request $request, DashboardStatisticsProvider $dashboardStatisticsProvider, ?Account $account = null): Response|RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($request->isXmlHttpRequest()) {
            return $this->renderDashboardFragment($request, $dashboardStatisticsProvider, $account);
        }

        return $this->redirectToRoute('base_url');
    }

    #[Route(path: '/login.php', methods: ['GET', 'POST'])]
    public function loginPhpAction(): RedirectResponse
    {
        return $this->redirectToRoute('dashboard');
    }

    #[Route(path: '/unsubscribe/{mail}/{hash}', name: 'unsubscribe-mail', methods: ['GET'])]
    public function unsubscribeMailAction(string $mail, string $hash, UnsubscribeHashGenerator $unsubscribeHashGenerator): Response
    {
        $repository = $this->getAccountRepository();
        $account = $repository->findOneBy([
            'mail' => $mail,
        ]);

        if ($account !== null && $unsubscribeHashGenerator->isValid($account->getUsername(), $hash)) {
            return $this->render('account/unsubscribe_info.html.twig', [
                'mail' => $mail,
                'hash' => $hash,
            ]);
        }

        return $this->render('account/unsubscribe_failure.html.twig');
    }

    #[Route(path: '/unsubscribe/{mail}/{hash}/confirm', name: 'unsubscribe-mail-confirm', methods: ['GET'])]
    public function unsubscribeMailConfirmAction(string $mail, string $hash, UnsubscribeHashGenerator $unsubscribeHashGenerator): Response
    {
        $repository = $this->getAccountRepository();
        $account = $repository->findOneBy([
            'mail' => $mail,
        ]);

        if ($account !== null && $unsubscribeHashGenerator->isValid($account->getUsername(), $hash)) {
            $account->setAllowMails(false);
            $repository->save($account);

            return $this->render('account/unsubscribe_success.html.twig');
        }

        return $this->render('account/unsubscribe_failure.html.twig');
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no feedback recipient is configured
     */
    #[Route(path: '/feedback', name: 'feedback', methods: ['GET', 'POST'])]
    public function feedbackAction(Request $request, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (empty($this->parameterBag->get('app.feedback_mail'))) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(FeedbackType::class, null, [
            'action' => $this->generateUrl('feedback'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->accountMailer->sendCustomFeedbackToSystem($account, $this->parameterBag->get('app.feedback_mail'), $form->getData()['message']);
        }

        return $this->render('feedback.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
