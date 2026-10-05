<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Internal;

use Exception;
use Runalyze\Bundle\CoreBundle\Controller\AppServiceSubscriberTrait;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Import\DuplicateFinder;
use Runalyze\Util\LocalTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ActivityMatcherController extends AbstractController
{
    use AppServiceSubscriberTrait;

    public function __construct(
        private DuplicateFinder $duplicateFinder,
        private ConfigurationManager $configurationManager
    ) {
    }

    #[Route(path: '/_internal/activity/matcher', name: 'internal-activity-matcher')]
    public function ajaxActivityMatcher(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $ids = [];
        $matches = [];
        $input = explode('&', urldecode($request->getContent()));

        foreach ($input as $line) {
            if (str_starts_with($line, 'externalIds=')) {
                $ids[] = substr($line, 12);
            }
        }

        $ignoredActivityIds = array_map(function (string $v): float {
            try {
                return (int) floor($this->parserStrtotime($v) / 60.0) * 60.0;
            } catch (Exception) {
                return 0.0;
            }
        }, $this->configurationManager
            ->getList()
            ->getActivityForm()
            ->getIgnoredActivityIds());

        /** @var Account $account */
        $account = $this->getUser();

        foreach ($ids as $id) {
            try {
                $possibleDuplicate = $this->duplicateFinder->isPossibleDuplicate(
                    (new Training())
                        ->setAccount($account)
                        ->setTime($this->parserStrtotime($id))
                );
            } catch (Exception) {
                $possibleDuplicate = false;
            }

            $matches[$id] = [
                'match' => $possibleDuplicate || in_array($id, $ignoredActivityIds),
            ];
        }

        return new JsonResponse([
            'matches' => $matches,
        ]);
    }

    /**
     * Adjusted strtotime
     * Timestamps are given in UTC but local timezone offset has to be considered!
     *
     * @throws Exception if $string cannot be parsed as a date/time
     */
    private function parserStrtotime(string $string): int
    {
        if (str_ends_with($string, 'Z')) {
            return LocalTime::fromServerTime((int) strtotime(substr($string, 0, -1) . ' UTC'))->getTimestamp();
        }

        return LocalTime::fromString($string)->getTimestamp();
    }
}
