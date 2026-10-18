<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Controller\Internal;

use Exception;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Import\DuplicateFinder;
use Runalyze\Util\LocalTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ActivityMatcherController extends AbstractController
{
    private const string EXTERNAL_IDS_PREFIX = 'externalIds=';

    public function __construct(
        private readonly DuplicateFinder $duplicateFinder,
        private readonly ConfigurationManager $configurationManager
    ) {
    }

    /**
     * @throws AccessDeniedException if the user is not logged in
     */
    #[Route(path: '/_internal/activity/matcher', name: 'internal-activity-matcher', methods: ['GET', 'POST'])]
    public function ajaxActivityMatcher(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $ignoredTimestamps = $this->getIgnoredActivityTimestamps();

        /** @var Account $account */
        $account = $this->getUser();

        $matches = [];

        foreach ($this->extractExternalIds($request->getContent()) as $id) {
            $timestamp = null;

            try {
                $timestamp = $this->parserStrtotime($id);
                $possibleDuplicate = $this->duplicateFinder->isPossibleDuplicate(
                    new Training()
                        ->setAccount($account)
                        ->setTime($timestamp)
                );
            } catch (Exception) {
                $possibleDuplicate = false;
            }

            $matches[$id] = [
                'match' => $possibleDuplicate
                    || ($timestamp !== null && in_array($this->roundDownToMinute($timestamp), $ignoredTimestamps, true)),
            ];
        }

        return new JsonResponse([
            'matches' => $matches,
        ]);
    }

    /**
     * @return string[]
     */
    private function extractExternalIds(string $content): array
    {
        $ids = [];

        foreach (explode('&', urldecode($content)) as $line) {
            if (str_starts_with($line, self::EXTERNAL_IDS_PREFIX)) {
                $ids[] = substr($line, strlen(self::EXTERNAL_IDS_PREFIX));
            }
        }

        return $ids;
    }

    /**
     * Ignored activities that cannot be parsed are skipped.
     *
     * @return int[]
     */
    private function getIgnoredActivityTimestamps(): array
    {
        $timestamps = [];

        foreach ($this->configurationManager->getList()->getActivityForm()->getIgnoredActivityIds() as $ignoredId) {
            try {
                $timestamps[] = $this->roundDownToMinute($this->parserStrtotime($ignoredId));
            } catch (Exception) {
                continue;
            }
        }

        return $timestamps;
    }

    private function roundDownToMinute(int $timestamp): int
    {
        return (int) floor($timestamp / 60) * 60;
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
