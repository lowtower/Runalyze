<?php

declare(strict_types=1);

namespace Runalyze\Calculation\Trimp;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Athlete;
use Runalyze\Profile\Athlete\Gender;

/**
 * @see http://fellrnr.com/wiki/TRIMP#Worked_Example
 */
#[\PHPUnit\Framework\Attributes\Group('default')]
class InverseCalculatorTest extends TestCase
{
    public function testReferenceExample(): void
    {
        // Fellrnr's example is with HRrest (max = 200, rest = 40, avg = 130)
        // Since we're using HRmax, we have to adapt the data
        $Trimp = new InverseCalculator(new Athlete(
            Gender::MALE,
            160,
            0
        ), 90, 32);

        $this->assertEqualsWithDelta(30 * 60, $Trimp->value(), 0.01 * 30 * 60);
    }

    public function testInverseValueRecoversShortDurationFromExactBugReport(): void
    {
        $Athlete = new Athlete(null, 205, 45);
        $bpm = 178;
        $seconds = 75;

        $Trimp = new Calculator($Athlete, [
            $bpm => $seconds,
        ]);
        $Inverse = new InverseCalculator($Athlete, $bpm, $Trimp->value());

        $this->assertSame($seconds, $Inverse->value());
    }

    #[DataProvider('provideRoundTripCases')]
    public function testValueRecoversOriginalDurationFromCalculatedTrimp(Athlete $Athlete, int $heartRate, int $seconds): void
    {
        $Trimp = new Calculator($Athlete, [
            $heartRate => $seconds,
        ]);

        $this->assertGreaterThan(0, $Trimp->value(), 'Test case must produce a positive trimp value.');

        $Inverse = new InverseCalculator($Athlete, $heartRate, $Trimp->value());

        $this->assertEqualsWithDelta($seconds, $Inverse->value(), 1);
    }

    public static function provideRoundTripCases(): array
    {
        return [
            'male, mid heart rate, short interval' => [new Athlete(Gender::MALE, 190, 50), 150, 90],
            'male, heart rate close to max, short interval' => [new Athlete(Gender::MALE, 200, 60), 199, 45],
            'female, mid heart rate, long session' => [new Athlete(Gender::FEMALE, 195, 55), 160, 1200],
            'female, low heart rate above rest' => [new Athlete(Gender::FEMALE, 195, 55), 100, 600],
            'unknown gender, mid heart rate' => [new Athlete(null, 205, 45), 178, 75],
            'unknown gender, athlete without known HR bounds uses defaults' => [new Athlete(), 130, 300],
        ];
    }

    #[DataProvider('provideInvalidConstructorArguments')]
    public function testConstructorThrowsExceptionForNonPositiveHeartRateOrTrimp(int $heartRate, float $trimp): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Heart rate and trimp have to be greater than zero.');

        new InverseCalculator(new Athlete(null, 200, 60), $heartRate, $trimp);
    }

    /**
     * @return array<string, array<int, int|float>>
     */
    public static function provideInvalidConstructorArguments(): array
    {
        return [
            'heart rate is zero' => [0, 30.0],
            'heart rate is negative' => [-10, 30.0],
            'trimp is zero' => [150, 0.0],
            'trimp is negative' => [150, -5.0],
            'both heart rate and trimp are zero' => [0, 0.0],
            'both heart rate and trimp are negative' => [-10, -5.0],
        ];
    }

    #[DataProvider('provideHeartRatesNotExceedingRestingHeartRate')]
    public function testConstructorThrowsExceptionWhenHeartRateDoesNotExceedRestingHeartRate(Athlete $Athlete, int $heartRate): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Heart rate must be greater than the athlete's resting heart rate.");

        new InverseCalculator($Athlete, $heartRate, 10.0);
    }

    public static function provideHeartRatesNotExceedingRestingHeartRate(): array
    {
        return [
            'heart rate equals known resting heart rate' => [new Athlete(null, 200, 60), 60],
            'heart rate is below known resting heart rate' => [new Athlete(null, 200, 60), 50],
            'heart rate equals default resting heart rate' => [new Athlete(null, 200), Calculator::DEFAULT_HR_REST],
            'heart rate is below default resting heart rate' => [new Athlete(null, 200), Calculator::DEFAULT_HR_REST - 5],
        ];
    }
}
