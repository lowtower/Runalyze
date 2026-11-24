<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Doctrine;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('requiresKernel')]
#[Group('requiresDoctrine')]
final class LegacyShapePlotTimezoneTest extends KernelTestCase
{
    #[DataProvider('calendarBoundaries')]
    public function testLegacyFromUnixtimeUsesMysqlSessionTimezoneInsteadOfAccountTimezone(
        string $utcTime,
        string $utcDay,
        string $berlinDay,
        string $fixedOffsetDay
    ): void {
        self::bootKernel([
            'environment' => 'test_empty',
        ]);

        /** @var ManagerRegistry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');
        $connection = $doctrine->getConnection();
        $originalTimezone = (string) $connection->fetchOne('SELECT @@session.time_zone');
        $timestamp = new DateTimeImmutable($utcTime, new DateTimeZone('UTC'))->getTimestamp();
        $accountDay = new DateTimeImmutable('@' . $timestamp)
            ->setTimezone(new DateTimeZone('Europe/Berlin'))
            ->format('Y-m-d');

        try {
            $connection->executeStatement("SET time_zone = '+00:00'");

            $legacyUtcDay = $connection->fetchOne(
                'SELECT DATE(FROM_UNIXTIME(:timestamp))',
                [
                    'timestamp' => $timestamp,
                ]
            );

            $this->assertSame($utcDay, $legacyUtcDay);
            $this->assertSame($berlinDay, $accountDay);
            $this->assertNotSame($accountDay, $legacyUtcDay);

            $connection->executeStatement("SET time_zone = '+01:00'");

            $legacyBerlinWinterOffsetDay = $connection->fetchOne(
                'SELECT DATE(FROM_UNIXTIME(:timestamp))',
                [
                    'timestamp' => $timestamp,
                ]
            );

            $this->assertSame($fixedOffsetDay, $legacyBerlinWinterOffsetDay);
        } finally {
            $connection->executeStatement(
                'SET time_zone = :original_timezone',
                [
                    'original_timezone' => $originalTimezone,
                ]
            );
        }
    }

    /** @return iterable<string, array{string, string, string, string}> */
    public static function calendarBoundaries(): iterable
    {
        yield 'spring transition' => ['2026-03-28 23:30:00', '2026-03-28', '2026-03-29', '2026-03-29'];
        yield 'historical summer' => ['2016-07-01 22:30:00', '2016-07-01', '2016-07-02', '2016-07-01'];
    }
}
