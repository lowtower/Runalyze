<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Command;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Command\ActivityBulkRouteNameEvaluationCommand;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContextAdapter;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Repository\AccountRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityContextAdapterFactory;
use Symfony\Component\Console\Tester\CommandTester;

final class ActivityBulkRouteNameEvaluationCommandTest extends TestCase
{
    private AccountRepository&Stub $accountRepository;

    private TrainingRepository&Stub $trainingRepository;

    private ActivityContextAdapterFactory&Stub $activityContextAdapterFactory;

    private ActivityBulkRouteNameEvaluationCommand $command;

    protected function setUp(): void
    {
        $this->accountRepository = $this->createStub(AccountRepository::class);
        $this->trainingRepository = $this->createStub(TrainingRepository::class);
        $this->activityContextAdapterFactory = $this->createStub(ActivityContextAdapterFactory::class);

        $this->command = $this->createCommand(osmOverpassUrl: 'https://overpass.example.org');
    }

    private function createCommand(
        ?AccountRepository $accountRepository = null,
        string $osmOverpassUrl = 'https://overpass.example.org',
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
            $osmOverpassUrl,
            $trainingRepository ?? $this->trainingRepository,
            $activityContextAdapterFactory ?? $this->activityContextAdapterFactory
        );
    }

    public function testCommandConfiguration(): void
    {
        $this->assertSame('runalyze:activity:bulk-routeval', $this->command->getName());
        $this->assertSame('Bulk update of trainings route-name evaluation', $this->command->getDescription());

        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasArgument('username'));
        $this->assertTrue($definition->getArgument('username')->isRequired());

        $this->assertTrue($definition->hasArgument('id'));
        $this->assertTrue($definition->getArgument('id')->isRequired());

        $this->assertTrue($definition->hasOption('nexted'));
        $this->assertTrue($definition->hasOption('override'));
    }

    public function testExecuteFailsForUnknownUser(): void
    {
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with('ghost')
            ->willReturn(null);

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository
            ->expects($this->never())
            ->method('findForAccount');

        $command = $this->createCommand(
            accountRepository: $accountRepository,
            trainingRepository: $trainingRepository
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'username' => 'ghost',
            'id' => '123',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Unknown User', $tester->getDisplay());
    }

    public function testExecuteFailsWhenOsmOverpassUrlIsNotConfigured(): void
    {
        $account = $this->createStub(Account::class);
        $this->accountRepository->method('loadUserByUsername')
            ->willReturn($account);

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository
            ->expects($this->never())
            ->method('findForAccount');

        $command = $this->createCommand(
            osmOverpassUrl: '',
            trainingRepository: $trainingRepository
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'username' => 'runner',
            'id' => '123',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Set environment variable OSM_OVERPASS_URL', $tester->getDisplay());
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

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository
            ->expects($this->once())
            ->method('findForAccount')
            ->with(123, 42)
            ->willReturn($activity);
        $trainingRepository
            ->expects($this->once())
            ->method('getIdOfNextActivity')
            ->with($activity)
            ->willReturn(null);

        $activityContextAdapterFactory = $this->createMock(ActivityContextAdapterFactory::class);
        $activityContextAdapterFactory
            ->expects($this->never())
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

        $this->assertSame(0, $exitCode);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Start route-name evaluation', $display);
        $this->assertStringContainsString('Done.', $display);
        $this->assertStringNotContainsString('skip id=', $display);
        $this->assertStringNotContainsString('successfully updated', $display);
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

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->method('findForAccount')
            ->willReturn($activity);
        $trainingRepository->method('getIdOfNextActivity')
            ->willReturn(null);
        $trainingRepository
            ->expects($this->never())
            ->method('save');

        $activityContextAdapterFactory = $this->createMock(ActivityContextAdapterFactory::class);
        $activityContextAdapterFactory
            ->expects($this->never())
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

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('skip id=123 / Morning Run', $tester->getDisplay());
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

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository
            ->expects($this->exactly(2))
            ->method('findForAccount')
            ->willReturnMap([[100, 42, $firstActivity], [200, 42, $secondActivity]]);
        $trainingRepository
            ->expects($this->exactly(2))
            ->method('getIdOfNextActivity')
            ->willReturnMap([[$firstActivity, 200], [$secondActivity, null]]);

        $command = $this->createCommand(trainingRepository: $trainingRepository);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'username' => 'runner',
            'id' => '100',
            '--nexted' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('nexted=1', $tester->getDisplay());
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

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository
            ->expects($this->once())
            ->method('findForAccount')
            ->with(100, 42)
            ->willReturn($activity);
        $trainingRepository
            ->expects($this->once())
            ->method('getIdOfNextActivity')
            ->with($activity)
            ->willReturn(200);

        $command = $this->createCommand(trainingRepository: $trainingRepository);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'username' => 'runner',
            'id' => '100',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('nexted=0', $tester->getDisplay());
    }

    public function testExecuteHandlesMissingActivityGracefully(): void
    {
        $account = $this->createStub(Account::class);
        $account->method('getId')
            ->willReturn(42);

        $this->accountRepository->method('loadUserByUsername')
            ->willReturn($account);

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository
            ->expects($this->once())
            ->method('findForAccount')
            ->with(999, 42)
            ->willReturn(null);
        $trainingRepository
            ->expects($this->never())
            ->method('getIdOfNextActivity');

        $command = $this->createCommand(trainingRepository: $trainingRepository);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'username' => 'runner',
            'id' => '999',
        ]);

        $this->assertSame(1, $exitCode);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Activity not found: id=999', $display);
        $this->assertStringContainsString('Done.', $display);
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

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository
            ->expects($this->exactly(2))
            ->method('findForAccount')
            ->willReturnMap([[100, 42, $firstActivity], [200, 42, null]]);
        $trainingRepository
            ->expects($this->once())
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

        $this->assertSame(1, $exitCode);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Activity not found: id=200', $display);
        $this->assertStringContainsString('Done.', $display);
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

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->method('findForAccount')
            ->willReturn($activity);
        $trainingRepository->method('getIdOfNextActivity')
            ->willReturn(null);
        $trainingRepository
            ->expects($this->once())
            ->method('save')
            ->with($activity);

        // Stays a mock: it has an expectation (once)
        $adapter = $this->createMock(ActivityContextAdapter::class);
        $adapter
            ->expects($this->once())
            ->method('guessRouteDetails')
            ->willReturn(true);

        $activityContextAdapterFactory = $this->createMock(ActivityContextAdapterFactory::class);
        $activityContextAdapterFactory
            ->expects($this->once())
            ->method('getAdapterFor')
            ->with(self::callback(static fn (ActivityContext $context): bool => $context->getActivity() === $activity
                && $context->getRoute() === $route))
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

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('successfully updated id=123 / Morning Run / Nice Loop Trail', $tester->getDisplay());
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

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->method('findForAccount')
            ->willReturn($activity);
        $trainingRepository->method('getIdOfNextActivity')
            ->willReturn(null);
        $trainingRepository
            ->expects($this->never())
            ->method('save');

        // Stays a mock: it has an expectation (once)
        $adapter = $this->createMock(ActivityContextAdapter::class);
        $adapter
            ->expects($this->once())
            ->method('guessRouteDetails')
            ->willReturn(false);

        $activityContextAdapterFactory = $this->createMock(ActivityContextAdapterFactory::class);
        $activityContextAdapterFactory
            ->expects($this->once())
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

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('skip id=123 / Morning Run', $tester->getDisplay());
        $this->assertStringNotContainsString('successfully updated', $tester->getDisplay());
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

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->method('findForAccount')
            ->willReturn($activity);
        $trainingRepository->method('getIdOfNextActivity')
            ->willReturn(null);
        $trainingRepository
            ->expects($this->once())
            ->method('save')
            ->with($activity);

        // Stays a mock: it has an expectation (once)
        $adapter = $this->createMock(ActivityContextAdapter::class);
        $adapter
            ->expects($this->once())
            ->method('guessRouteDetails')
            ->willReturn(true);

        $activityContextAdapterFactory = $this->createMock(ActivityContextAdapterFactory::class);
        $activityContextAdapterFactory
            ->expects($this->once())
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

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('successfully updated id=123 / Evening Run / Updated Trail Name', $tester->getDisplay());
    }
}
