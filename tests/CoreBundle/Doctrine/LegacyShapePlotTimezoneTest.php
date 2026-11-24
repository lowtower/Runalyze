<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Doctrine;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('requiresKernel')]
#[Group('requiresDoctrine')]
final class LegacyShapePlotTimezoneTest extends KernelTestCase
{
    public function testLegacyFromUnixtimeUsesMysqlSessionTimezoneInsteadOfAccountTimezone(): void
    {
        self::bootKernel([
            'environment' => 'test_empty',
        ]);

        /** @var ManagerRegistry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');
        $connection = $doctrine->getConnection();
        $originalTimezone = (string) $connection->fetchOne('SELECT @@session.time_zone');
        $timestamp = new DateTimeImmutable('2026-03-28 23:30:00', new DateTimeZone('UTC'))->getTimestamp();
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

            $this->assertSame('2026-03-28', $legacyUtcDay);
            $this->assertSame('2026-03-29', $accountDay);
            $this->assertNotSame($accountDay, $legacyUtcDay);

            $connection->executeStatement("SET time_zone = '+01:00'");

            $legacyBerlinWinterOffsetDay = $connection->fetchOne(
                'SELECT DATE(FROM_UNIXTIME(:timestamp))',
                [
                    'timestamp' => $timestamp,
                ]
            );

            $this->assertSame($accountDay, $legacyBerlinWinterOffsetDay);
        } finally {
            $connection->executeStatement(
                'SET time_zone = :original_timezone',
                [
                    'original_timezone' => $originalTimezone,
                ]
            );
        }
    }
}
