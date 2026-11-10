<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Controller\Internal;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Controller\Internal\ActivityMatcherController;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class ActivityMatcherControllerUnitTest extends TestCase
{
    private const string MATCHING_ID = '2020-06-15T10:00:00Z';

    // Stubs by default. Tests that need expects() build their own mock
    // and create the controller again with createController().
    private TrainingRepository&Stub $trainingRepository;

    private ConfigurationManager&Stub $configurationManager;

    private RunalyzeConfigurationList $configurationList;

    private Container $defaultContainer;

    private ActivityMatcherController $controller;

    protected function setUp(): void
    {
        $this->trainingRepository = $this->createStub(TrainingRepository::class);
        $this->configurationList = new RunalyzeConfigurationList();
        $this->configurationManager = $this->createStub(ConfigurationManager::class);
        $this->configurationManager->method('getList')
            ->willReturn($this->configurationList);

        $authorizationChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $authorizationChecker->method('isGranted')
            ->willReturn(true);
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new UsernamePasswordToken($this->createStub(Account::class), 'default', ['ROLE_USER']));

        $this->defaultContainer = new Container();
        $this->defaultContainer->set('security.authorization_checker', $authorizationChecker);
        $this->defaultContainer->set('security.token_storage', $tokenStorage);

        $this->controller = $this->createController($this->trainingRepository);
    }

    private function createController(TrainingRepository $trainingRepository, ?Container $container = null): ActivityMatcherController
    {
        $controller = new ActivityMatcherController($trainingRepository, $this->configurationManager);
        $controller->setContainer($container ?? $this->defaultContainer);

        return $controller;
    }

    /**
     * @param string[] $externalIds
     *
     * @return array<string, array{match: bool}>
     */
    private function requestMatchesFor(array $externalIds): array
    {
        $body = implode('&', array_map(
            static fn (string $id): string => 'externalIds=' . urlencode($id),
            $externalIds
        ));

        $response = $this->controller->ajaxActivityMatcher(Request::create('/_internal/activity/matcher', 'POST', [], [], [], [], $body));

        return json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR)['matches'];
    }

    private function ignoreActivities(string $commaSeparatedExternalIds): void
    {
        $this->configurationList->set('activity-form.GARMIN_IGNORE_IDS', $commaSeparatedExternalIds);
    }

    public function testAccessIsDeniedWhenUserIsNotLoggedIn(): void
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(false);

        $container = new Container();
        $container->set('security.authorization_checker', $authorizationChecker);
        $container->set('security.token_storage', new TokenStorage());

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->expects($this->never())
            ->method('isPossibleDuplicate');

        $this->controller = $this->createController($trainingRepository, $container);

        $this->expectException(AccessDeniedException::class);

        $this->requestMatchesFor([self::MATCHING_ID]);
    }

    public function testActivityIsNoMatchWhenItIsNeitherDuplicateNorIgnored(): void
    {
        $this->trainingRepository->method('isPossibleDuplicate')
            ->willReturn(false);

        $this->assertSame([
            self::MATCHING_ID => [
                'match' => false,
            ],
        ], $this->requestMatchesFor([self::MATCHING_ID]));
    }

    public function testActivityIsMatchWhenRepositoryFindsDuplicate(): void
    {
        $this->trainingRepository->method('isPossibleDuplicate')
            ->willReturn(true);

        $this->assertTrue($this->requestMatchesFor([self::MATCHING_ID])[self::MATCHING_ID]['match']);
    }

    public function testRepositoryReceivesTrainingOfLoggedInAccountAtParsedTime(): void
    {
        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->expects($this->once())
            ->method('isPossibleDuplicate')
            ->with(self::callback(static fn (Training $training): bool => $training->getAccount() instanceof Account
                && is_int($training->getTime())))
            ->willReturn(false);

        $this->controller = $this->createController($trainingRepository);

        $this->requestMatchesFor([self::MATCHING_ID]);
    }

    public function testActivityIsMatchWhenItIsOnTheIgnoreList(): void
    {
        $this->trainingRepository->method('isPossibleDuplicate')
            ->willReturn(false);
        $this->ignoreActivities(self::MATCHING_ID . ',2013-02-28T08:29:54Z');

        $this->assertTrue($this->requestMatchesFor([self::MATCHING_ID])[self::MATCHING_ID]['match']);
    }

    public function testActivityWithinTheSameMinuteAsIgnoredActivityIsMatch(): void
    {
        $this->trainingRepository->method('isPossibleDuplicate')
            ->willReturn(false);
        $this->ignoreActivities(self::MATCHING_ID);

        $this->assertTrue($this->requestMatchesFor(['2020-06-15T10:00:45Z'])['2020-06-15T10:00:45Z']['match']);
    }

    public function testActivityInDifferentMinuteThanIgnoredActivityIsNoMatch(): void
    {
        $this->trainingRepository->method('isPossibleDuplicate')
            ->willReturn(false);
        $this->ignoreActivities(self::MATCHING_ID);

        $this->assertFalse($this->requestMatchesFor(['2020-06-15T10:01:00Z'])['2020-06-15T10:01:00Z']['match']);
    }

    public function testUnparseableEntriesOnTheIgnoreListAreSkipped(): void
    {
        $this->trainingRepository->method('isPossibleDuplicate')
            ->willReturn(false);
        $this->ignoreActivities('not-a-valid-date,' . self::MATCHING_ID);

        $this->assertTrue($this->requestMatchesFor([self::MATCHING_ID])[self::MATCHING_ID]['match']);
    }

    public function testUnparseableExternalIdIsNoMatchAndDoesNotAskRepository(): void
    {
        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->expects($this->never())
            ->method('isPossibleDuplicate');
        $this->controller = $this->createController($trainingRepository);

        $this->ignoreActivities('not-a-valid-date');

        $this->assertFalse($this->requestMatchesFor(['not-a-valid-date'])['not-a-valid-date']['match']);
    }

    public function testRequestWithoutExternalIdsReturnsNoMatches(): void
    {
        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->expects($this->never())
            ->method('isPossibleDuplicate');
        $this->controller = $this->createController($trainingRepository);

        $this->assertSame([], $this->requestMatchesFor([]));
    }

    public function testEveryExternalIdIsEvaluatedIndependently(): void
    {
        $this->trainingRepository->method('isPossibleDuplicate')
            ->willReturn(false);
        $this->ignoreActivities(self::MATCHING_ID);

        $matches = $this->requestMatchesFor([self::MATCHING_ID, '2021-01-01T00:00:00Z']);

        $this->assertTrue($matches[self::MATCHING_ID]['match']);
        $this->assertFalse($matches['2021-01-01T00:00:00Z']['match']);
    }
}
