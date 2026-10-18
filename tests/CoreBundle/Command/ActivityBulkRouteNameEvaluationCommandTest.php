<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Command;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Command\ActivityBulkRouteNameEvaluationCommand;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContextAdapter;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\AccountRepository;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityContextAdapterFactory;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class ActivityBulkRouteNameEvaluationCommandTest extends TestCase
{
    private AccountRepository&Stub $accountRepository;

    private ParameterBagInterface&Stub $parameterBag;

    private TrainingRepository&Stub $trainingRepository;

    private ActivityContextAdapterFactory&Stub $activityContextAdapterFactory;

    private ActivityBulkRouteNameEvaluationCommand $command;

    protected function setUp(): void
    {
        $this->accountRepository = $this->createStub(AccountRepository::class);
        $this->parameterBag = $this->createStub(ParameterBagInterface::class);
        $this->trainingRepository = $this->createStub(TrainingRepository::class);
        $this->activityContextAdapterFactory = $this->createStub(ActivityContextAdapterFactory::class);

        $this->command = $this->createCommand();
    }

    private function createCommand(
        ?AccountRepository $accountRepository = null,
        ?ParameterBagInterface $parameterBag = null,
        ?TrainingRepository $trainingRepository = null,
        ?ActivityContextAdapterFactory $activityContextAdapterFactory = null
    ): ActivityBulkRouteNameEvaluationCommand {
        $accountRepository ??= $this->accountRepository;

        $doctrine = $this->createStub(ManagerRegistry::class);
        $doctrine
            ->method('getRepository')
            ->willReturn($accountRepository);

        return new ActivityBulkRouteNameEvaluationCommand(
            $doctrine,
            $parameterBag ?? $this->parameterBag,
            $trainingRepository ?? $this->trainingRepository,
            $activityContextAdapterFactory ?? $this->activityContextAdapterFactory
        );
    }

    public function testCommandConfiguration(): void
    {
        self::assertSame('runalyze:activity:bulk-routeval', $this->command->getName());
        self::assertSame(
            'Bulk update of trainings route-name evaluation',
            $this->command->getDescription()
        );

        $definition = $this->command->getDefinition();

        self::assertTrue($definition->hasArgument('username'));
        self::assertTrue($definition->getArgument('username')->isRequired());

        self::assertTrue($definition->hasArgument('id'));
        self::assertTrue($definition->getArgument('id')->isRequired());

        self::assertTrue($definition->hasOption('nexted'));
        self::assertTrue($definition->hasOption('override'));
    }

    public function testExecuteFailsForUnknownUser(): void
    {
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository
            ->expects(self::once())
            ->method('loadUserByUsername')
            ->with('ghost')
            ->willReturn(null);

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository
            ->expects(self::never())
            ->method('findForAccount');

        $command = $this->createCommand(accountRepository: $accountRepository, trainingRepository: $trainingRepository);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'username' => 'ghost',
            'id' => '123',
        ]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Unknown User', $tester->getDisplay());
    }

    public function testExecuteFailsWhenOsmOverpassUrlIsNotConfigured(): void
    {
        $account = $this->createStub(Account::class);
        $this->accountRepository->method('loadUserByUsername')
            ->willReturn($account);

        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag
            ->expects(self::once())
            ->method('get')
            ->with('osm_overpass_url')
            ->willReturn('');

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository
            ->expects(self::never())
            ->method('findForAccount');

        $command = $this->createCommand(parameterBag: $parameterBag, trainingRepository: $trainingRepository);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'username' => 'runner',
            'id' => '123',
        ]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Set parameter osm_overpass_url', $tester->getDisplay());
    }

    public function testExecuteSkipsSilentlyWhenActivityHasNoRoute(): void
    {
        $account = $this->createStub(Account::class);
        $account->method('getId')
            ->willReturn(42);

        $activity = $this->createStub(Training::class);
        $activity->method('hasRoute')
            ->willReturn(false);

        $this->accountRepository->method('loadUserByUsername')
            ->willReturn($account);
        // $this->parameterBag is a stub: with() has no effect on stubs (and is
        // deprecated). Only 'osm_overpass_url' is ever queried in this test.
        $this->parameterBag->method('get')
            ->willReturn('https://overpass.example.org');

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository
            ->expects(self::once())
            ->method('findForAccount')
            ->with(123, 42)
            ->willReturn($activity);
        $trainingRepository
            ->expects(self::once())
            ->method('getIdOfNextActivity')
            ->with($activity)
            ->willReturn(null);

        $activityContextAdapterFactory = $this->createMock(ActivityContextAdapterFactory::class);
        $activityContextAdapterFactory
            ->expects(self::never())
            ->method('getAdapterFor');

        $command = $this->createCommand(
            trainingRepository: $trainingRepository,
            activityContextAdapterFactory: $activityContextAdapterFactory
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'username' => 'runner',
            'id' => '123',
        ]);

        self::assertSame(0, $exitCode);
        $display = $tester->getDisplay();
        self::assertStringContainsString('Start route-name evaluation', $display);
        self::assertStringContainsString('Done.', $display);
        self::assertStringNotContainsString('skip id=', $display);
        self::assertStringNotContainsString('successfully updated', $display);
    }

    public function testExecuteSkipsActivityThatAlreadyHasARouteNameWithoutOverride(): void
    {
        $account = $this->createStub(Account::class);
        $account->method('getId')
            ->willReturn(42);

        $activity = $this->createStub(Training::class);
        $activity->method('hasRoute')
            ->willReturn(true);
        $activity->method('getRouteName')
            ->willReturn('Existing Route');
        $activity->method('getTitle')
            ->willReturn('Morning Run');

        $this->accountRepository->method('loadUserByUsername')
            ->willReturn($account);
        $this->parameterBag->method('get')
            ->willReturn('https://overpass.example.org');

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->method('findForAccount')
            ->willReturn($activity);
        $trainingRepository->method('getIdOfNextActivity')
            ->willReturn(null);
        $trainingRepository
            ->expects(self::never())
            ->method('save');

        $activityContextAdapterFactory = $this->createMock(ActivityContextAdapterFactory::class);
        $activityContextAdapterFactory
            ->expects(self::never())
            ->method('getAdapterFor');

        $command = $this->createCommand(
            trainingRepository: $trainingRepository,
            activityContextAdapterFactory: $activityContextAdapterFactory
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'username' => 'runner',
            'id' => '123',
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('skip id=123 / Morning Run', $tester->getDisplay());
    }

    public function testExecuteWithNextedOptionProcessesUntilNoNextActivityExists(): void
    {
        $account = $this->createStub(Account::class);
        $account->method('getId')
            ->willReturn(42);

        $firstActivity = $this->createStub(Training::class);
        $firstActivity->method('hasRoute')
            ->willReturn(false);

        $secondActivity = $this->createStub(Training::class);
        $secondActivity->method('hasRoute')
            ->willReturn(false);

        $this->accountRepository->method('loadUserByUsername')
            ->willReturn($account);
        $this->parameterBag->method('get')
            ->willReturn('https://overpass.example.org');

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository
            ->expects(self::exactly(2))
            ->method('findForAccount')
            ->willReturnMap([
                [100, 42, $firstActivity],
                [200, 42, $secondActivity],
            ]);
        $trainingRepository
            ->expects(self::exactly(2))
            ->method('getIdOfNextActivity')
            ->willReturnMap([
                [$firstActivity, 200],
                [$secondActivity, null],
            ]);

        $command = $this->createCommand(trainingRepository: $trainingRepository);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'username' => 'runner',
            'id' => '100',
            '--nexted' => true,
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('nexted=1', $tester->getDisplay());
    }

    public function testExecuteWithoutNextedOptionProcessesOnlyOneActivityEvenIfANextOneExists(): void
    {
        $account = $this->createStub(Account::class);
        $account->method('getId')
            ->willReturn(42);

        $activity = $this->createStub(Training::class);
        $activity->method('hasRoute')
            ->willReturn(false);

        $this->accountRepository->method('loadUserByUsername')
            ->willReturn($account);
        $this->parameterBag->method('get')
            ->willReturn('https://overpass.example.org');

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository
            ->expects(self::once())
            ->method('findForAccount')
            ->with(100, 42)
            ->willReturn($activity);
        $trainingRepository
            ->expects(self::once())
            ->method('getIdOfNextActivity')
            ->with($activity)
            ->willReturn(200);

        $command = $this->createCommand(trainingRepository: $trainingRepository);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'username' => 'runner',
            'id' => '100',
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('nexted=0', $tester->getDisplay());
    }

    public function testExecuteHandlesMissingActivityGracefully(): void
    {
        $account = $this->createStub(Account::class);
        $account->method('getId')
            ->willReturn(42);

        $this->accountRepository->method('loadUserByUsername')
            ->willReturn($account);
        $this->parameterBag->method('get')
            ->willReturn('https://overpass.example.org');

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository
            ->expects(self::once())
            ->method('findForAccount')
            ->with(999, 42)
            ->willReturn(null);
        $trainingRepository
            ->expects(self::never())
            ->method('getIdOfNextActivity');

        $command = $this->createCommand(trainingRepository: $trainingRepository);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'username' => 'runner',
            'id' => '999',
        ]);

        self::assertSame(1, $exitCode);
        $display = $tester->getDisplay();
        self::assertStringContainsString('Activity not found: id=999', $display);
        self::assertStringContainsString('Done.', $display);
    }

    public function testExecuteWithNextedOptionStopsWhenAnIntermediateActivityIsMissing(): void
    {
        $account = $this->createStub(Account::class);
        $account->method('getId')
            ->willReturn(42);

        $firstActivity = $this->createStub(Training::class);
        $firstActivity->method('hasRoute')
            ->willReturn(false);

        $this->accountRepository->method('loadUserByUsername')
            ->willReturn($account);
        $this->parameterBag->method('get')
            ->willReturn('https://overpass.example.org');

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository
            ->expects(self::exactly(2))
            ->method('findForAccount')
            ->willReturnMap([
                [100, 42, $firstActivity],
                [200, 42, null],
            ]);
        $trainingRepository
            ->expects(self::once())
            ->method('getIdOfNextActivity')
            ->with($firstActivity)
            ->willReturn(200);

        $command = $this->createCommand(trainingRepository: $trainingRepository);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'username' => 'runner',
            'id' => '100',
            '--nexted' => true,
        ]);

        self::assertSame(1, $exitCode);
        $display = $tester->getDisplay();
        self::assertStringContainsString('Activity not found: id=200', $display);
        self::assertStringContainsString('Done.', $display);
    }

    public function testExecuteSuccessfullyUpdatesRouteNameWhenNoneExistsYet(): void
    {
        $account = $this->createStub(Account::class);
        $account->method('getId')
            ->willReturn(42);

        $route = $this->createStub(Route::class);

        $activity = $this->createStub(Training::class);
        $activity->method('hasRoute')
            ->willReturn(true);
        $activity->method('getRoute')
            ->willReturn($route);
        $activity->method('getRouteName')
            ->willReturnOnConsecutiveCalls('', 'Nice Loop Trail');
        $activity->method('getTitle')
            ->willReturn('Morning Run');

        $this->accountRepository->method('loadUserByUsername')
            ->willReturn($account);
        $this->parameterBag->method('get')
            ->willReturn('https://overpass.example.org');

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->method('findForAccount')
            ->willReturn($activity);
        $trainingRepository->method('getIdOfNextActivity')
            ->willReturn(null);
        $trainingRepository
            ->expects(self::once())
            ->method('save')
            ->with($activity);

        // Stays a mock: it has an expectation (once)
        $adapter = $this->createMock(ActivityContextAdapter::class);
        $adapter
            ->expects(self::once())
            ->method('guessRouteDetails')
            ->willReturn(true);

        $activityContextAdapterFactory = $this->createMock(ActivityContextAdapterFactory::class);
        $activityContextAdapterFactory
            ->expects(self::once())
            ->method('getAdapterFor')
            ->with(self::callback(
                static fn (ActivityContext $context): bool => $context->getActivity() === $activity
                    && $context->getRoute() === $route
            ))
            ->willReturn($adapter);

        $command = $this->createCommand(
            trainingRepository: $trainingRepository,
            activityContextAdapterFactory: $activityContextAdapterFactory
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'username' => 'runner',
            'id' => '123',
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString(
            'successfully updated id=123 / Morning Run / Nice Loop Trail',
            $tester->getDisplay()
        );
    }

    public function testExecuteSkipsWhenGuessRouteDetailsFindsNothing(): void
    {
        $account = $this->createStub(Account::class);
        $account->method('getId')
            ->willReturn(42);

        $route = $this->createStub(Route::class);

        $activity = $this->createStub(Training::class);
        $activity->method('hasRoute')
            ->willReturn(true);
        $activity->method('getRoute')
            ->willReturn($route);
        $activity->method('getRouteName')
            ->willReturn('');
        $activity->method('getTitle')
            ->willReturn('Morning Run');

        $this->accountRepository->method('loadUserByUsername')
            ->willReturn($account);
        $this->parameterBag->method('get')
            ->willReturn('https://overpass.example.org');

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->method('findForAccount')
            ->willReturn($activity);
        $trainingRepository->method('getIdOfNextActivity')
            ->willReturn(null);
        $trainingRepository
            ->expects(self::never())
            ->method('save');

        // Stays a mock: it has an expectation (once)
        $adapter = $this->createMock(ActivityContextAdapter::class);
        $adapter
            ->expects(self::once())
            ->method('guessRouteDetails')
            ->willReturn(false);

        $activityContextAdapterFactory = $this->createMock(ActivityContextAdapterFactory::class);
        $activityContextAdapterFactory
            ->expects(self::once())
            ->method('getAdapterFor')
            ->willReturn($adapter);

        $command = $this->createCommand(
            trainingRepository: $trainingRepository,
            activityContextAdapterFactory: $activityContextAdapterFactory
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'username' => 'runner',
            'id' => '123',
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('skip id=123 / Morning Run', $tester->getDisplay());
        self::assertStringNotContainsString('successfully updated', $tester->getDisplay());
    }

    public function testExecuteWithOverrideOptionReEvaluatesExistingRouteName(): void
    {
        $account = $this->createStub(Account::class);
        $account->method('getId')
            ->willReturn(42);

        $route = $this->createStub(Route::class);

        $activity = $this->createStub(Training::class);
        $activity->method('hasRoute')
            ->willReturn(true);
        $activity->method('getRoute')
            ->willReturn($route);
        $activity->method('getRouteName')
            ->willReturnOnConsecutiveCalls('Old Name', 'Updated Trail Name');
        $activity->method('getTitle')
            ->willReturn('Evening Run');

        $this->accountRepository->method('loadUserByUsername')
            ->willReturn($account);
        $this->parameterBag->method('get')
            ->willReturn('https://overpass.example.org');

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->method('findForAccount')
            ->willReturn($activity);
        $trainingRepository->method('getIdOfNextActivity')
            ->willReturn(null);
        $trainingRepository
            ->expects(self::once())
            ->method('save')
            ->with($activity);

        // Stays a mock: it has an expectation (once)
        $adapter = $this->createMock(ActivityContextAdapter::class);
        $adapter
            ->expects(self::once())
            ->method('guessRouteDetails')
            ->willReturn(true);

        $activityContextAdapterFactory = $this->createMock(ActivityContextAdapterFactory::class);
        $activityContextAdapterFactory
            ->expects(self::once())
            ->method('getAdapterFor')
            ->willReturn($adapter);

        $command = $this->createCommand(
            trainingRepository: $trainingRepository,
            activityContextAdapterFactory: $activityContextAdapterFactory
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'username' => 'runner',
            'id' => '123',
            '--override' => true,
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString(
            'successfully updated id=123 / Evening Run / Updated Trail Name',
            $tester->getDisplay()
        );
    }
}
