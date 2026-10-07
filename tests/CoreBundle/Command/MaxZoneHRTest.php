<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Command;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Command\ActivityBulkImportCommand;
use Runalyze\Bundle\CoreBundle\Command\MaxZoneHR;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\User;
use Runalyze\Bundle\CoreBundle\Entity\UserRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityContextAdapterFactory;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityDataContainerFilter;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityDataContainerToActivityContextConverter;
use Runalyze\Bundle\CoreBundle\Services\Import\FileImporter;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class MaxZoneHRTest extends TestCase
{
    private function createMaxZoneHRWithImportFlag(bool $import): MaxZoneHR
    {
        $reflectionClass = new \ReflectionClass(MaxZoneHR::class);
        $instance = $reflectionClass->newInstanceWithoutConstructor();

        $importProperty = $reflectionClass->getProperty('import');
        $importProperty->setValue($instance, $import);

        return $instance;
    }

    private function createMainSportActivity(bool $isMain): Stub&Training
    {
        $sport = $this->createStub(Sport::class);
        $sport->method('isMain')
            ->willReturn($isMain);

        $activity = $this->createStub(Training::class);
        $activity->method('getSport')
            ->willReturn($sport);

        return $activity;
    }

    private function createContainer(?int $maxHeartRateUser, int $timestamp): StubActivityDataContainer
    {
        return new StubActivityDataContainer($maxHeartRateUser, $timestamp);
    }

    public function testIsCollectedIsFalseInitially(): void
    {
        $maxZoneHR = $this->createMaxZoneHRWithImportFlag(true);

        self::assertFalse($maxZoneHR->isCollected());
        self::assertSame(0, $maxZoneHR->MaxHR);
        self::assertNull($maxZoneHR->Time);
    }

    public function testCollectDoesNothingWhenImportIsDisabled(): void
    {
        $maxZoneHR = $this->createMaxZoneHRWithImportFlag(false);

        $activity = $this->createMainSportActivity(true);
        $container = $this->createContainer(190, 1_700_000_000);

        $maxZoneHR->collect($activity, $container);

        self::assertFalse($maxZoneHR->isCollected());
        self::assertSame(0, $maxZoneHR->MaxHR);
    }

    public function testCollectDoesNothingWhenActivityHasNoGetSportMethod(): void
    {
        $maxZoneHR = $this->createMaxZoneHRWithImportFlag(true);

        $activity = new \stdClass();
        $container = $this->createContainer(190, 1_700_000_000);

        $maxZoneHR->collect($activity, $container);

        self::assertFalse($maxZoneHR->isCollected());
    }

    public function testCollectDoesNothingWhenSportIsNotMain(): void
    {
        $maxZoneHR = $this->createMaxZoneHRWithImportFlag(true);

        $activity = $this->createMainSportActivity(false);
        $container = $this->createContainer(190, 1_700_000_000);

        $maxZoneHR->collect($activity, $container);

        self::assertFalse($maxZoneHR->isCollected());
    }

    public function testCollectDoesNothingWhenMaxHeartRateUserIsNotSet(): void
    {
        $maxZoneHR = $this->createMaxZoneHRWithImportFlag(true);

        $activity = $this->createMainSportActivity(true);
        $container = $this->createContainer(null, 1_700_000_000);

        $maxZoneHR->collect($activity, $container);

        self::assertFalse($maxZoneHR->isCollected());
    }

    public function testCollectStoresHeartRateAndTimestampWhenAllConditionsAreMet(): void
    {
        $maxZoneHR = $this->createMaxZoneHRWithImportFlag(true);

        $activity = $this->createMainSportActivity(true);
        $container = $this->createContainer(195, 1_700_000_000);

        $maxZoneHR->collect($activity, $container);

        self::assertTrue($maxZoneHR->isCollected());
        self::assertSame(195, $maxZoneHR->MaxHR);
        self::assertSame(1_700_000_000, $maxZoneHR->Time);
    }

    public function testCollectKeepsOnlyTheHighestHeartRateAcrossMultipleCalls(): void
    {
        $maxZoneHR = $this->createMaxZoneHRWithImportFlag(true);
        $activity = $this->createMainSportActivity(true);

        $maxZoneHR->collect($activity, $this->createContainer(180, 1_700_000_000));
        $maxZoneHR->collect($activity, $this->createContainer(200, 1_700_100_000));
        $maxZoneHR->collect($activity, $this->createContainer(190, 1_700_200_000));

        self::assertSame(200, $maxZoneHR->MaxHR);
        self::assertSame(1_700_100_000, $maxZoneHR->Time);
    }

    /**
     * The comment on MaxZoneHR::$Time says "timestamp of the first
     * highest HR". Since the production condition is a strict `>` (not
     * `>=`), a second activity with an EQUAL (not higher) heart rate must
     * NOT overwrite the timestamp of the first occurrence.
     */
    public function testCollectKeepsTimestampOfTheFirstOccurrenceWhenHeartRatesAreEqual(): void
    {
        $maxZoneHR = $this->createMaxZoneHRWithImportFlag(true);
        $activity = $this->createMainSportActivity(true);

        $maxZoneHR->collect($activity, $this->createContainer(200, 1_700_000_000));
        $maxZoneHR->collect($activity, $this->createContainer(200, 1_700_999_999));

        self::assertSame(200, $maxZoneHR->MaxHR);
        self::assertSame(1_700_000_000, $maxZoneHR->Time);
    }
}

/**
 * Minimal stand-in for the real activity data container. We only know its
 * shape from how it's used in the codebase
 * ($container->ActivityData->MaxHeartRateUser,
 * $container->Metadata->getTimestamp()); the actual
 * Runalyze\Parser\Activity\Common\Data\ActivityDataContainer class wasn't
 * provided, so this deliberately minimal stub only implements the two
 * accessors MaxZoneHR::collect() actually reads.
 */
final class StubActivityDataContainer
{
    public StubActivityDataForZoneHR $ActivityData;

    public StubMetadataForZoneHR $Metadata;

    public function __construct(
        ?int $maxHeartRateUser,
        int $timestamp
    ) {
        $this->ActivityData = new StubActivityDataForZoneHR($maxHeartRateUser);
        $this->Metadata = new StubMetadataForZoneHR($timestamp);
    }
}

final class StubActivityDataForZoneHR
{
    public function __construct(
        public ?int $MaxHeartRateUser
    ) {
    }
}

final readonly class StubMetadataForZoneHR
{
    public function __construct(
        private int $timestamp
    ) {
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }
}

final class ActivityBulkImportCommandAdditionalTest extends TestCase
{
    /**
     * $doctrine is declared `private readonly` in ActivityBulkImportCommand.
     * Reflection cannot overwrite an already-initialized readonly property
     * (only uninitialized ones, as used for MaxZoneHR's $import above), so
     * any test-specific ManagerRegistry must be passed in directly at
     * construction time via this optional parameter, rather than injected
     * afterwards.
     */
    private function createCommand(?ManagerRegistry $doctrine = null): ActivityBulkImportCommand
    {
        return new ActivityBulkImportCommand(
            $this->createStub(ParameterBagInterface::class),
            $doctrine ?? $this->createStub(ManagerRegistry::class),
            $this->createStub(TokenStorageInterface::class),
            $this->createStub(Filesystem::class),
            $this->createStub(FileImporter::class),
            $this->createStub(ActivityDataContainerFilter::class),
            $this->createStub(ActivityDataContainerToActivityContextConverter::class),
            $this->createStub(ActivityContextAdapterFactory::class),
            $this->createStub(ConfigurationManager::class)
        );
    }

    /**
     * Replaces ->with(User::class): a stub has no with(), so the check
     * is an assertion inside the callback.
     */
    private function createDoctrineReturning(UserRepository $userRepository): ManagerRegistry
    {
        $doctrine = $this->createStub(ManagerRegistry::class);
        $doctrine->method('getRepository')
            ->willReturnCallback(function (string $className) use ($userRepository): UserRepository {
                $this->assertSame(User::class, $className);

                return $userRepository;
            });

        return $doctrine;
    }

    private function invokeCheckStoreMaxZoneHR(
        ActivityBulkImportCommand $command,
        Account $account,
        BufferedOutput $output,
        MaxZoneHR $maxZoneHR
    ): void {
        $method = new \ReflectionMethod(ActivityBulkImportCommand::class, 'checkStoreMaxZoneHR');
        $method->invoke($command, $account, $output, $maxZoneHR);
    }

    private function createMaxZoneHRWithValues(int $maxHR, ?int $time): MaxZoneHR
    {
        $reflectionClass = new \ReflectionClass(MaxZoneHR::class);
        $instance = $reflectionClass->newInstanceWithoutConstructor();

        $importProperty = $reflectionClass->getProperty('import');
        $importProperty->setValue($instance, true);

        $instance->MaxHR = $maxHR;
        $instance->Time = $time;

        return $instance;
    }

    public function testCheckStoreMaxZoneHRDoesNothingWhenNoPreviousUserEntryExists(): void
    {
        // Stays a mock: it has an expectation (never)
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('getLatestEntryFor')
            ->willReturn(null);
        $userRepository->expects(self::never())->method('save');

        $command = $this->createCommand($this->createDoctrineReturning($userRepository));

        $account = $this->createStub(Account::class);
        $maxZoneHR = $this->createMaxZoneHRWithValues(190, 1_700_000_000);

        $this->invokeCheckStoreMaxZoneHR($command, $account, new BufferedOutput(), $maxZoneHR);
    }

    public function testCheckStoreMaxZoneHRDoesNothingWhenPulseIsUnchanged(): void
    {
        // Stays a mock: it has an expectation (never)
        $oldUser = $this->createMock(User::class);
        $oldUser->method('getPulseMax')
            ->willReturn(190);
        $oldUser->expects(self::never())->method('cloneObjectForForm');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('getLatestEntryFor')
            ->willReturn($oldUser);
        $userRepository->expects(self::never())->method('save');

        $command = $this->createCommand($this->createDoctrineReturning($userRepository));

        $account = $this->createStub(Account::class);
        $maxZoneHR = $this->createMaxZoneHRWithValues(190, 1_700_000_000);

        $this->invokeCheckStoreMaxZoneHR($command, $account, new BufferedOutput(), $maxZoneHR);
    }

    public function testCheckStoreMaxZoneHRClonesAndSavesNewUserWhenPulseDiffers(): void
    {
        $newUser = $this->createMock(User::class);
        $newUser->expects(self::once())->method('setTime')->with(1_700_000_000);
        $newUser->expects(self::once())->method('setPulseMax')->with(200);
        $newUser->expects(self::once())->method('setNotes')->with(
            'New max pulse is set while importing main-sports activity.'
        );

        // Only returns values, so a stub is enough
        $oldUser = $this->createStub(User::class);
        $oldUser->method('getPulseMax')
            ->willReturn(190);
        $oldUser->method('cloneObjectForForm')
            ->willReturn($newUser);

        $account = $this->createStub(Account::class);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('getLatestEntryFor')
            ->willReturn($oldUser);
        $userRepository->expects(self::once())->method('save')->with($newUser, $account);

        $command = $this->createCommand($this->createDoctrineReturning($userRepository));

        $maxZoneHR = $this->createMaxZoneHRWithValues(200, 1_700_000_000);

        $output = new BufferedOutput();
        $this->invokeCheckStoreMaxZoneHR($command, $account, $output, $maxZoneHR);

        self::assertStringContainsString('Max heart-rate of user is changed from 190 to 200', $output->fetch());
    }

    private function setMoveFolderOn(ActivityBulkImportCommand $command, ?string $moveFolder): void
    {
        $property = new \ReflectionProperty(ActivityBulkImportCommand::class, 'moveFolder');
        $property->setValue($command, $moveFolder);
    }

    private function invokeMoveFile(
        ActivityBulkImportCommand $command,
        string $path,
        string $importedFilename,
        BufferedOutput $output
    ): void {
        $method = new \ReflectionMethod(ActivityBulkImportCommand::class, 'moveFile');
        $method->invoke($command, $path, $importedFilename, $output);
    }

    public function testMoveFileDoesNothingWhenMoveFolderOptionWasNotSet(): void
    {
        $tempDirectory = sys_get_temp_dir() . '/bulk-import-movefile-test-' . bin2hex(random_bytes(8));
        mkdir($tempDirectory, 0777, true);

        try {
            $filePath = $tempDirectory . '/activity.fit';
            file_put_contents($filePath, 'dummy');

            $command = $this->createCommand();
            $this->setMoveFolderOn($command, null);

            $this->invokeMoveFile($command, $tempDirectory, 'activity.fit', new BufferedOutput());

            self::assertFileExists($filePath, 'File must remain in place when --move was not used.');
        } finally {
            new Filesystem()->remove($tempDirectory);
        }
    }

    public function testMoveFileMovesFileIntoConfiguredSubfolder(): void
    {
        $tempDirectory = sys_get_temp_dir() . '/bulk-import-movefile-test-' . bin2hex(random_bytes(8));
        mkdir($tempDirectory, 0777, true);
        mkdir($tempDirectory . '/imported', 0777, true);

        try {
            $filePath = $tempDirectory . '/activity.fit';
            file_put_contents($filePath, 'dummy content');

            $command = $this->createCommand();
            $this->setMoveFolderOn($command, 'imported');

            $this->invokeMoveFile($command, $tempDirectory, 'activity.fit', new BufferedOutput());

            self::assertFileDoesNotExist($filePath);
            self::assertFileExists($tempDirectory . '/imported/activity.fit');
            self::assertSame('dummy content', file_get_contents($tempDirectory . '/imported/activity.fit'));
        } finally {
            new Filesystem()->remove($tempDirectory);
        }
    }

    public function testMoveFileWritesErrorWhenTargetSubfolderDoesNotExist(): void
    {
        $tempDirectory = sys_get_temp_dir() . '/bulk-import-movefile-test-' . bin2hex(random_bytes(8));
        mkdir($tempDirectory, 0777, true);

        try {
            $filePath = $tempDirectory . '/activity.fit';
            file_put_contents($filePath, 'dummy content');

            $command = $this->createCommand();
            $this->setMoveFolderOn($command, 'imported');

            $output = new BufferedOutput();

            set_error_handler(static fn (): bool => true, E_WARNING);

            try {
                $this->invokeMoveFile($command, $tempDirectory, 'activity.fit', $output);
            } finally {
                restore_error_handler();
            }

            self::assertFileExists(
                $filePath,
                'The file must remain at its original location since the move failed.'
            );
            self::assertStringContainsString('Cant move to', $output->fetch());
        } finally {
            new Filesystem()->remove($tempDirectory);
        }
    }
}
