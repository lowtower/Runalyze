<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Twig;

use PHPUnit\Framework\MockObject\Stub;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Twig\ValueExtension;
use Runalyze\Metrics;

final class ValueExtensionTest extends \PHPUnit\Framework\TestCase
{
    protected ValueExtension $Extension;

    protected function setUp(): void
    {
        $this->Extension = $this->getExtensionFor();
    }

    /**
     * @param array<string, mixed> $configurationListData
     */
    protected function getExtensionFor(array $configurationListData = []): ValueExtension
    {
        /** @var ConfigurationManager&Stub $configManager */
        $configManager = $this->createStub(ConfigurationManager::class);
        $configManager
            ->method('getList')
            ->willReturn(new RunalyzeConfigurationList($configurationListData));

        return new ValueExtension($configManager);
    }

    public function testSimpleValue(): void
    {
        $value = $this->Extension->value(3.14159, 'pi', 2, ',');

        $this->assertSame('3,14', $value->getValue());
        $this->assertSame('pi', $value->getUnit());
    }

    public function testDistanceWithGivenUnit(): void
    {
        $this->assertSame('3.14&nbsp;km', $this->Extension->distance(3.14, new Metrics\Distance\Unit\Kilometer())->getWithUnit());
        $this->assertSame('1.00&nbsp;mi', $this->Extension->distance(1.609, new Metrics\Distance\Unit\Miles())->getWithUnit());
    }

    public function testElevationWithGivenUnit(): void
    {
        $this->assertSame('247&nbsp;m', $this->Extension->elevation(247, new Metrics\Distance\Unit\Meter())->getWithUnit());
    }

    public function testStrideLengthWithGivenUnit(): void
    {
        $this->assertSame('1.23&nbsp;m', $this->Extension->strideLength(123, new Metrics\Distance\Unit\Meter())->getWithUnit());
        $this->assertSame('157&nbsp;cm', $this->Extension->strideLength(157, new Metrics\Distance\Unit\Centimeter(), 0)->getWithUnit());
    }

    public function testEnergyWithGivenUnit(): void
    {
        $this->assertSame('512&nbsp;kcal', $this->Extension->energy(512, new Metrics\Energy\Unit\Kilocalories())->getWithUnit());
    }

    public function testHeartRateWithGivenUnit(): void
    {
        $this->assertSame('120&nbsp;bpm', $this->Extension->heartRate(120, new Metrics\HeartRate\Unit\BeatsPerMinute())->getWithUnit());
        $this->assertSame('84&nbsp;%', $this->Extension->heartRate(168, new Metrics\HeartRate\Unit\PercentMaximum(200))->getWithUnit());
        $this->assertSame('67&nbsp;%', $this->Extension->heartRate(140, new Metrics\HeartRate\Unit\PercentReserve(180, 60))->getWithUnit());
    }

    public function testHeartRateComparisonWithGivenUnit(): void
    {
        $this->assertSame('10&nbsp;bpm', $this->Extension->heartRateComparison(130, 120, new Metrics\HeartRate\Unit\BeatsPerMinute())->getWithUnit());
        $this->assertSame('-7&nbsp;bpm', $this->Extension->heartRateComparison(113, 120, new Metrics\HeartRate\Unit\BeatsPerMinute())->getWithUnit());
        $this->assertSame('2&nbsp;%', $this->Extension->heartRateComparison(172, 168, new Metrics\HeartRate\Unit\PercentMaximum(200))->getWithUnit());
        $this->assertSame('-15&nbsp;%', $this->Extension->heartRateComparison(122, 140, new Metrics\HeartRate\Unit\PercentReserve(180, 60))->getWithUnit());
    }

    public function testPaceWithGivenUnit(): void
    {
        $this->assertSame('5:30/km', $this->Extension->pace(330, new Metrics\Velocity\Unit\SecondsPerKilometer())->getWithUnit());
        $this->assertSame('10.9&nbsp;km/h', $this->Extension->pace(330, new Metrics\Velocity\Unit\KilometerPerHour())->getWithUnit());
    }

    public function testPaceComparisonWithGivenUnit(): void
    {
        $this->assertSame('-0:30/km', $this->Extension->paceComparison(300, 330, new Metrics\Velocity\Unit\SecondsPerKilometer())->getWithUnit());
        $this->assertSame('0.9&nbsp;km/h', $this->Extension->paceComparison(330, 360, new Metrics\Velocity\Unit\KilometerPerHour())->getWithUnit());
    }

    public function testTemperatureWithGivenUnit(): void
    {
        $this->assertSame('13&nbsp;°C', $this->Extension->temperature(13, new Metrics\Temperature\Unit\Celsius())->getWithUnit());
    }

    public function testWeightWithGivenUnit(): void
    {
        $this->assertSame('87.3&nbsp;kg', $this->Extension->weight(87.332, new Metrics\Weight\Unit\Kilogram())->getWithUnit());
    }
}
