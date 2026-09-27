<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\Internal;

use Doctrine\ORM\EntityManagerInterface;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\SportRepository;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Runalyze\Util\LocalTime;
use Symfony\Bundle\FrameworkBundle\Client;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
class ActivityMatcherControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->getDefaultAccount();
    }

    protected function computeActivityIdFor(string $externalId): int
    {
        $previousTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');

        try {
            $timestamp = str_ends_with($externalId, 'Z')
                ? LocalTime::fromServerTime((int) strtotime(substr($externalId, 0, -1) . ' UTC'))->getTimestamp()
                : LocalTime::fromString($externalId)->getTimestamp();

            return (int) floor($timestamp / 60.0) * 60;
        } finally {
            date_default_timezone_set($previousTimezone);
        }
    }

    protected function insertTrainingWithActivityId(Client $client, int $accountId, int $activityId, ?int $sportOwnerAccountId = null): Training
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $client->getContainer()
            ->get('doctrine')
            ->getManager();

        $account = $entityManager->getRepository(Account::class)->find($accountId);

        /** @var SportRepository $sportRepository */
        $sportRepository = $entityManager->getRepository(Sport::class);
        $runningSport = $sportRepository->findOneBy([
            'account' => $sportOwnerAccountId ?? $accountId,
        ]);

        $activity = (new Training())
            ->setAccount($account)
            ->setSport($runningSport)
            ->setTime(time())
            ->setDistance(10.0)
            ->setS(3600.0)
            ->setActivityId($activityId);

        $entityManager->persist($activity);
        $entityManager->flush();

        return $activity;
    }

    /**
     * @param string[] $externalIds
     */
    protected function requestBodyFor(array $externalIds): string
    {
        return implode('&', array_map(
            fn (string $id): string => 'externalIds=' . urlencode($id),
            $externalIds
        ));
    }

    public function testAjaxActivityMatcherRequiresAuthentication(): void
    {
        $client = $this->makeClient();
        $client->request(
            'POST',
            '/_internal/activity/matcher',
            [],
            [],
            [],
            $this->requestBodyFor(['2020-06-15T10:00:00Z'])
        );

        $this->isSuccessful($client->getResponse(), false);
    }

    public function testAjaxActivityMatcherReturnsNoMatchForUnknownExternalId(): void
    {
        $externalId = '2020-06-15T10:00:00Z';

        $client = $this->makeAuthenticatedClient();
        $client->request(
            'POST',
            '/_internal/activity/matcher',
            [],
            [],
            [],
            $this->requestBodyFor([$externalId])
        );

        $this->assertStatusCode(200, $client);

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertFalse($responseData['matches'][$externalId]['match']);
    }

    public function testAjaxActivityMatcherDetectsExistingActivityAsDuplicate(): void
    {
        $externalId = '2020-06-15T10:00:00Z';

        $client = $this->makeAuthenticatedClient();
        $this->insertTrainingWithActivityId($client, $this->Account->getId(), $this->computeActivityIdFor($externalId));

        $client->request(
            'POST',
            '/_internal/activity/matcher',
            [],
            [],
            [],
            $this->requestBodyFor([$externalId])
        );

        $this->assertStatusCode(200, $client);

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertTrue($responseData['matches'][$externalId]['match']);
    }

    public function testAjaxActivityMatcherDoesNotMatchActivityOfAnotherAccount(): void
    {
        $externalId = '2020-06-15T10:00:00Z';

        $client = $this->makeAuthenticatedClient();
        $this->insertTrainingWithActivityId(
            $client,
            $this->getEmptyAccount()
                ->getId(),
            $this->computeActivityIdFor($externalId),
            $this->Account->getId()
        );

        $client->request(
            'POST',
            '/_internal/activity/matcher',
            [],
            [],
            [],
            $this->requestBodyFor([$externalId])
        );

        $this->assertStatusCode(200, $client);

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertFalse($responseData['matches'][$externalId]['match']);
    }

    public function testAjaxActivityMatcherReturnsFalseForUnparseableExternalId(): void
    {
        $externalId = 'not-a-valid-date';

        $client = $this->makeAuthenticatedClient();
        $client->request(
            'POST',
            '/_internal/activity/matcher',
            [],
            [],
            [],
            $this->requestBodyFor([$externalId])
        );

        $this->assertStatusCode(200, $client);

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertFalse($responseData['matches'][$externalId]['match']);
    }

    public function testAjaxActivityMatcherHandlesMultipleExternalIdsIndependently(): void
    {
        $duplicateExternalId = '2020-06-15T10:00:00Z';
        $unknownExternalId = '2021-01-01T00:00:00Z';

        $client = $this->makeAuthenticatedClient();
        $this->insertTrainingWithActivityId($client, $this->Account->getId(), $this->computeActivityIdFor($duplicateExternalId));

        $client->request(
            'POST',
            '/_internal/activity/matcher',
            [],
            [],
            [],
            $this->requestBodyFor([$duplicateExternalId, $unknownExternalId])
        );

        $this->assertStatusCode(200, $client);

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertTrue($responseData['matches'][$duplicateExternalId]['match']);
        $this->assertFalse($responseData['matches'][$unknownExternalId]['match']);
    }

    public function testAjaxActivityMatcherReturnsEmptyMatchesForEmptyRequestBody(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('POST', '/_internal/activity/matcher', [], [], [], '');

        $this->assertStatusCode(200, $client);

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame([], $responseData['matches']);
    }
}
