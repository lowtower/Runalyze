<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Internal\Data;

use Doctrine\Persistence\ManagerRegistry;
use Runalyze\Bundle\CoreBundle\Controller\AppServiceSubscriberTrait;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\PluginConf;
use Runalyze\Bundle\CoreBundle\Entity\PluginConfRepository;
use Runalyze\Bundle\CoreBundle\Entity\Raceresult;
use Runalyze\Bundle\CoreBundle\Entity\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Services\Activity\AgeGradeLookup;
use Runalyze\Util\LocalTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/_internal/data/race-results')]
class RaceResultsController extends AbstractController
{
    use AppServiceSubscriberTrait;

    public function __construct(
        protected ManagerRegistry $doctrine,
        private AgeGradeLookup $ageGradeLookup
    ) {
    }

    protected function getRaceResultRepository(): RaceresultRepository
    {
        return $this->doctrine
            ->getRepository(Raceresult::class);
    }

    protected function getPluginConfRepository(): PluginConfRepository
    {
        return $this->doctrine
            ->getRepository(PluginConf::class);
    }

    #[Route(path: '/all', name: 'internal-data-race-results-all', methods: ['GET'])]
    public function allRaceResultsAction(?Account $account = null): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $result = [];
        $races = $this->getRaceResultRepository()
            ->findAllWithActivityStats($account);
        $ageGradeLookup = $this->ageGradeLookup
            ->getLookup() ?: $this->ageGradeLookup
            ->getDefaultLookup();
        $funIds = $this->getPluginConfRepository()
            ->getAllActivityIdsOfFunRaces($account);

        foreach ($races as $race) {
            $ageGrade = $ageGradeLookup->getAgeGrade(
                $race->getOfficialDistance(),
                (int) $race->getOfficialTime(),
                (int) $race->getActivity()
                    ->getDateTime()
                    ->diff(new LocalTime())
                    ->format('%y')
            );

            $result[] = [
                'name' => $race->getName(),
                'date' => $race->getActivity()
                    ->getDateTime()
                    ->format('c'),
                'sport_id' => $race->getActivity()
                    ->getSport()
                    ->getId(),
                'distance' => $race->getOfficialDistance(),
                'duration' => $race->getOfficialTime(),
                'officially_measured' => $race->getOfficiallyMeasured(),
                'place_total' => $race->getPlaceTotal(),
                'place_gender' => $race->getPlaceGender(),
                'place_ageclass' => $race->getPlaceAgeclass(),
                'participants_total' => $race->getParticipantsTotal(),
                'participants_gender' => $race->getParticipantsGender(),
                'participants_ageclass' => $race->getParticipantsAgeclass(),
                'vo2max' => $race->getActivity()
                    ->getVO2max(),
                'vo2max_by_time' => $race->getActivity()
                    ->getVO2maxByTime(),
                'vo2max_with_elevation' => $race->getActivity()
                    ->getVO2maxWithElevation(),
                'age_grade' => $ageGrade->getPerformance(),
                'is_fun' => in_array($race->getActivity()->getId(), $funIds),
            ];
        }

        return new JsonResponse($result);
    }
}
