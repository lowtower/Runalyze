<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\Internal;

use Doctrine\ORM\EntityManagerInterface;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Repository\SportRepository;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Runalyze\Util\LocalTime;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
final class ActivityMatcherControllerTest extends AbstractFixturesAwareWebTestCase
{
    private const string MATCHER_URL = '/_internal/activity/matcher';

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

    protected function insertTrainingWithActivityId(KernelBrowser $client, int $accountId, int $activityId, ?int $sportOwnerAccountId = null): Training
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $client->getContainer()
            ->get('doctrine')
            ->getManager();

        $account = $entityManager->getRepository(Account::class)->find($accountId);
        $this->assertInstanceOf(Account::class, $account, 'Account ' . $accountId . ' must exist.');

        /** @var SportRepository $sportRepository */
        $sportRepository = $entityManager->getRepository(Sport::class);
        $runningSport = $sportRepository->findOneBy([
            'account' => $sportOwnerAccountId ?? $accountId,
        ]);
        $this->assertInstanceOf(Sport::class, $runningSport, 'Account ' . ($sportOwnerAccountId ?? $accountId) . ' must own a sport.');

        $activity = new Training()
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

    /**
     * @param string[] $externalIds
     *
     * @return array<string, array<string, mixed>>
     *
     * @throws \JsonException if the response is not valid JSON
     */
    protected function requestMatchesFor(KernelBrowser $client, array $externalIds): array
    {
        $client->request('POST', self::MATCHER_URL, [], [], [], $this->requestBodyFor($externalIds));

        $this->assertStatusCode(200, $client);

        $responseData = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return $responseData['matches'];
    }

    public function testAjaxActivityMatcherRequiresAuthentication(): void
    {
        $client = $this->makeClient();
        $client->request(
            'POST',
            self::MATCHER_URL,
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

        $matches = $this->requestMatchesFor($this->makeAuthenticatedClient(), [$externalId]);

        $this->assertFalse($matches[$externalId]['match']);
    }

    public function testAjaxActivityMatcherDetectsExistingActivityAsDuplicate(): void
    {
        $externalId = '2020-06-15T10:00:00Z';

        $client = $this->makeAuthenticatedClient();
        $this->insertTrainingWithActivityId($client, $this->Account->getId(), $this->computeActivityIdFor($externalId));

        $matches = $this->requestMatchesFor($client, [$externalId]);

        $this->assertTrue($matches[$externalId]['match']);
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

        $matches = $this->requestMatchesFor($client, [$externalId]);

        $this->assertFalse($matches[$externalId]['match']);
    }

    public function testAjaxActivityMatcherReturnsFalseForUnparseableExternalId(): void
    {
        $externalId = 'not-a-valid-date';

        $matches = $this->requestMatchesFor($this->makeAuthenticatedClient(), [$externalId]);

        $this->assertFalse($matches[$externalId]['match']);
    }

    public function testAjaxActivityMatcherHandlesMultipleExternalIdsIndependently(): void
    {
        $duplicateExternalId = '2020-06-15T10:00:00Z';
        $unknownExternalId = '2021-01-01T00:00:00Z';

        $client = $this->makeAuthenticatedClient();
        $this->insertTrainingWithActivityId($client, $this->Account->getId(), $this->computeActivityIdFor($duplicateExternalId));

        $matches = $this->requestMatchesFor($client, [$duplicateExternalId, $unknownExternalId]);

        $this->assertTrue($matches[$duplicateExternalId]['match']);
        $this->assertFalse($matches[$unknownExternalId]['match']);
    }

    public function testAjaxActivityMatcherIgnoresParametersOtherThanExternalIds(): void
    {
        $externalId = '2021-01-01T00:00:00Z';

        $client = $this->makeAuthenticatedClient();
        $client->request(
            'POST',
            self::MATCHER_URL,
            [],
            [],
            [],
            'other=value&externalIds=' . urlencode($externalId) . '&another=1'
        );

        $this->assertStatusCode(200, $client);

        $responseData = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame([$externalId], array_keys($responseData['matches']));
    }

    public function testAjaxActivityMatcherReturnsEmptyMatchesForEmptyRequestBody(): void
    {
        $matches = $this->requestMatchesFor($this->makeAuthenticatedClient(), []);

        $this->assertSame([], $matches);
    }
}
