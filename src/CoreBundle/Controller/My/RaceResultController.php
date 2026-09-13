<?php

namespace Runalyze\Bundle\CoreBundle\Controller\My;

use Runalyze\Bundle\CoreBundle\Controller\AppServiceSubscriberTrait;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\SportRepository;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Form\RaceResultType;
use Runalyze\Metrics\LegacyUnitConverter;
use Runalyze\Sports\Running\VO2max\Estimation\DanielsGilbertFormula;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/my/raceresult")
 */
class RaceResultController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    use AppServiceSubscriberTrait;

    protected function getRaceresultRepository(): RaceresultRepository
    {
        return $this->getDoctrine()
            ->getRepository(Raceresult::class);
    }

    protected function getSportRepository(): SportRepository
    {
        return $this->getDoctrine()
            ->getRepository(Sport::class);
    }

    protected function getTrainingRepository(): TrainingRepository
    {
        return $this->getDoctrine()
            ->getRepository(Training::class);
    }

    /**
     * @Route("/{activityId}", name="raceresult-form", requirements={"activityId"="\d+"})
     */
    public function raceresultFormAction(int $activityId, Account $account, Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $activity = $this->getTrainingRepository()
            ->findForAccount($activityId, $account->getId());

        if (! $activity instanceof \Runalyze\Bundle\CoreBundle\Entity\Training) {
            throw $this->createAccessDeniedException();
        }

        $raceResult = $this->getRaceresultRepository()
            ->findForAccount($activityId, $account->getId());
        $isNew = false;

        if ($raceResult === null) {
            $isNew = true;
            $raceResult = new Raceresult();
            $raceResult->setAccount($account);
            $raceResult->fillFromActivity($activity);
        }

        $form = $this->createForm(RaceResultType::class, $raceResult, [
            'action' => $this->generateUrl('raceresult-form', [
                'activityId' => $activityId,
            ]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getRaceresultRepository()
                ->save($raceResult);
            $this->get('app.legacy_cache')
                ->clearRaceResultCache($raceResult);
        }

        return $this->render('my/raceresult/form.html.twig', [
            'form' => $form->createView(),
            'isNew' => $isNew,
            'activity' => $activity,
            'unitConverter' => new LegacyUnitConverter(),
        ]);
    }

    /**
     * @Route("/{activityId}/delete", name="raceresult-delete", requirements={"activityId"="\d+"})
     */
    public function raceresultDeleteAction(int $activityId, Request $request, Account $account): \Symfony\Component\HttpFoundation\Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $raceResult = $this->getRaceresultRepository()
            ->findForAccount($activityId, $account->getId());

        if ($raceResult) {
            $this->getRaceresultRepository()
                ->delete($raceResult);
            $this->get('app.legacy_cache')
                ->clearRaceResultCache($raceResult);
        } else {
            throw $this->createAccessDeniedException();
        }

        return $this->render('my/raceresult/deleted.html.twig');
    }

    /**
     * @Route("/performance-chart", name="race-results-performance-chart")
     */
    public function performanceChartAction(Account $account): \Symfony\Component\HttpFoundation\Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $danielsGilbertFormula = new DanielsGilbertFormula();
        $ageGradeLookup = $this->get('app.age_grade_lookup')
            ->getLookup() ?: $this->get('app.age_grade_lookup')
            ->getDefaultLookup();
        $distances = [0.06, 0.1, 0.2, 0.4, 0.8, 1.0, 1.5, 3.0, 5.0, 10.0, 21.1, 42.2, 50.0];
        $distanceTicks = [0.06, 0.1, 0.2, 0.4, 0.8, 1.5, 3.0, 5.0, 10.0, 21.1, 42.2];
        $ageStandardTimes = array_map(fn (float $kilometer) => $ageGradeLookup->getAgeStandard($kilometer), $distances);
        $ageStandardVO2max = array_map(fn ($kilometer, $seconds): float => $danielsGilbertFormula->estimateFromRaceResult($kilometer, $seconds), $distances, $ageStandardTimes);

        return $this->render('my/raceresult/performance_chart.html.twig', [
            'runningSportId' => $this->getSportRepository()
                ->findRunningFor($account)
                ->getId(),
            'mainDistances' => $distances,
            'mainDistanceTicks' => $distanceTicks,
            'ageStandardTimes' => $ageStandardTimes,
            'ageStandardVO2max' => $ageStandardVO2max,
        ]);
    }
}
