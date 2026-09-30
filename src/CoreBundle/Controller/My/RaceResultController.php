<?php

namespace Runalyze\Bundle\CoreBundle\Controller\My;

use Doctrine\Persistence\ManagerRegistry;
use Runalyze\Bundle\CoreBundle\Controller\AppServiceSubscriberTrait;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\SportRepository;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Form\RaceResultType;
use Runalyze\Bundle\CoreBundle\Services\Activity\AgeGradeLookup;
use Runalyze\Bundle\CoreBundle\Services\LegacyCache;
use Runalyze\Metrics\LegacyUnitConverter;
use Runalyze\Sports\Running\VO2max\Estimation\DanielsGilbertFormula;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/my/raceresult")
 */
class RaceResultController extends AbstractController
{
    use AppServiceSubscriberTrait;

    public function __construct(
        protected ManagerRegistry $doctrine,
        private LegacyCache $legacyCache,
        private AgeGradeLookup $ageGradeLookup
    ) {
    }

    protected function getRaceresultRepository(): RaceresultRepository
    {
        return $this->doctrine
            ->getRepository(Raceresult::class);
    }

    protected function getSportRepository(): SportRepository
    {
        return $this->doctrine
            ->getRepository(Sport::class);
    }

    protected function getTrainingRepository(): TrainingRepository
    {
        return $this->doctrine
            ->getRepository(Training::class);
    }

    /**
     * @Route("/{activityId}", name="raceresult-form", requirements={"activityId"="\d+"})
     */
    public function raceresultFormAction(int $activityId, ?Account $account = null, ?Request $request = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $activity = $this->getTrainingRepository()
            ->findForAccount($activityId, $account->getId());

        if (! $activity instanceof Training) {
            throw $this->createAccessDeniedException();
        }

        $raceResult = $this->getRaceresultRepository()
            ->findForAccount($activityId, $account->getId());
        $isNew = false;

        if (! $raceResult instanceof Raceresult) {
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
            $this->legacyCache
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
    public function raceresultDeleteAction(int $activityId, Request $request, ?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $raceResult = $this->getRaceresultRepository()
            ->findForAccount($activityId, $account->getId());

        if ($raceResult instanceof Raceresult) {
            $this->getRaceresultRepository()
                ->delete($raceResult);
            $this->legacyCache
                ->clearRaceResultCache($raceResult);
        } else {
            throw $this->createAccessDeniedException();
        }

        return $this->render('my/raceresult/deleted.html.twig');
    }

    /**
     * @Route("/performance-chart", name="race-results-performance-chart")
     */
    public function performanceChartAction(?Account $account = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $danielsGilbertFormula = new DanielsGilbertFormula();
        $ageGradeLookup = $this->ageGradeLookup
            ->getLookup() ?: $this->ageGradeLookup
            ->getDefaultLookup();
        $distances = [0.06, 0.1, 0.2, 0.4, 0.8, 1.0, 1.5, 3.0, 5.0, 10.0, 21.1, 42.2, 50.0];
        $distanceTicks = [0.06, 0.1, 0.2, 0.4, 0.8, 1.5, 3.0, 5.0, 10.0, 21.1, 42.2];
        $ageStandardTimes = array_map($ageGradeLookup->getAgeStandard(...), $distances);
        $ageStandardVO2max = array_map($danielsGilbertFormula->estimateFromRaceResult(...), $distances, $ageStandardTimes);

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
