<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Activity;

use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityDecorator;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityPreview;
use Runalyze\Bundle\CoreBundle\Controller\AppServiceSubscriberTrait;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Form\ActivityType;
use Runalyze\Bundle\CoreBundle\Services\AutomaticReloadFlagSetter;
use Runalyze\Service\ElevationCorrection\Strategy\StrategyInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class EditController extends AbstractController
{
    use AppServiceSubscriberTrait;

    protected function getTrainingRepository(): TrainingRepository
    {
        return $this->getDoctrine()
            ->getRepository(Training::class);
    }

    protected function getTraining(int $id): ?Training
    {
        return $this->getTrainingRepository()
            ->find($id);
    }

    protected function checkThatEntityBelongsToActivity(AccountRelatedEntityInterface $entity, Account $account)
    {
        if ($entity->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
        }
    }

    /**
     * @Route("/activity/{id}/edit", name="activity-edit", requirements={"id"="\d+"})
     */
    public function activityEditAction(Request $request, int $id, Account $account, TranslatorInterface $translator): \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $activity = $this->getTraining($id);
        if (! $activity instanceof \Runalyze\Bundle\CoreBundle\Entity\Training) {
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
            if ($form->has('data_series_remover')) {
                $this->get('app.data_series_remover')
                    ->handleRequest($form->get('data_series_remover')->getData(), $activity);
            }

            if ($form->get('is_race')->getData() && ! $activity->hasRaceresult()) {
                $raceResult = (new Raceresult())->fillFromActivity($activity);
                $activity->setRaceresult($raceResult);
            } elseif (! $form->get('is_race')->getData() && $activity->hasRaceresult()) {
                /** @var RaceresultRepository $repository */
                $repository = $this->getDoctrine()
                    ->getRepository(Raceresult::class);
                $repository->delete($activity->getRaceresult());
                $activity->setRaceresult();
            }

            $this->getTrainingRepository()
                ->save($activity);
            $this->get('app.legacy_cache')
                ->clearActivityCache($activity);

            $this->addFlash('success', $translator->trans('Changes have been saved.'));
            $this->get('app.automatic_reload_flag_setter')
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

        $context = $this->get('app.activity_context.factory')
            ->getContext($activity);

        return $this->render('activity/form.html.twig', [
            'form' => $form->createView(),
            'isNew' => false,
            'isMulti' => (bool) $request->get('multi', false),
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

    /**
     * @Route("/activity/multi-editor", name="multi-editor")
     */
    public function multiEditorAction(Request $request, Account $account): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $ids = array_map(
            'intval',
            array_filter(explode(',', $request->get('ids', '')))
        );

        return $this->getResponseForMultiEditor($ids, $account);
    }

    /**
     * @param int[] $activityIds
     */
    protected function getResponseForMultiEditor(array $activityIds, Account $account): Response
    {
        $previews = array_map(fn (Training $activity): ActivityPreview => new ActivityPreview($activity), $this->getTrainingRepository()
            ->getPartialEntitiesForPreview($activityIds, $account, 20));

        return $this->render('activity/multi_editor_navigation.html.twig', [
            'previews' => $previews,
        ]);
    }

    /**
     * @Route("/activity/{id}/delete", name="activity-delete", requirements={"id"="\d+"})
     */
    public function deleteAction(int $id, Account $account): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $activity = $this->getTraining($id);
        if (! $activity instanceof \Runalyze\Bundle\CoreBundle\Entity\Training) {
            throw $this->createNotFoundException('Activity not found.');
        }

        $this->checkThatEntityBelongsToActivity($activity, $account);

        $this->getTrainingRepository()
            ->remove($activity);
        $this->get('app.legacy_cache')
            ->clearActivityCache($activity);
        $this->get('app.automatic_reload_flag_setter')
            ->set(AutomaticReloadFlagSetter::FLAG_ALL);

        $this->addFlash('success', 'Activity has been deleted.');

        return $this->redirectToRoute('dashboard');
    }

    /**
     * @Route("/activity/{id}/elevation-correction", name="activity-elevation-correction", requirements={"id"="\d+"})
     */
    public function elevationCorrectionAction(Request $request, int $id, Account $account, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $activity = $this->getTraining($id);
        if (! $activity instanceof \Runalyze\Bundle\CoreBundle\Entity\Training) {
            throw $this->createNotFoundException('Activity not found.');
        }

        $this->checkThatEntityBelongsToActivity($activity, $account);

        $success = false;

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

                if ($routeAdapter->correctElevation($this->get('app.elevation_correction'), $strategy)) {
                    $this->addFlash('success', $translator->trans('Elevation data has been corrected.'));
                    $success = true;
                }
            }
        }

        if ($success) {
            $this->adjustAndSaveRouteAndActivityForElevationCorrection($activity);
        } else {
            $this->addFlash('error', $translator->trans('Elevation data could not be retrieved.'));
        }

        return $this->render('util/flashmessages_only.html.twig', [
            'reloadActivityOverlay' => $success,
            'activityId' => $activity->getId(),
        ]);
    }

    protected function adjustAndSaveRouteAndActivityForElevationCorrection(Training $activity)
    {
        $configuration = $this->get('app.configuration_manager')
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
            ->save($activity);

        $this->get('app.legacy_cache')
            ->clearActivityCache($activity);
        $this->get('app.automatic_reload_flag_setter')
            ->set(AutomaticReloadFlagSetter::FLAG_TRAINING_AND_DATA_BROWSER);
    }

    protected function getElevationCorrectionStrategyFromRequest(?string $string): ?StrategyInterface
    {
        if ($string === 'GeoTIFF') {
            return $this->get('app.elevation_correction.geotiff');
        }

        if ($string === 'Geonames') {
            return $this->get('app.elevation_correction.geonames');
        }

        if ($string === 'GoogleMaps') {
            return $this->get('app.elevation_correction.google_maps');
        }

        return null;
    }
}
