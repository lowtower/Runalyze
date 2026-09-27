<?php

declare(strict_types=1);

use Runalyze\Tests\DatabaseTestCase;

#[\PHPUnit\Framework\Attributes\Group('dependsOn')]
#[\PHPUnit\Framework\Attributes\Group('dependsOnOldDatabase')]
class HelperDatabaseTest extends DatabaseTestCase
{
    /**
     * @throws ReflectionException
     */
    private function invokePrivateCalculationMethod(string $method): int
    {
        $reflectionMethod = new ReflectionMethod(Helper::class, $method);

        return $reflectionMethod->invoke(null);
    }

    public function testCalculateStartTimeReturnsZeroWhenAccountHasNoTrainings(): void
    {
        $this->assertSame(0, $this->invokePrivateCalculationMethod('calculateStartTime'));
    }

    public function testCalculateStartTimeReturnsEarliestTrainingTimeForAccount(): void
    {
        $accountId = SessionAccountHandler::getId();

        $this->DB->exec('INSERT INTO `' . PREFIX . 'training` (`sportid`, `time`, `s`, `accountid`) VALUES (1, 2000000000, 3600, ' . $accountId . ')');
        $this->DB->exec('INSERT INTO `' . PREFIX . 'training` (`sportid`, `time`, `s`, `accountid`) VALUES (1, 1000000000, 3600, ' . $accountId . ')');

        $this->assertSame(1000000000, $this->invokePrivateCalculationMethod('calculateStartTime'));
    }

    public function testCalculateStartTimeIgnoresATrainingWithTimeZeroWhenALaterTrainingExists(): void
    {
        $accountId = SessionAccountHandler::getId();

        $this->DB->exec('INSERT INTO `' . PREFIX . 'training` (`sportid`, `time`, `s`, `accountid`) VALUES (1, 0, 3600, ' . $accountId . ')');
        $this->DB->exec('INSERT INTO `' . PREFIX . 'training` (`sportid`, `time`, `s`, `accountid`) VALUES (1, 1500000000, 3600, ' . $accountId . ')');

        $this->assertSame(1500000000, $this->invokePrivateCalculationMethod('calculateStartTime'));
    }

    public function testCalculateHFmaxReturnsTwoHundredWhenAccountHasNoUserData(): void
    {
        $this->assertSame(200, $this->invokePrivateCalculationMethod('calculateHFmax'));
    }

    public function testCalculateHFmaxReturnsPulseMaxOfMostRecentUserEntryWhenNotOnASharedPage(): void
    {
        $accountId = SessionAccountHandler::getId();

        $this->DB->exec('INSERT INTO `' . PREFIX . 'user` (`time`, `pulse_max`, `accountid`) VALUES (1000000000, 180, ' . $accountId . ')');
        $this->DB->exec('INSERT INTO `' . PREFIX . 'user` (`time`, `pulse_max`, `accountid`) VALUES (2000000000, 190, ' . $accountId . ')');

        $this->assertSame(190, $this->invokePrivateCalculationMethod('calculateHFmax'));
    }

    public function testCalculateHFrestReturnsSixtyWhenAccountHasNoUserData(): void
    {
        $this->assertSame(60, $this->invokePrivateCalculationMethod('calculateHFrest'));
    }

    public function testCalculateHFrestReturnsPulseRestOfMostRecentUserEntryWhenNotOnASharedPage(): void
    {
        $accountId = SessionAccountHandler::getId();

        $this->DB->exec('INSERT INTO `' . PREFIX . 'user` (`time`, `pulse_rest`, `accountid`) VALUES (1000000000, 55, ' . $accountId . ')');
        $this->DB->exec('INSERT INTO `' . PREFIX . 'user` (`time`, `pulse_rest`, `accountid`) VALUES (2000000000, 50, ' . $accountId . ')');

        $this->assertSame(50, $this->invokePrivateCalculationMethod('calculateHFrest'));
    }
}
