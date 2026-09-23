<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Activity;

use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityDecorator;
use Runalyze\Bundle\CoreBundle\Component\Activity\Tool\BestSubSegmentsStatistics;
use Runalyze\Bundle\CoreBundle\Component\Activity\Tool\TimeSeriesStatistics;
use Runalyze\Bundle\CoreBundle\Component\Activity\VO2maxCalculationDetailsDecorator;
use Runalyze\Bundle\CoreBundle\Controller\AppServiceSubscriberTrait;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Common\AccountRelatedEntityInterface;
use Runalyze\Bundle\CoreBundle\Entity\Trackdata;
use Runalyze\Bundle\CoreBundle\Entity\TrackdataRepository;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Metrics\Velocity\Unit\PaceEnum;
use Runalyze\Model\Activity;
use Runalyze\Service\ElevationCorrection\StepwiseElevationProfileFixer;
use Runalyze\View\Activity\Context;
use Runalyze\View\Window\Laps\Window;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ViewController extends AbstractController
{
    use AppServiceSubscriberTrait;

    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private ParameterBagInterface $parameterBag
    ) {
    }

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

    protected function getTrackdataRepository(): TrackdataRepository
    {
        return $this->getDoctrine()
            ->getRepository(Trackdata::class);
    }

    protected function checkThatEntityBelongsToActivity(AccountRelatedEntityInterface $entity, Account $account)
    {
        if ($entity->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
        }
    }

    /**
     * @Route("/activity/{id}", name="ActivityShow", requirements={"id"="\d+"})
     */
    public function displayAction(Request $request, int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $activity = $this->getTraining($id);
        if (! $activity instanceof \Runalyze\Bundle\CoreBundle\Entity\Training) {
            throw $this->createNotFoundException('Activity not found.');
        }

        $account = $activity->getAccount();

        $this->checkThatEntityBelongsToActivity($activity, $account);

        switch ($request->query->get('action')) {
            case 'changePrivacy':
                $this->getTrainingRepository()
                    ->save($activity->togglePrivacy());
                $this->get('app.legacy_cache')
                    ->clearActivityCache($activity);
                break;
            case 'delete':
                $this->getTrainingRepository()
                    ->remove($activity);

                return $this->render('activity/activity_has_been_removed.html.twig');
        }

        if (! $request->query->get('silent')) {
            $frontend = new \Frontend(true, $this->tokenStorage, $this->parameterBag);
            $context = new Context($activity->getId(), $account->getId());

            $view = new \TrainingView($context);
            $view->display();
        }

        return new Response();
    }

    /**
     * @Route("/activity/{id}/vo2max-info")
     */
    public function vo2maxInfoAction(int $id, Account $account): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $activity = $this->getTraining($id);
        if (! $activity instanceof \Runalyze\Bundle\CoreBundle\Entity\Training) {
            throw $this->createNotFoundException('Activity not found.');
        }

        $this->checkThatEntityBelongsToActivity($activity, $account);

        $configList = $this->get('app.configuration_manager')
            ->getList();
        $activityContext = $this->get('app.activity_context.factory')
            ->getContext($activity);

        return $this->render('activity/vo2max_info.html.twig', [
            'context' => $activityContext,
            'details' => new VO2maxCalculationDetailsDecorator($activityContext, $configList),
        ]);
    }

    /**
     * @Route("/activity/{id}/splits-info", requirements={"id"="\d+"})
     */
    public function splitsInfoAction(int $id, Account $account): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $Frontend = new \Frontend(false, $this->tokenStorage, $this->parameterBag);

        $context = new Context($id, $account->getId());

        if (! $context->hasTrackdata()) {
            return $this->render('activity/tool/not_possible.html.twig');
        }

        $window = new Window($context);
        $window->display();

        return new Response();
    }

    /**
     * @Route("/activity/{id}/elevation-info", requirements={"id"="\d+"})
     */
    public function elevationInfoAction(int $id, Request $request, Account $account): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($request->get('use-calculated-value') == 'true') {
            $activity = $this->getTraining($id);

            $this->checkThatEntityBelongsToActivity($activity, $account);

            $activity->getAdapter()
                ->useElevationFromRoute();

            $this->getTrainingRepository()
                ->save($activity);
        }

        $Frontend = new \Frontend(false, $this->tokenStorage, $this->parameterBag);

        $context = new Context($id, $account->getId());

        if (! $context->hasRoute()) {
            return $this->render('activity/tool/not_possible.html.twig');
        }

        $elevationInfo = new \ElevationInfo($context);
        $elevationInfo->display();

        return new Response();
    }

    /**
     * @Route("/activity/{id}/time-series-info", requirements={"id"="\d+"}, name="activity-tool-time-series-info")
     */
    public function timeSeriesInfoAction(int $id, Account $account): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $trackdata = $this->getTrackdataRepository()
            ->findByActivity($id, $account);

        if (! $trackdata instanceof \Runalyze\Bundle\CoreBundle\Entity\Trackdata) {
            return $this->render('activity/tool/not_possible.html.twig');
        }

        $trackdataModel = $trackdata->getLegacyModel();

        $paceUnit = PaceEnum::get(
            $this->getTrainingRepository()
                ->getSpeedUnitFor($id, $account->getId())
        );

        $statistics = new TimeSeriesStatistics($trackdataModel);
        $statistics->calculateStatistics([0.1, 0.9]);

        return $this->render('activity/tool/time_series_statistics.html.twig', [
            'statistics' => $statistics,
            'paceAverage' => $trackdataModel->totalPace(),
            'paceUnit' => $paceUnit,
        ]);
    }

    /**
     * @Route("/activity/{id}/sub-segments-info", requirements={"id"="\d+"}, name="activity-tool-sub-segments-info")
     */
    public function subSegmentInfoAction(int $id, Account $account): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $trackdata = $this->getTrackdataRepository()
            ->findByActivity($id, $account);

        if (! $trackdata instanceof \Runalyze\Bundle\CoreBundle\Entity\Trackdata) {
            return $this->render('activity/tool/not_possible.html.twig');
        }

        $trackdataModel = $trackdata->getLegacyModel();

        $paceUnit = PaceEnum::get(
            $this->getTrainingRepository()
                ->getSpeedUnitFor($id, $account->getId())
        );

        $statistics = new BestSubSegmentsStatistics($trackdataModel);
        $statistics->setDistancesToAnalyze([0.2, 1.0, 1.609, 3.0, 5.0, 10.0, 16.09, 21.1, 42.2, 50, 100]);
        $statistics->findDistanceSegments();
        $statistics->setTimesToAnalyze([30, 60, 120, 300, 600, 720, 1800, 3600, 7200]);
        $statistics->findTimeSegments();

        return $this->render('activity/tool/best_sub_segments.html.twig', [
            'statistics' => $statistics,
            'distanceArray' => $trackdataModel->distance(),
            'paceUnit' => $paceUnit,
        ]);
    }

    /**
     * @Route("/activity/{id}/climb-score", requirements={"id"="\d+"}, name="activity-tool-climb-score")
     */
    public function climbScoreAction(int $id, ?Account $account = null): Response
    {
        $activity = $this->getTraining($id);
        if (! $activity instanceof \Runalyze\Bundle\CoreBundle\Entity\Training) {
            throw $this->createNotFoundException('No activity found.');
        }

        $activityContext = $this->get('app.activity_context.factory')
            ->getContext($activity);

        if (! $activity->isPublic() && ! $account instanceof Account) {
            throw $this->createNotFoundException('No activity found.');
        }

        if (! $activityContext->hasTrackdata() || ! $activityContext->hasRoute()) {
            return $this->render('activity/tool/not_possible.html.twig');
        }

        if (
            $activity->hasRoute() && $activity->getRoute()
                ->getElevationsCorrected() !== null &&
            $activity->hasTrackdata() && $activity->getTrackdata()
                ->getDistance() !== null
        ) {
            $numDistance = count($activity->getTrackdata()->getDistance());
            $numElevations = count($activity->getRoute()->getElevationsCorrected());

            if ($numElevations > $numDistance) {
                $activity->getRoute()
                    ->setElevationsCorrected(array_slice($activity->getRoute()->getElevationsCorrected(), 0, $numDistance));
            }
        }

        if ($activity->getRoute()->getElevationsCorrected() !== null && $activity->getTrackdata()->getDistance() !== null) {
            $activity->getRoute()
                ->setElevationsCorrected((new StepwiseElevationProfileFixer(
                    5,
                    StepwiseElevationProfileFixer::METHOD_VARIABLE_GROUP_SIZE
                ))->fixStepwiseElevations(
                    $activity->getRoute()
                        ->getElevationsCorrected(),
                    $activity->getTrackdata()
                        ->getDistance()
                ));
        }

        return $this->render('activity/tool/climb_score.html.twig', [
            'context' => $activityContext,
            'decorator' => new ActivityDecorator($activityContext),
            'paceUnit' => $activity->getSport()
                ->getSpeedUnit(),
        ]);
    }
}
