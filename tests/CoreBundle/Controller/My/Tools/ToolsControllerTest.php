<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\My\Tools;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Message\PosterGenerationMessage;
use Runalyze\Bundle\CoreBundle\Tests\DataFixtures\AbstractFixturesAwareWebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
#[\PHPUnit\Framework\Attributes\Group('requiresDoctrine')]
#[\PHPUnit\Framework\Attributes\Group('requiresClient')]
class ToolsControllerTest extends AbstractFixturesAwareWebTestCase
{
    protected Account $Account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Account = $this->getDefaultAccount();
    }

    protected function getPathToPosterFiles(): string
    {
        return self::getContainer()->getParameter('app.data_directory') . '/poster/';
    }

    protected function getTransportFromClientKernel(KernelBrowser $client): InMemoryTransport
    {
        $container = $client->getKernel()
            ->getContainer();

        if ($container->has('test.service_container')) {
            $container = $container->get('test.service_container');
        }

        $transport = $container->get('messenger.transport.async');
        self::assertInstanceOf(InMemoryTransport::class, $transport);

        return $transport;
    }

    protected function insertActivityForSportAndYear(Sport $sport, int $year): void
    {
        $activity = new Training()
            ->setSport($sport)
            ->setTime(mktime(12, 0, 0, 6, 15, $year))
            ->setS(3600.0)
            ->setDistance(10.0)
            ->setAccount($this->Account);

        $this->EntityManager->persist($activity);
        $this->EntityManager->flush();
    }

    protected function findOptionValueByText(Crawler $options, string $text): string
    {
        return $options->reduce(fn ($node): bool => trim($node->text()) === $text)
            ->first()
            ->attr('value');
    }

    /**
     * @return array<string, mixed>
     */
    protected function posterFormData(string $token, string $yearOptionValue, string $sportOptionValue): array
    {
        return [
            'poster' => [
                '_token' => $token,
                'postertype' => ['heatmap'],
                'year' => $yearOptionValue,
                'title' => 'Test',
                'sport' => $sportOptionValue,
                'size' => '4000',
                'backgroundColor' => '#222222',
                'trackColor' => '#4DD2FF',
                'textColor' => '#FFFFFF',
                'raceColor' => '#FFFF00',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \JsonException if the response is not valid JSON
     */
    protected function decodeJsonResponse(KernelBrowser $client): array
    {
        return json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function testOverviewActionRequiresAuthentication(): void
    {
        $client = $this->makeClient();
        $client->request('GET', '/my/tools');

        $this->isSuccessful($client->getResponse(), false);
    }

    public function testOverviewActionIsAccessibleForLoggedInUser(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/tools');

        $this->assertStatusCode(200, $client);
    }

    public function testTableGeneralPaceActionRequiresAuthentication(): void
    {
        $client = $this->makeClient();
        $client->request('GET', '/my/tools/tables/general-pace');

        $this->isSuccessful($client->getResponse(), false);
    }

    public function testTableGeneralPaceActionIsAccessibleForLoggedInUser(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/tools/tables/general-pace');

        $this->assertStatusCode(200, $client);
    }

    public function testTableVo2maxRaceResultActionRequiresAuthentication(): void
    {
        $client = $this->makeClient();
        $client->request('GET', '/my/tools/tables/vo2max');

        $this->isSuccessful($client->getResponse(), false);
    }

    public function testTableVo2maxRaceResultActionIsAccessibleForLoggedInUser(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/tools/tables/vo2max');

        $this->assertStatusCode(200, $client);
    }

    public function testTableVo2maxRaceResultActionIsAlsoReachableViaShortRoute(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/tools/tables');

        $this->assertStatusCode(200, $client);
    }

    public function testTableVo2maxPaceActionRequiresAuthentication(): void
    {
        $client = $this->makeClient();
        $client->request('GET', '/my/tools/tables/vo2max-pace');

        $this->isSuccessful($client->getResponse(), false);
    }

    public function testTableVo2maxPaceActionIsAccessibleForLoggedInUser(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/tools/tables/vo2max-pace');

        $this->assertStatusCode(200, $client);
    }

    public function testAnovaActionRequiresAuthentication(): void
    {
        $client = $this->makeClient();
        $client->request('GET', '/my/tools/anova');

        $this->isSuccessful($client->getResponse(), false);
    }

    public function testAnovaActionIsAccessibleForLoggedInUser(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/tools/anova');

        $this->assertStatusCode(200, $client);
    }

    public function testAnovaActionReturnsResultsForValidSubmission(): void
    {
        $sport = $this->getDefaultAccountsRunningSport();

        $client = $this->makeAuthenticatedClient();
        $crawler = $client->request('GET', '/my/tools/anova');

        $token = $crawler->filter('input[name="anova[_token]"]')
            ->attr('value');
        $sportOptionValue = $crawler->filter('#anova_sport option[data-id="' . $sport->getId() . '"]')->attr('value');
        $groupByValue = $crawler->filter('#anova_valueToGroupBy option')
            ->first()
            ->attr('value');
        $lookAtValue = $crawler->filter('#anova_valueToLookAt option')
            ->first()
            ->attr('value');

        $client->request('POST', '/my/tools/anova', [
            'anova' => [
                '_token' => $token,
                'dateFrom' => '01.01.2020',
                'dateTo' => '31.12.2026',
                'sport' => [$sportOptionValue],
                'type' => [],
                'valueToGroupBy' => $groupByValue,
                'valueToLookAt' => $lookAtValue,
            ],
        ]);

        $this->assertStatusCode(200, $client);

        $responseData = $this->decodeJsonResponse($client);

        $this->assertArrayHasKey('tickFormatter', $responseData);
        $this->assertArrayHasKey('groups', $responseData);
    }

    public function testAnovaActionReturnsProblemJsonForInvalidSubmission(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('POST', '/my/tools/anova', [
            'anova' => [
                '_token' => 'invalid-token',
            ],
        ]);

        $this->assertStatusCode(200, $client);
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'status' => 'There was a problem.',
            ]),
            (string) $client->getResponse()
                ->getContent()
        );
    }

    public function testTrendAnalysisActionRequiresAuthentication(): void
    {
        $client = $this->makeClient();
        $client->request('GET', '/my/tools/trend-analysis');

        $this->isSuccessful($client->getResponse(), false);
    }

    public function testTrendAnalysisActionIsAccessibleForLoggedInUser(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/tools/trend-analysis');

        $this->assertStatusCode(200, $client);
    }

    public function testTrendAnalysisActionReturnsResultsForValidSubmission(): void
    {
        $sport = $this->getDefaultAccountsRunningSport();

        $client = $this->makeAuthenticatedClient();
        $crawler = $client->request('GET', '/my/tools/trend-analysis');

        $token = $crawler->filter('input[name="trend_analysis[_token]"]')
            ->attr('value');
        $sportOptionValue = $crawler->filter('#trend_analysis_sport option[data-id="' . $sport->getId() . '"]')->attr('value');
        $lookAtValue = $crawler->filter('#trend_analysis_valueToLookAt option')
            ->first()
            ->attr('value');

        $client->request('POST', '/my/tools/trend-analysis', [
            'trend_analysis' => [
                '_token' => $token,
                'dateFrom' => '01.01.2020',
                'dateTo' => '31.12.2026',
                'sport' => [$sportOptionValue],
                'type' => [],
                'valueToLookAt' => $lookAtValue,
            ],
        ]);

        $this->assertStatusCode(200, $client);

        $responseData = $this->decodeJsonResponse($client);

        $this->assertArrayHasKey('tickFormatter', $responseData);
        $this->assertArrayHasKey('values', $responseData);
    }

    public function testTrendAnalysisActionReturnsProblemJsonForInvalidSubmission(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('POST', '/my/tools/trend-analysis', [
            'trend_analysis' => [
                '_token' => 'invalid-token',
            ],
        ]);

        $this->assertStatusCode(200, $client);
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'status' => 'There was a problem.',
            ]),
            (string) $client->getResponse()
                ->getContent()
        );
    }

    public function testPosterActionRequiresAuthentication(): void
    {
        $client = $this->makeClient();
        $client->request('GET', '/my/tools/poster');

        $this->isSuccessful($client->getResponse(), false);
    }

    public function testPosterActionIsAccessibleForLoggedInUser(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/tools/poster');

        $this->assertStatusCode(200, $client);
    }

    public function testPosterActionQueuesGenerationForValidSubmissionWithEnoughActivities(): void
    {
        $runningSport = $this->getDefaultAccountsRunningSport();
        $year = (int) date('Y') - 2;

        $this->insertActivityForSportAndYear($runningSport, $year);
        $this->insertActivityForSportAndYear($runningSport, $year);

        $client = $this->makeAuthenticatedClient();
        $crawler = $client->request('GET', '/my/tools/poster');

        $token = $crawler->filter('input[name="poster[_token]"]')
            ->attr('value');
        $sportOptionValue = $this->findOptionValueByText($crawler->filter('#poster_sport option'), $runningSport->getName());
        $yearOptionValue = $this->findOptionValueByText($crawler->filter('#poster_year option'), (string) $year);

        $client->request('POST', '/my/tools/poster', $this->posterFormData($token, $yearOptionValue, $sportOptionValue));

        $this->assertStatusCode(200, $client);

        $envelopes = $this->getTransportFromClientKernel($client)
            ->getSent();

        $this->assertNotEmpty($envelopes, 'Expected a poster generation message to have been dispatched.');

        $message = $envelopes[array_key_last($envelopes)]->getMessage();
        $this->assertInstanceOf(PosterGenerationMessage::class, $message);
        $this->assertSame($this->Account->getId(), $message->getAccountId());
        $this->assertSame($year, $message->getYear());
    }

    public function testPosterActionShowsFlashErrorWhenNotEnoughActivitiesForChosenSport(): void
    {
        $runningSport = $this->getDefaultAccountsRunningSport();
        $cyclingSport = $this->getDefaultAccountsCyclingSport();
        $year = (int) date('Y') - 3;

        $this->insertActivityForSportAndYear($runningSport, $year);
        $this->insertActivityForSportAndYear($cyclingSport, $year);

        $client = $this->makeAuthenticatedClient();
        $crawler = $client->request('GET', '/my/tools/poster');

        $token = $crawler->filter('input[name="poster[_token]"]')
            ->attr('value');
        $sportOptionValue = $this->findOptionValueByText($crawler->filter('#poster_sport option'), $runningSport->getName());
        $yearOptionValue = $this->findOptionValueByText($crawler->filter('#poster_year option'), (string) $year);

        $transport = $this->getTransportFromClientKernel($client);
        $envelopesBefore = count($transport->getSent());

        $client->request('POST', '/my/tools/poster', $this->posterFormData($token, $yearOptionValue, $sportOptionValue));

        $this->assertStatusCode(200, $client);
        $this->assertCount(
            $envelopesBefore,
            $transport->getSent(),
            'Expected no poster generation message to have been dispatched when there are not enough activities.'
        );
    }

    public function testPosterActionShowsNoPostersMessageWhenListIsEmpty(): void
    {
        $client = $this->makeAuthenticatedClient();
        $crawler = $client->request('GET', '/my/tools/poster');

        $this->assertStatusCode(200, $client);
        $this->assertStringContainsString('no posters so far', $crawler->filter('.panel-content')->last()->text());
    }

    public function testPosterActionListsPreviouslyGeneratedPosterFile(): void
    {
        $filesystem = new Filesystem();
        $posterPath = $this->getPathToPosterFiles();
        $residualFilename = 'test-poster-heatmap.png';
        $internalFilename = $this->Account->getId() . '-' . $residualFilename;

        $filesystem->mkdir($posterPath);
        $filesystem->dumpFile($posterPath . $internalFilename, 'fake poster content');

        try {
            $client = $this->makeAuthenticatedClient();
            $crawler = $client->request('GET', '/my/tools/poster');

            $this->assertStatusCode(200, $client);
            $this->assertStringContainsString($residualFilename, $crawler->filter('.panel-content')->last()->text());
        } finally {
            $filesystem->remove($posterPath . $internalFilename);
        }
    }

    public function testPosterDownloadActionRequiresAuthentication(): void
    {
        $client = $this->makeClient();
        $client->request('GET', '/my/tools/poster/does-not-exist.png');

        $this->isSuccessful($client->getResponse(), false);
    }

    public function testPosterDownloadActionThrowsNotFoundForMissingFile(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/tools/poster/does-not-exist.png');

        $this->assertStatusCode(404, $client);
    }

    public function testPosterDownloadActionSucceedsForOwnFile(): void
    {
        $filesystem = new Filesystem();
        $posterPath = $this->getPathToPosterFiles();
        $residualFilename = 'test-download-poster.png';
        $internalFilename = $this->Account->getId() . '-' . $residualFilename;

        $filesystem->mkdir($posterPath);
        $filesystem->dumpFile($posterPath . $internalFilename, 'fake poster content');

        try {
            $client = $this->makeAuthenticatedClient();
            $client->request('GET', '/my/tools/poster/' . $residualFilename);

            $this->assertStatusCode(200, $client);
            $this->assertSame('image/png', $client->getResponse()->headers->get('Content-type'));
            $this->assertSame('fake poster content', $client->getResponse()->getContent());
        } finally {
            $filesystem->remove($posterPath . $internalFilename);
        }
    }

    public function testPosterDownloadActionDoesNotServeFileOfAnotherAccount(): void
    {
        $filesystem = new Filesystem();
        $posterPath = $this->getPathToPosterFiles();
        $residualFilename = 'test-foreign-poster.png';
        $internalFilename = $this->getEmptyAccount()
            ->getId() . '-' . $residualFilename;

        $filesystem->mkdir($posterPath);
        $filesystem->dumpFile($posterPath . $internalFilename, 'foreign poster content');

        try {
            $client = $this->makeAuthenticatedClient();
            $client->request('GET', '/my/tools/poster/' . $residualFilename);

            $this->assertStatusCode(404, $client);
        } finally {
            $filesystem->remove($posterPath . $internalFilename);
        }
    }

    public function testVo2maxAnalysisActionRequiresAuthentication(): void
    {
        $client = $this->makeClient();
        $client->request('GET', '/my/tools/vo2max-analysis');

        $this->isSuccessful($client->getResponse(), false);
    }

    public function testVo2maxAnalysisActionRendersEmptyResultsForAccountWithoutRaceResults(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/tools/vo2max-analysis');

        $this->assertStatusCode(200, $client);
    }

    public function testCleanupActionRequiresAuthentication(): void
    {
        $client = $this->makeClient();
        $client->request('GET', '/my/tools/cleanup');

        $this->isSuccessful($client->getResponse(), false);
    }

    public function testCleanupActionIsAccessibleForLoggedInUser(): void
    {
        $client = $this->makeAuthenticatedClient();
        $client->request('GET', '/my/tools/cleanup');

        $this->assertStatusCode(200, $client);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function cleanupModes(): iterable
    {
        yield 'general mode' => ['general'];
        yield 'loop mode' => ['loop'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('cleanupModes')]
    public function testCleanupActionRendersResultsForModeWithNoOptionsSelected(string $mode): void
    {
        $client = $this->makeAuthenticatedClient();
        $crawler = $client->request('GET', '/my/tools/cleanup');

        $token = $crawler->filter('input[name="database_cleanup[_token]"]')
            ->attr('value');

        $client->request('POST', '/my/tools/cleanup', [
            'database_cleanup' => [
                '_token' => $token,
                'mode' => $mode,
            ],
        ]);

        $this->assertStatusCode(200, $client);
    }
}
