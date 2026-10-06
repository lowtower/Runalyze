<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Entity;

use DateTime;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use Runalyze\Bundle\CoreBundle\Entity\Notification;

class NotificationTest extends \PHPUnit\Framework\TestCase
{
    protected Notification $Notification;

    protected function setUp(): void
    {
        $this->Notification = new Notification();
    }

    public function testSetLifetime(): void
    {
        $timezone = new DateTimeZone('Europe/Berlin');
        $createdAt = (new DateTime('2026-06-15 10:00:00', $timezone))->getTimestamp();

        $this->Notification->setCreatedAt($createdAt);
        $this->Notification->setLifetime(2);

        $created = (new DateTime('@' . $createdAt))->setTimezone($timezone);
        $expiration = (new DateTime('@' . $this->Notification->getExpirationAt()))->setTimezone($timezone);

        $this->assertEquals('+2 days', $created->diff($expiration)->format('%R%a days'));
        // Uhrzeit muss trotz evtl. Zeitumstellung erhalten bleiben
        $this->assertSame($created->format('H:i:s'), $expiration->format('H:i:s'));
    }

    #[DataProvider('dstTransitionProvider')]
    public function testSetLifetimeAcrossDstTransition(string $createdAtString): void
    {
        $timezone = new DateTimeZone('Europe/Berlin');
        $createdAt = (new DateTime($createdAtString, $timezone))->getTimestamp();

        $this->Notification->setCreatedAt($createdAt);
        $this->Notification->setLifetime(2);

        $created = (new DateTime('@' . $createdAt))->setTimezone($timezone);
        $expiration = (new DateTime('@' . $this->Notification->getExpirationAt()))->setTimezone($timezone);

        $this->assertEquals('+2 days', $created->diff($expiration)->format('%R%a days'));

        $this->assertSame($created->format('H:i:s'), $expiration->format('H:i:s'));
    }

    /**
     * @return array<string, string[]>
     */
    public static function dstTransitionProvider(): array
    {
        return [
            'Start summertime (28.-30.03.2026)' => ['2026-03-28 10:00:00'],
            'End summertime (23.-25.10.2026)' => ['2026-10-23 10:00:00'],
            'Without time change' => ['2026-06-15 10:00:00'],
        ];
    }

    public function testSetExpirationAtWithZeroIsNotTreatedAsNoExpiration(): void
    {
        $this->Notification->setExpirationAt(0);

        $this->assertSame(0, $this->Notification->getExpirationAt());
    }

    public function testSetExpirationAtWithNullClearsTheExpiration(): void
    {
        $this->Notification->setExpirationAt(100);
        $this->Notification->setExpirationAt();

        $this->assertNull($this->Notification->getExpirationAt());
    }

    public function testSetExpirationAtWithPositiveTimestampIsStoredUnchanged(): void
    {
        $this->Notification->setExpirationAt(1700000000);

        $this->assertSame(1700000000, $this->Notification->getExpirationAt());
    }
}
