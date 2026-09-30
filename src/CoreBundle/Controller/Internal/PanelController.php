<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Internal;

use Doctrine\Persistence\ManagerRegistry;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\SportRepository;
use Runalyze\Util\LocalTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/_internal/panel")
 */
class PanelController extends AbstractController
{
    public function __construct(
        protected ManagerRegistry $doctrine
    ) {
    }

    /**
     * @Route("/sport", name="internal-sport-panel")
     * @throws AccessDeniedException
     */
    public function sportStatAction(Request $request, ?Account $account = null): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var SportRepository $sportRepository */
        $sportRepository = $this->doctrine
            ->getRepository(Sport::class);

        $today = (new LocalTime())->setTime(0, 0, 0);
        $weekStart = $today->weekstart();
        $monthStart = $today->monthStart();
        $yearStart = $today->yearStart();

        return new JsonResponse([
            'weekStatistics' => $sportRepository->getSportStatisticsSince($weekStart, $account, true),
            'monthStatistics' => $sportRepository->getSportStatisticsSince($monthStart, $account, true),
            'yearStatistics' => $sportRepository->getSportStatisticsSince($yearStart, $account, true),
            'totalStatistics' => $sportRepository->getSportStatisticsSince(null, $account, true),
        ]);
    }
}
