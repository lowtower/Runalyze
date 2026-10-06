<?php

namespace Tests\Runalyze\Bundle\CoreBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
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
    private ManagerRegistry&MockObject $doctrine;

    private ParameterBagInterface&MockObject $parameterBag;

    private TrainingRepository&MockObject $trainingRepository;

    private ActivityContextAdapterFactory&MockObject $activityContextAdapterFactory;

    private AccountRepository&MockObject $accountRepository;

    private ActivityBulkRouteNameEvaluationCommand $command;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->trainingRepository = $this->createMock(TrainingRepository::class);
        $this->activityContextAdapterFactory = $this->createMock(ActivityContextAdapterFactory::class);
        $this->accountRepository = $this->createMock(AccountRepository::class);

        $this->doctrine
            ->method('getRepository')
            ->with(Account::class)
            ->willReturn($this->accountRepository);

        $this->command = new ActivityBulkRouteNameEvaluationCommand(
            $this->doctrine,
            $this->parameterBag,
            $this->trainingRepository,
            $this->activityContextAdapterFactory
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
        $this->accountRepository
            ->expects(self::once())
            ->method('loadUserByUsername')
            ->with('ghost')
            ->willReturn(null);

        $this->trainingRepository
            ->expects(self::never())
            ->method('findForAccount');

        $tester = new CommandTester($this->command);
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

        $this->accountRepository
            ->method('loadUserByUsername')
            ->willReturn($account);

        $this->parameterBag
            ->expects(self::once())
            ->method('get')
            ->with('osm_overpass_url')
            ->willReturn('');

        $this->trainingRepository
            ->expects(self::never())
            ->method('findForAccount');

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute([
            'username' => 'runner',
            'id' => '123',
        ]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Set parameter osm_overpass_url', $tester->getDisplay());
    }

    public function testExecuteSkipsSilentlyWhenActivityHasNoRoute(): void
    {
        $account = $this->createMock(Account::class);
        $account->method('getId')
            ->willReturn(42);

        $activity = $this->createMock(Training::class);
        $activity->method('hasRoute')
            ->willReturn(false);

        $this->accountRepository
            ->method('loadUserByUsername')
            ->willReturn($account);

        $this->parameterBag
            ->method('get')
            ->with('osm_overpass_url')
            ->willReturn('https://overpass.example.org');

        $this->trainingRepository
            ->expects(self::once())
            ->method('findForAccount')
            ->with(123, 42)
            ->willReturn($activity);

        $this->trainingRepository
            ->expects(self::once())
            ->method('getIdOfNextActivity')
            ->with($activity)
            ->willReturn(null);

        $this->activityContextAdapterFactory
            ->expects(self::never())
            ->method('getAdapterFor');

        $tester = new CommandTester($this->command);
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
        $account = $this->createMock(Account::class);
        $account->method('getId')
            ->willReturn(42);

        $activity = $this->createMock(Training::class);
        $activity->method('hasRoute')
            ->willReturn(true);
        $activity->method('getRouteName')
            ->willReturn('Existing Route');
        $activity->method('getTitle')
            ->willReturn('Morning Run');

        $this->accountRepository
            ->method('loadUserByUsername')
            ->willReturn($account);

        $this->parameterBag
            ->method('get')
            ->willReturn('https://overpass.example.org');

        $this->trainingRepository
            ->method('findForAccount')
            ->willReturn($activity);

        $this->trainingRepository
            ->method('getIdOfNextActivity')
            ->willReturn(null);

        $this->trainingRepository
            ->expects(self::never())
            ->method('save');

        $this->activityContextAdapterFactory
            ->expects(self::never())
            ->method('getAdapterFor');

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute([
            'username' => 'runner',
            'id' => '123',
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('skip id=123 / Morning Run', $tester->getDisplay());
    }

    public function testExecuteWithNextedOptionProcessesUntilNoNextActivityExists(): void
    {
        $account = $this->createMock(Account::class);
        $account->method('getId')
            ->willReturn(42);

        $firstActivity = $this->createMock(Training::class);
        $firstActivity->method('hasRoute')
            ->willReturn(false);

        $secondActivity = $this->createMock(Training::class);
        $secondActivity->method('hasRoute')
            ->willReturn(false);

        $this->accountRepository
            ->method('loadUserByUsername')
            ->willReturn($account);

        $this->parameterBag
            ->method('get')
            ->willReturn('https://overpass.example.org');

        $this->trainingRepository
            ->expects(self::exactly(2))
            ->method('findForAccount')
            ->willReturnMap([
                [100, 42, $firstActivity],
                [200, 42, $secondActivity],
            ]);

        $this->trainingRepository
            ->expects(self::exactly(2))
            ->method('getIdOfNextActivity')
            ->willReturnMap([
                [$firstActivity, 200],
                [$secondActivity, null],
            ]);

        $tester = new CommandTester($this->command);
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
        $account = $this->createMock(Account::class);
        $account->method('getId')
            ->willReturn(42);

        $activity = $this->createMock(Training::class);
        $activity->method('hasRoute')
            ->willReturn(false);

        $this->accountRepository
            ->method('loadUserByUsername')
            ->willReturn($account);

        $this->parameterBag
            ->method('get')
            ->willReturn('https://overpass.example.org');

        $this->trainingRepository
            ->expects(self::once())
            ->method('findForAccount')
            ->with(100, 42)
            ->willReturn($activity);

        $this->trainingRepository
            ->expects(self::once())
            ->method('getIdOfNextActivity')
            ->with($activity)
            ->willReturn(200);

        $tester = new CommandTester($this->command);
        $exitCode = $tester->execute([
            'username' => 'runner',
            'id' => '100',
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('nexted=0', $tester->getDisplay());
    }

    public function testExecuteHandlesMissingActivityGracefully(): void
    {
        $account = $this->createMock(Account::class);
        $account->method('getId')
            ->willReturn(42);

        $this->accountRepository
            ->method('loadUserByUsername')
            ->willReturn($account);

        $this->parameterBag
            ->method('get')
            ->willReturn('https://overpass.example.org');

        $this->trainingRepository
            ->expects(self::once())
            ->method('findForAccount')
            ->with(999, 42)
            ->willReturn(null);

        $this->trainingRepository
            ->expects(self::never())
            ->method('getIdOfNextActivity');

        $tester = new CommandTester($this->command);
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
        $account = $this->createMock(Account::class);
        $account->method('getId')
            ->willReturn(42);

        $firstActivity = $this->createMock(Training::class);
        $firstActivity->method('hasRoute')
            ->willReturn(false);

        $this->accountRepository
            ->method('loadUserByUsername')
            ->willReturn($account);

        $this->parameterBag
            ->method('get')
            ->willReturn('https://overpass.example.org');

        $this->trainingRepository
            ->expects(self::exactly(2))
            ->method('findForAccount')
            ->willReturnMap([
                [100, 42, $firstActivity],
                [200, 42, null],
            ]);

        $this->trainingRepository
            ->expects(self::once())
            ->method('getIdOfNextActivity')
            ->with($firstActivity)
            ->willReturn(200);

        $tester = new CommandTester($this->command);
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
        $account = $this->createMock(Account::class);
        $account->method('getId')
            ->willReturn(42);

        $route = $this->createStub(Route::class);

        $activity = $this->createMock(Training::class);
        $activity->method('hasRoute')
            ->willReturn(true);
        $activity->method('getRoute')
            ->willReturn($route);
        $activity->method('getRouteName')
            ->willReturnOnConsecutiveCalls('', 'Nice Loop Trail');
        $activity->method('getTitle')
            ->willReturn('Morning Run');

        $this->accountRepository
            ->method('loadUserByUsername')
            ->willReturn($account);

        $this->parameterBag
            ->method('get')
            ->willReturn('https://overpass.example.org');

        $this->trainingRepository
            ->method('findForAccount')
            ->willReturn($activity);

        $this->trainingRepository
            ->method('getIdOfNextActivity')
            ->willReturn(null);

        $adapter = $this->createMock(ActivityContextAdapter::class);
        $adapter
            ->expects(self::once())
            ->method('guessRouteDetails')
            ->willReturn(true);

        $this->activityContextAdapterFactory
            ->expects(self::once())
            ->method('getAdapterFor')
            ->with(self::callback(
                static fn (ActivityContext $context): bool => $context->getActivity() === $activity
                    && $context->getRoute() === $route
            ))
            ->willReturn($adapter);

        $this->trainingRepository
            ->expects(self::once())
            ->method('save')
            ->with($activity);

        $tester = new CommandTester($this->command);
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
        $account = $this->createMock(Account::class);
        $account->method('getId')
            ->willReturn(42);

        $route = $this->createStub(Route::class);

        $activity = $this->createMock(Training::class);
        $activity->method('hasRoute')
            ->willReturn(true);
        $activity->method('getRoute')
            ->willReturn($route);
        $activity->method('getRouteName')
            ->willReturn('');
        $activity->method('getTitle')
            ->willReturn('Morning Run');

        $this->accountRepository
            ->method('loadUserByUsername')
            ->willReturn($account);

        $this->parameterBag
            ->method('get')
            ->willReturn('https://overpass.example.org');

        $this->trainingRepository
            ->method('findForAccount')
            ->willReturn($activity);

        $this->trainingRepository
            ->method('getIdOfNextActivity')
            ->willReturn(null);

        $adapter = $this->createMock(ActivityContextAdapter::class);
        $adapter
            ->expects(self::once())
            ->method('guessRouteDetails')
            ->willReturn(false);

        $this->activityContextAdapterFactory
            ->expects(self::once())
            ->method('getAdapterFor')
            ->willReturn($adapter);

        $this->trainingRepository
            ->expects(self::never())
            ->method('save');

        $tester = new CommandTester($this->command);
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
        $account = $this->createMock(Account::class);
        $account->method('getId')
            ->willReturn(42);

        $route = $this->createStub(Route::class);

        $activity = $this->createMock(Training::class);
        $activity->method('hasRoute')
            ->willReturn(true);
        $activity->method('getRoute')
            ->willReturn($route);
        $activity->method('getRouteName')
            ->willReturnOnConsecutiveCalls('Old Name', 'Updated Trail Name');
        $activity->method('getTitle')
            ->willReturn('Evening Run');

        $this->accountRepository
            ->method('loadUserByUsername')
            ->willReturn($account);

        $this->parameterBag
            ->method('get')
            ->willReturn('https://overpass.example.org');

        $this->trainingRepository
            ->method('findForAccount')
            ->willReturn($activity);

        $this->trainingRepository
            ->method('getIdOfNextActivity')
            ->willReturn(null);

        $adapter = $this->createMock(ActivityContextAdapter::class);
        $adapter
            ->expects(self::once())
            ->method('guessRouteDetails')
            ->willReturn(true);

        $this->activityContextAdapterFactory
            ->expects(self::once())
            ->method('getAdapterFor')
            ->willReturn($adapter);

        $this->trainingRepository
            ->expects(self::once())
            ->method('save')
            ->with($activity);

        $tester = new CommandTester($this->command);
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
