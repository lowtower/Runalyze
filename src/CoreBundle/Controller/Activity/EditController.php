<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Activity;

use Doctrine\Persistence\ManagerRegistry;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityDecorator;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityPreview;
use Runalyze\Bundle\CoreBundle\Controller\AppServiceSubscriberTrait;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Form\ActivityType;
use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Activity\ActivityContextFactory;
use Runalyze\Bundle\CoreBundle\Services\Activity\DataSeriesRemover;
use Runalyze\Bundle\CoreBundle\Services\AutomaticReloadFlagSetter;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Import\ElevationCorrection;
use Runalyze\Bundle\CoreBundle\Services\LegacyCache;
use Runalyze\Service\ElevationCorrection\Exception\InvalidResponseException;
use Runalyze\Service\ElevationCorrection\Exception\NoResponseException;
use Runalyze\Service\ElevationCorrection\Exception\OverQueryLimitException;
use Runalyze\Service\ElevationCorrection\Strategy\Geonames;
use Runalyze\Service\ElevationCorrection\Strategy\GeoTiff;
use Runalyze\Service\ElevationCorrection\Strategy\GoogleMaps;
use Runalyze\Service\ElevationCorrection\Strategy\StrategyInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class EditController extends AbstractController
{
    use AppServiceSubscriberTrait;

    public function __construct(
        protected ManagerRegistry $doctrine,
        private DataSeriesRemover $dataSeriesRemover,
        private LegacyCache $legacyCache,
        private AutomaticReloadFlagSetter $automaticReloadFlagSetter,
        private ActivityContextFactory $activityContextFactory,
        private ElevationCorrection $elevationCorrection,
        private ConfigurationManager $configurationManager,
        private GeoTiff $elevationCorrectionGeoTiff,
        private Geonames $elevationCorrectionGeonames,
        private GoogleMaps $elevationCorrectionGoogleMaps
    ) {
    }

    protected function getTrainingRepository(): TrainingRepository
    {
        return $this->doctrine
            ->getRepository(Training::class);
    }

    protected function getTraining(int $id): ?Training
    {
        return $this->getTrainingRepository()
            ->find($id);
    }

    /**
     * @throws NotFoundHttpException if the entity does not belong to the given account
     */
    protected function checkThatEntityBelongsToActivity(AccountRelatedEntityInterface $entity, ?Account $account = null): void
    {
        if ($entity->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
        }
    }

    /**
     * @throws NotFoundHttpException if the activity does not exist or does not belong to the account
     */
    #[Route(path: '/activity/{id}/edit', name: 'activity-edit', requirements: [
        'id' => '\d+',
    ], methods: ['GET', 'POST'])]
    public function activityEditAction(Request $request, int $id, ?Account $account = null, ?TranslatorInterface $translator = null): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $activity = $this->getTraining($id);
        if (! $activity instanceof Training) {
            throw $this->createNotFoundException('Activity not found.');
        }

        $this->checkThatEntityBelongsToActivity($activity, $account);

        $form = $this->createForm(ActivityType::class, $activity, [
            'action' => $this->generateUrl('activity-edit', [
                'id' => $activity->getId(),
            ]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getTrainingRepository()
                ->ensureThatSportAndTypeAreManaged($activity);

            if ($form->has('data_series_remover')) {
                $this->dataSeriesRemover
                    ->handleRequest($form->get('data_series_remover')->getData(), $activity);
            }

            if ($form->get('is_race')->getData() && ! $activity->hasRaceresult()) {
                $raceResult = new Raceresult()->fillFromActivity($activity);
                $activity->setRaceresult($raceResult);
            } elseif (! $form->get('is_race')->getData() && $activity->hasRaceresult()) {
                /** @var RaceresultRepository $repository */
                $repository = $this->doctrine
                    ->getRepository(Raceresult::class);
                $repository->delete($activity->getRaceresult());
                $activity->setRaceresult();
            }

            $this->getTrainingRepository()
                ->save($activity);
            $this->legacyCache
                ->clearActivityCache($activity);

            $this->addFlash('success', $translator->trans('Changes have been saved.'));
            $this->automaticReloadFlagSetter
                ->set(AutomaticReloadFlagSetter::FLAG_ALL);

            $nextId = $form->get('next-multi-editor')
                ->getData();

            if (is_numeric($nextId)) {
                return $this->redirectToRoute('activity-edit', [
                    'id' => (int) $nextId,
                    'multi' => '1',
                ]);
            }
        }

        $context = $this->activityContextFactory
            ->getContext($activity);

        return $this->render('activity/form.html.twig', [
            'form' => $form->createView(),
            'isNew' => false,
            'isMulti' => $request->query->getBoolean('multi'),
            'decorator' => new ActivityDecorator($context),
            'activity_id' => $activity->getId(),
            'prev_activity_id' => $this->getTrainingRepository()
                ->getIdOfPreviousActivity($activity),
            'next_activity_id' => $this->getTrainingRepository()
                ->getIdOfNextActivity($activity),
            'showElevationCorrectionLink' => $activity->hasRoute() && $activity->getRoute()
                ->hasGeohashes() && ! $activity->getRoute()
                ->hasCorrectedElevations(),
            'isPowerLocked' => $activity->isPowerCalculated() !== null,
        ]);
    }

    #[Route(path: '/activity/multi-editor', name: 'multi-editor', methods: ['GET', 'POST'])]
    public function multiEditorAction(Request $request, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $ids = array_map(
            intval(...),
            array_filter(explode(',', $request->query->get('ids', '')))
        );

        return $this->getResponseForMultiEditor($ids, $account);
    }

    /**
     * @param int[] $activityIds
     */
    protected function getResponseForMultiEditor(array $activityIds, ?Account $account = null): Response
    {
        $previews = array_map(fn (Training $activity): ActivityPreview => new ActivityPreview($activity), $this->getTrainingRepository()
            ->getPartialEntitiesForPreview($activityIds, $account, 20));

        return $this->render('activity/multi_editor_navigation.html.twig', [
            'previews' => $previews,
        ]);
    }

    /**
     * @throws NotFoundHttpException if the activity does not exist or does not belong to the account
     */
    #[Route(path: '/activity/{id}/delete', name: 'activity-delete', requirements: [
        'id' => '\d+',
    ], methods: ['GET', 'POST'])]
    public function deleteAction(int $id, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $activity = $this->getTraining($id);
        if (! $activity instanceof Training) {
            throw $this->createNotFoundException('Activity not found.');
        }

        $this->checkThatEntityBelongsToActivity($activity, $account);

        $this->getTrainingRepository()
            ->remove($activity);
        $this->legacyCache
            ->clearActivityCache($activity);
        $this->automaticReloadFlagSetter
            ->set(AutomaticReloadFlagSetter::FLAG_ALL);

        $this->addFlash('success', 'Activity has been deleted.');

        return $this->redirectToRoute('dashboard');
    }

    /**
     * @throws NotFoundHttpException if the activity does not exist or does not belong to the account
     */
    #[Route(path: '/activity/{id}/elevation-correction', name: 'activity-elevation-correction', requirements: [
        'id' => '\d+',
    ], methods: ['GET', 'POST'])]
    public function elevationCorrectionAction(Request $request, int $id, ?Account $account = null, ?TranslatorInterface $translator = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $activity = $this->getTraining($id);
        if (! $activity instanceof Training) {
            throw $this->createNotFoundException('Activity not found.');
        }

        $this->checkThatEntityBelongsToActivity($activity, $account);

        $success = false;
        $specificErrorMessageAlreadyShown = false;

        if ($activity->hasRoute()) {
            $routeAdapter = $activity->getRoute()
                ->getAdapter();
            $strategy = null;

            if ($request->query->get('strategy') == 'none') {
                $routeAdapter->removeElevation();
                $this->addFlash('notice', $translator->trans('Corrected elevation data has been removed.'));
                $success = true;
            } else {
                $strategy = $this->getElevationCorrectionStrategyFromRequest($request->query->get('strategy'));

                if ($routeAdapter->correctElevation($this->elevationCorrection, $strategy)) {
                    $this->addFlash('success', $translator->trans('Elevation data has been corrected.'));
                    $success = true;
                } elseif (! $strategy instanceof StrategyInterface) {
                    $this->addFlash('error_html', $this->buildElevationCorrectionErrorMessage($this->elevationCorrection, $translator));
                    $specificErrorMessageAlreadyShown = true;
                }
            }
        }

        if ($success) {
            $this->adjustAndSaveRouteAndActivityForElevationCorrection($activity);
        } elseif (! $specificErrorMessageAlreadyShown) {
            $this->addFlash('error', $translator->trans('Elevation data could not be retrieved.'));
        }

        return $this->render('util/flashmessages_only.html.twig', [
            'reloadActivityOverlay' => $success,
            'activityId' => $activity->getId(),
        ]);
    }

    private function buildElevationCorrectionErrorMessage(
        ElevationCorrection $elevationCorrectionService,
        TranslatorInterface $translator
    ): string {
        $reasons = $elevationCorrectionService->getLastAttemptReasons();
        $exceptions = $elevationCorrectionService->getLastAttemptExceptions();

        $introText = $translator->trans('Elevation data could not be retrieved.');

        if ($reasons === []) {
            return sprintf('<p>%s</p>', htmlspecialchars($introText, ENT_QUOTES, 'UTF-8'));
        }

        $strategyLabels = [
            GeoTiff::class => $translator->trans('Local elevation data') . ' (SRTM)',
            Geonames::class => 'Geonames',
            GoogleMaps::class => 'Google Maps',
        ];

        $exceptionReasonLabels = [
            OverQueryLimitException::class => $translator->trans('query limit exceeded'),
            NoResponseException::class => $translator->trans('no response received (network error)'),
            InvalidResponseException::class => $translator->trans('request was rejected (check API configuration/credentials)'),
        ];

        $reasonLabels = [
            'not_possible' => $translator->trans('not configured on this server'),
            'no_data' => $translator->trans('no data available for this location'),
        ];

        $listItems = '';

        foreach ($reasons as $strategyClass => $reason) {
            $label = $strategyLabels[$strategyClass] ?? $strategyClass;
            $reasonText = $reasonLabels[$reason] ?? $reason;

            if ($reason === 'no_data' && isset($exceptions[$strategyClass])) {
                $exceptionClass = $exceptions[$strategyClass];
                $parentClasses = class_parents($exceptionClass);
                $candidateClasses = array_merge(
                    [$exceptionClass],
                    $parentClasses !== false ? array_values($parentClasses) : []
                );

                foreach ($candidateClasses as $candidateClass) {
                    if (isset($exceptionReasonLabels[$candidateClass])) {
                        $reasonText = $exceptionReasonLabels[$candidateClass];
                        break;
                    }
                }
            }

            $listItems .= sprintf(
                '<li><strong>%s</strong>: %s</li>',
                $label,
                $reasonText
            );
        }

        return sprintf(
            '<p>%s</p><ul>%s</ul>',
            htmlspecialchars($introText, ENT_QUOTES, 'UTF-8'),
            $listItems
        );
    }

    protected function adjustAndSaveRouteAndActivityForElevationCorrection(Training $activity): void
    {
        $configuration = $this->configurationManager
            ->getList($activity->getAccount())
            ->getActivityView();

        $activity->getRoute()
            ->getAdapter()
            ->calculateElevation(
                $configuration->getElevationCalculationMethod(),
                $configuration->getElevationCalculationThreshold()
            );

        $activityAdapter = $activity->getAdapter();
        $activityAdapter->useElevationFromRoute();
        $activityAdapter->calculateClimbScore();

        $this->getTrainingRepository()
            ->save($activity, true);

        $this->legacyCache
            ->clearActivityCache($activity);
        $this->automaticReloadFlagSetter
            ->set(AutomaticReloadFlagSetter::FLAG_TRAINING_AND_DATA_BROWSER);
    }

    protected function getElevationCorrectionStrategyFromRequest(?string $string): ?StrategyInterface
    {
        if ($string === 'GeoTIFF') {
            return $this->elevationCorrectionGeoTiff;
        }

        if ($string === 'Geonames') {
            return $this->elevationCorrectionGeonames;
        }

        if ($string === 'GoogleMaps') {
            return $this->elevationCorrectionGoogleMaps;
        }

        return null;
    }
}
