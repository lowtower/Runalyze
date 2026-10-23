<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Controller\My\Tools;

use DB;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Frontend;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\AnovaDataQuery;
use Runalyze\Bundle\CoreBundle\Component\Tool\DatabaseCleanup\JobGeneral;
use Runalyze\Bundle\CoreBundle\Component\Tool\DatabaseCleanup\JobLoop;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\Availability;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\FileHandler;
use Runalyze\Bundle\CoreBundle\Component\Tool\TrendAnalysis\TrendAnalysisDataQuery;
use Runalyze\Bundle\CoreBundle\Component\Tool\VO2maxAnalysis\VO2maxAnalysis;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Form\Tools\Anova\AnovaData;
use Runalyze\Bundle\CoreBundle\Form\Tools\Anova\AnovaType;
use Runalyze\Bundle\CoreBundle\Form\Tools\DatabaseCleanupType;
use Runalyze\Bundle\CoreBundle\Form\Tools\PosterType;
use Runalyze\Bundle\CoreBundle\Form\Tools\TrendAnalysis\TrendAnalysisData;
use Runalyze\Bundle\CoreBundle\Form\Tools\TrendAnalysis\TrendAnalysisType;
use Runalyze\Bundle\CoreBundle\Message\PosterGenerationMessage;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Configuration\Category as ConfigurationCategory;
use Runalyze\Configuration\Category\VO2max as VO2maxConfigurationCategory;
use Runalyze\Metrics\Common\JavaScriptFormatter;
use Runalyze\Sports\Running\Prognosis\VO2max;
use Runalyze\Sports\Running\VO2max\VO2maxVelocity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class ToolsController extends AbstractController
{
    public function __construct(
        protected ManagerRegistry $doctrine,
        private readonly Availability $posterAvailability,
        private readonly TranslatorInterface $translator,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly ParameterBagInterface $parameterBag,
        private readonly FileHandler $fileHandler,
        private readonly MessageBusInterface $messageBus,
        private readonly ConfigurationManager $configurationManager,
        #[Autowire(param: 'app.database_prefix')]
        private readonly string $databasePrefix,
        #[Autowire(param: 'app.poster_storage_period')]
        private readonly int $posterStoragePeriod
    ) {}

    /**
     * @throws AccessDeniedException if the user is not logged in
     */
    #[Route(path: '/my/tools/cleanup', name: 'tools-cleanup', methods: ['GET', 'POST'])]
    public function cleanupAction(Request $request, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $defaultData = [];
        $form = $this->createForm(DatabaseCleanupType::class, $defaultData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $form->getData()['mode'] !== null) {
            $Frontend = new Frontend(true, $this->tokenStorage, $this->parameterBag);

            if ($form->getData()['mode'] === 'general') {
                $job = new JobGeneral($form->getData(), DB::getInstance(), $account->getId(), $this->databasePrefix);
            } else {
                $job = new JobLoop($form->getData(), DB::getInstance(), $account->getId(), $this->databasePrefix);
            }

            $job->run();

            return $this->render('tools/database_cleanup/results.html.twig', [
                'messages' => $job->messages(),
            ]);
        }

        return $this->render('tools/database_cleanup/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws AccessDeniedException if the user is not logged in
     */
    #[Route(path: '/my/tools/tables/vo2max-pace', name: 'tools-tables-vo2max-pace', methods: ['GET'])]
    public function tableVo2maxPaceAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $config = $this->configurationManager
            ->getList();
        $running = $this->doctrine
            ->getRepository(Sport::class)
            ->find($config->getGeneral()->getRunningSport());

        return $this->render('tools/tables/vo2max_paces.html.twig', [
            'activeTable' => 2,
            'currentVo2max' => $config->getCurrentVO2maxShape(),
            'vo2maxVelocity' => new VO2maxVelocity(),
            'paceUnit' => $config->getUnitSystem()
                ->getPaceUnit($running),
        ]);
    }

    /**
     * @throws AccessDeniedException if the user is not logged in
     */
    #[Route(path: '/my/tools/tables/general-pace', name: 'tools-tables-general-pace', methods: ['GET'])]
    public function tableGeneralPaceAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('tools/tables/general_paces.html.twig', [
            'activeTable' => 3,
        ]);
    }

    /**
     * @throws AccessDeniedException if the user is not logged in
     */
    #[Route(path: '/my/tools/tables/vo2max', name: 'tools-tables-vo2max', methods: ['GET'])]
    #[Route(path: '/my/tools/tables', name: 'tools-tables', methods: ['GET'])]
    public function tableVo2maxRaceResultAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('tools/tables/vo2max.html.twig', [
            'activeTable' => 1,
            'currentVo2max' => $this->configurationManager
                ->getList()
                ->getCurrentVO2maxShape(),
            'prognosis' => new VO2max(),
            'distances' => [1.0, 3.0, 5.0, 10.0, 21.1, 42.2, 50],
            'vo2maxValues' => range(30.0, 80.0),
        ]);
    }

    /**
     * @throws AccessDeniedException if the user is not logged in
     */
    #[Route(path: '/my/tools/vo2max-analysis', name: 'tools-vo2max-analysis', methods: ['GET'])]
    public function vo2maxAnalysisAction(?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $Frontend = new Frontend(true, $this->tokenStorage, $this->parameterBag);

        $configuration = $this->configurationManager
            ->getList();
        $correctionFactor = $configuration->getVO2maxCorrectionFactor();

        /** @var VO2maxConfigurationCategory&ConfigurationCategory $vo2max */
        $vo2max = $configuration->getVO2max()
            ->getLegacyCategory();
        $analysisTable = new VO2maxAnalysis($vo2max);
        $races = $analysisTable->getAnalysisForAllRaces(
            $correctionFactor,
            $configuration->getGeneral()
                ->getRunningSport(),
            $account->getId()
        );

        return $this->render('tools/vo2max_analysis.html.twig', [
            'races' => $races,
            'vo2maxFactor' => $correctionFactor,
        ]);
    }

    /**
     * @throws AccessDeniedException if the user is not logged in
     */
    #[Route(path: '/my/tools/anova', name: 'tools-anova', methods: ['GET', 'POST'])]
    public function anovaAction(Request $request, ?Account $account = null): JsonResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var SportRepository $repository */
        $repository = $this->doctrine
            ->getRepository(Sport::class);
        $data = AnovaData::getDefault($repository->findAllFor($account), []);

        $form = $this->createForm(AnovaType::class, $data, [
            'action' => $this->generateUrl('tools-anova'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (! $form->isValid()) {
                return new JsonResponse([
                    'status' => 'There was a problem.',
                ]);
            }

            $unitSystem = $this->configurationManager
                ->getList($account)
                ->getUnitSystem();
            /** @var EntityManager $entityManager */
            $entityManager = $this->doctrine
                ->getManager();
            $query = new AnovaDataQuery($data);
            $query->loadAllGroups($entityManager, $account);

            return new JsonResponse([
                'tickFormatter' => JavaScriptFormatter::getFormatter($query->getValueUnit($unitSystem)),
                'groups' => $query->getResults(
                    $this->doctrine
                        ->getRepository(Training::class),
                    $account,
                    $unitSystem
                ),
            ]);
        }

        return $this->render('tools/anova/base.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws AccessDeniedException if the user is not logged in
     */
    #[Route(path: '/my/tools/trend-analysis', name: 'tools-trend-analysis', methods: ['GET', 'POST'])]
    public function trendAnalysisAction(Request $request, ?Account $account = null): JsonResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var SportRepository $repository */
        $repository = $this->doctrine
            ->getRepository(Sport::class);
        $data = TrendAnalysisData::getDefault($repository->findAllFor($account), []);

        $form = $this->createForm(TrendAnalysisType::class, $data, [
            'action' => $this->generateUrl('tools-trend-analysis'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (! $form->isValid()) {
                return new JsonResponse([
                    'status' => 'There was a problem.',
                ]);
            }

            $unitSystem = $this->configurationManager
                ->getList($account)
                ->getUnitSystem();
            $query = new TrendAnalysisDataQuery($data);

            return new JsonResponse([
                'tickFormatter' => JavaScriptFormatter::getFormatter($query->getValueUnit($unitSystem)),
                'values' => $query->getResults(
                    $this->doctrine
                        ->getRepository(Training::class),
                    $account,
                    $unitSystem
                ),
            ]);
        }

        return $this->render('tools/trend-analysis/base.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws AccessDeniedException if the user is not logged in
     */
    #[Route(path: '/my/tools/poster', name: 'poster', methods: ['GET', 'POST'])]
    public function posterAction(Request $request, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $form = $this->createForm(PosterType::class, [
            'postertype' => ['heatmap'],
            'year' => date('Y') - 1,
            'title' => ' ',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formdata = $request->request->all($form->getName());

            /** @var TrainingRepository $repository */
            $repository = $this->doctrine
                ->getRepository(Training::class);
            $numberOfActivities = $repository->getNumberOfActivitiesFor($account, (int) $formdata['year'], (int) $formdata['sport']);
            if ($numberOfActivities <= 1) {
                $this->addFlash('error', $this->translator->trans('There are not enough activities to generate a poster. Please change your selection.'));
            } else {
                $this->messageBus->dispatch(new PosterGenerationMessage(
                    $account->getId(),
                    (int) $formdata['year'],
                    $formdata['postertype'],
                    (int) $formdata['sport'],
                    $formdata['title'],
                    $formdata['size'],
                    $formdata['backgroundColor'],
                    $formdata['trackColor'],
                    $formdata['textColor'],
                    $formdata['raceColor']
                ));

                return $this->render('tools/poster_success.html.twig', [
                    'posterStoragePeriod' => $this->posterStoragePeriod,
                    'listing' => $this->fileHandler
                        ->getFileList($account),
                ]);
            }
        }

        return $this->render('tools/poster.html.twig', [
            'form' => $form->createView(),
            'posterStoragePeriod' => $this->posterStoragePeriod,
            'listing' => $this->fileHandler->getFileList($account),
        ]);
    }

    /**
     * @throws AccessDeniedException if the user is not logged in
     * @throws NotFoundHttpException if the poster does not exist for the current account
     */
    #[Route(path: '/my/tools/poster/{name}', name: 'poster-download', requirements: [
        'name' => Requirement::CATCH_ALL,
    ], methods: ['GET', 'POST'])]
    public function posterDownloadAction(?Account $account = null, string $name = ''): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->fileHandler->getPosterDownloadResponse($account, $name);
    }

    /**
     * @throws AccessDeniedException if the user is not logged in
     */
    #[Route(path: '/my/tools', name: 'tools', methods: ['GET'])]
    public function overviewAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('tools/tools_list.html.twig', [
            'posterAvailable' => $this->posterAvailability->isAvailable(),
        ]);
    }
}
