<?php

namespace Tests\Runalyze\Bundle\CoreBundle\Command;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Command\CreateNotificationCommand;
use Runalyze\Bundle\CoreBundle\Entity\Notification;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class CreateNotificationCommandTest extends TestCase
{
    private ParameterBagInterface&MockObject $parameterBag;

    private ManagerRegistry&MockObject $doctrine;

    private \Doctrine\DBAL\Connection $connection;

    private TestableCreateNotificationCommand $command;

    protected function setUp(): void
    {
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->parameterBag
            ->method('get')
            ->with('app.database_prefix')
            ->willReturn('runalyze_');

        $configuration = new Configuration();
        $configuration->setSchemaManagerFactory(new DefaultSchemaManagerFactory());

        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ], $configuration);

        $this->createSchema();

        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->doctrine
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->command = new TestableCreateNotificationCommand($this->parameterBag, $this->doctrine);
    }

    private function createSchema(): void
    {
        $this->connection->executeStatement(
            'CREATE TABLE `runalyze_account` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `language` TEXT,
                `lastaction` INTEGER,
                `registerdate` INTEGER
            )'
        );

        $this->connection->executeStatement(
            'CREATE TABLE `runalyze_notification` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `messageType` INTEGER,
                `createdAt` INTEGER,
                `expirationAt` INTEGER,
                `data` TEXT,
                `account_id` INTEGER
            )'
        );
    }

    private function insertAccount(string $language, int $lastAction, int $registerDate): int
    {
        $this->connection->executeStatement(
            'INSERT INTO `runalyze_account` (`language`, `lastaction`, `registerdate`) VALUES (?, ?, ?)',
            [$language, $lastAction, $registerDate]
        );

        return (int) $this->connection->lastInsertId();
    }

    private function createNotification(): Notification&MockObject
    {
        $notification = $this->createMock(Notification::class);
        $notification->method('getMessageType')
            ->willReturn(1);
        $notification->method('getCreatedAt')
            ->willReturn(1_700_000_000);
        $notification->method('getExpirationAt')
            ->willReturn(1_700_100_000);
        $notification->method('getData')
            ->willReturn('{"foo":"bar"}');

        return $notification;
    }

    public function testInsertsOneNotificationPerMatchingAccountWithNoFilters(): void
    {
        $this->insertAccount('de', 1_650_000_000, 1_600_000_000);
        $this->insertAccount('en', 1_650_000_000, 1_600_000_000);
        $this->insertAccount('fr', 1_650_000_000, 1_600_000_000);

        $notification = $this->createNotification();

        $affectedRows = $this->command->insertNotificationsWithSubqueryPublic(
            $notification,
            [],
            [],
            null,
            null,
            null,
            null
        );

        self::assertSame(3, $affectedRows);

        $insertedRows = $this->connection->fetchAllAssociative('SELECT * FROM `runalyze_notification`');
        self::assertCount(3, $insertedRows);

        foreach ($insertedRows as $row) {
            self::assertSame(1, (int) $row['messageType']);
            self::assertSame(1_700_000_000, (int) $row['createdAt']);
            self::assertSame(1_700_100_000, (int) $row['expirationAt']);
            self::assertSame('{"foo":"bar"}', $row['data']);
        }

        $notifiedAccountIds = array_map(static fn (array $row): int => (int) $row['account_id'], $insertedRows);
        sort($notifiedAccountIds);
        self::assertSame([1, 2, 3], $notifiedAccountIds);
    }

    public function testInsertsOnlyForAccountsMatchingLanguageFilter(): void
    {
        $this->insertAccount('de', 1_650_000_000, 1_600_000_000);
        $germanAccountId = (int) $this->connection->lastInsertId();
        $this->insertAccount('en', 1_650_000_000, 1_600_000_000);

        $affectedRows = $this->command->insertNotificationsWithSubqueryPublic(
            $this->createNotification(),
            ['de'],
            [],
            null,
            null,
            null,
            null
        );

        self::assertSame(1, $affectedRows);

        $insertedRows = $this->connection->fetchAllAssociative('SELECT * FROM `runalyze_notification`');
        self::assertCount(1, $insertedRows);
        self::assertSame($germanAccountId, (int) $insertedRows[0]['account_id']);
    }

    public function testExcludeLangTakesPrecedenceOverLang(): void
    {
        $this->insertAccount('de', 1_650_000_000, 1_600_000_000);
        $this->insertAccount('en', 1_650_000_000, 1_600_000_000);
        $englishAccountId = (int) $this->connection->lastInsertId();

        $affectedRows = $this->command->insertNotificationsWithSubqueryPublic(
            $this->createNotification(),
            ['de'],
            ['de'],
            null,
            null,
            null,
            null
        );

        self::assertSame(1, $affectedRows);

        $insertedRows = $this->connection->fetchAllAssociative('SELECT * FROM `runalyze_notification`');
        self::assertCount(1, $insertedRows);
        self::assertSame($englishAccountId, (int) $insertedRows[0]['account_id']);
    }

    public function testFiltersByLastActionAndRegistrationDateRange(): void
    {
        // Should match: within both ranges.
        $matchingId = $this->insertAccount('de', 1_650_000_000, 1_600_000_000);
        // Should NOT match: lastaction too old.
        $this->insertAccount('de', 1_000_000_000, 1_600_000_000);
        // Should NOT match: registerdate too recent.
        $this->insertAccount('de', 1_650_000_000, 1_690_000_000);

        $affectedRows = $this->command->insertNotificationsWithSubqueryPublic(
            $this->createNotification(),
            [],
            [],
            1_660_000_000, // lastActionBefore
            1_640_000_000, // lastActionAfter
            1_650_000_000, // registrationBefore
            1_500_000_000  // registrationAfter
        );

        self::assertSame(1, $affectedRows);

        $insertedRows = $this->connection->fetchAllAssociative('SELECT * FROM `runalyze_notification`');
        self::assertCount(1, $insertedRows);
        self::assertSame($matchingId, (int) $insertedRows[0]['account_id']);
    }

    public function testReturnsZeroWhenNoAccountsMatch(): void
    {
        $this->insertAccount('de', 1_650_000_000, 1_600_000_000);

        $affectedRows = $this->command->insertNotificationsWithSubqueryPublic(
            $this->createNotification(),
            ['fr'], // no French accounts exist
            [],
            null,
            null,
            null,
            null
        );

        self::assertSame(0, $affectedRows);
        self::assertCount(0, $this->connection->fetchAllAssociative('SELECT * FROM `runalyze_notification`'));
    }
}

final class TestableCreateNotificationCommand extends CreateNotificationCommand
{
    public function insertNotificationsWithSubqueryPublic(
        Notification $notification,
        array $lang,
        array $excludeLang,
        $lastActionBefore,
        $lastActionAfter,
        $registrationBefore,
        $registrationAfter
    ): int {
        return $this->insertNotificationsWithSubquery(
            $notification,
            $lang,
            $excludeLang,
            $lastActionBefore,
            $lastActionAfter,
            $registrationBefore,
            $registrationAfter
        );
    }
}
