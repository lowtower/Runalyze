<?php

namespace Runalyze\Bundle\CoreBundle\Controller\My\Tools;

use Doctrine\ORM\EntityManager;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\AnovaDataQuery;
use Runalyze\Bundle\CoreBundle\Component\Tool\DatabaseCleanup\JobGeneral;
use Runalyze\Bundle\CoreBundle\Component\Tool\DatabaseCleanup\JobLoop;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\Availability;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\FileHandler;
use Runalyze\Bundle\CoreBundle\Component\Tool\TrendAnalysis\TrendAnalysisDataQuery;
use Runalyze\Bundle\CoreBundle\Component\Tool\VO2maxAnalysis\VO2maxAnalysis;
use Runalyze\Bundle\CoreBundle\Controller\AppServiceSubscriberTrait;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\SportRepository;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Form\Tools\Anova\AnovaData;
use Runalyze\Bundle\CoreBundle\Form\Tools\Anova\AnovaType;
use Runalyze\Bundle\CoreBundle\Form\Tools\DatabaseCleanupType;
use Runalyze\Bundle\CoreBundle\Form\Tools\PosterType;
use Runalyze\Bundle\CoreBundle\Form\Tools\TrendAnalysis\TrendAnalysisData;
use Runalyze\Bundle\CoreBundle\Form\Tools\TrendAnalysis\TrendAnalysisType;
use Runalyze\Bundle\CoreBundle\Message\PosterGenerationMessage;
use Runalyze\Metrics\Common\JavaScriptFormatter;
use Runalyze\Sports\Running\Prognosis\VO2max;
use Runalyze\Sports\Running\VO2max\VO2maxVelocity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ToolsController extends AbstractController
{
    use AppServiceSubscriberTrait;

    public function __construct(
        private Availability $posterAvailability,
        private TranslatorInterface $translator,
        private TokenStorageInterface $tokenStorage,
        private ParameterBagInterface $parameterBag,
        private FileHandler $fileHandler,
        private MessageBusInterface $messageBus
    ) {
    }

    /**
     * @Route("/my/tools/cleanup", name="tools-cleanup")
     */
    public function cleanupAction(Request $request, Account $account): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $prefix = $this->parameterBag->get('app.database_prefix');

        $defaultData = [];
        $form = $this->createForm(DatabaseCleanupType::class, $defaultData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $form->getData()['mode'] !== null) {
            $Frontend = new \Frontend(true, $this->tokenStorage, $this->parameterBag);

            if ($form->getData()['mode'] === 'general') {
                $job = new JobGeneral($form->getData(), \DB::getInstance(), $account->getId(), $prefix);
            } else {
                $job = new JobLoop($form->getData(), \DB::getInstance(), $account->getId(), $prefix);
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
     * @Route("/my/tools/tables/vo2max-pace", name="tools-tables-vo2max-pace")
     */
    public function tableVo2maxPaceAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $config = $this->get('app.configuration_manager')
            ->getList();
        $running = $this->getDoctrine()
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
     * @Route("/my/tools/tables/general-pace", name="tools-tables-general-pace")
     */
    public function tableGeneralPaceAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('tools/tables/general_paces.html.twig', [
            'activeTable' => 3,
        ]);
    }

    /**
     * @Route("/my/tools/tables/vo2max", name="tools-tables-vo2max")
     * @Route("/my/tools/tables", name="tools-tables")
     */
    public function tableVo2maxRaceResultAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('tools/tables/vo2max.html.twig', [
            'activeTable' => 1,
            'currentVo2max' => $this->get('app.configuration_manager')
                ->getList()
                ->getCurrentVO2maxShape(),
            'prognosis' => new VO2max(),
            'distances' => [1.0, 3.0, 5.0, 10.0, 21.1, 42.2, 50],
            'vo2maxValues' => range(30.0, 80.0),
        ]);
    }

    /**
     * @Route("/my/tools/vo2max-analysis", name="tools-vo2max-analysis")
     */
    public function vo2maxAnalysisAction(Account $account): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $Frontend = new \Frontend(true, $this->tokenStorage, $this->parameterBag);

        $configuration = $this->get('app.configuration_manager')
            ->getList();
        $correctionFactor = $configuration->getVO2maxCorrectionFactor();

        $analysisTable = new VO2maxAnalysis($configuration->getVO2max()->getLegacyCategory());
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
     * @Route("/my/tools/anova", name="tools-anova")
     */
    public function anovaAction(Request $request, Account $account): \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var SportRepository $repository */
        $repository = $this->getDoctrine()
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

            $unitSystem = $this->get('app.configuration_manager')
                ->getList($account)
                ->getUnitSystem();
            /** @var EntityManager $entityManager */
            $entityManager = $this->getDoctrine()
                ->getManager();
            $query = new AnovaDataQuery($data);
            $query->loadAllGroups($entityManager, $account);

            return new JsonResponse([
                'tickFormatter' => JavaScriptFormatter::getFormatter($query->getValueUnit($unitSystem)),
                'groups' => $query->getResults(
                    $this->getDoctrine()
                        ->getRepository(\Runalyze\Bundle\CoreBundle\Entity\Training::class),
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
     * @Route("/my/tools/trend-analysis", name="tools-trend-analysis")
     */
    public function trendAnalysisAction(Request $request, Account $account): \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var SportRepository $repository */
        $repository = $this->getDoctrine()
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

            $unitSystem = $this->get('app.configuration_manager')
                ->getList($account)
                ->getUnitSystem();
            $query = new TrendAnalysisDataQuery($data);

            return new JsonResponse([
                'tickFormatter' => JavaScriptFormatter::getFormatter($query->getValueUnit($unitSystem)),
                'values' => $query->getResults(
                    $this->getDoctrine()
                        ->getRepository(\Runalyze\Bundle\CoreBundle\Entity\Training::class),
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
     * @Route("/my/tools/poster", name="poster")
     */
    public function posterAction(Request $request, Account $account): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var TrainingRepository $repository */
        $repository = $this->getDoctrine()
            ->getRepository(Training::class);
        $numberOfActivities = $repository->getNumberOfActivitiesFor($account, 2017, 2);

        $form = $this->createForm(PosterType::class, [
            'postertype' => ['heatmap'],
            'year' => date('Y') - 1,
            'title' => ' ',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formdata = $request->request->get($form->getName());

            /** @var TrainingRepository $repository */
            $repository = $this->getDoctrine()
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
                    'posterStoragePeriod' => $this->parameterBag->get('app.poster_storage_period'),
                    'listing' => $this->get('app.poster.filehandler')
                        ->getFileList($account),
                ]);
            }
        }

        return $this->render('tools/poster.html.twig', [
            'form' => $form->createView(),
            'posterStoragePeriod' => $this->parameterBag->get('app.poster_storage_period'),
            'listing' => $this->fileHandler->getFileList($account),
        ]);
    }

    /**
     * @Route("/my/tools/poster/{name}", name="poster-download", requirements={"name"=".+"})
     */
    public function posterDownloadAction(Account $account, string $name): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->fileHandler->getPosterDownloadResponse($account, $name);
    }

    /**
     * @Route("/my/tools", name="tools")
     */
    public function overviewAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('tools/tools_list.html.twig', [
            'posterAvailable' => $this->posterAvailability->isAvailable(),
        ]);
    }
}
