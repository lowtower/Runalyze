<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Twig;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\RunalyzeConfigurationList;
use Runalyze\Bundle\CoreBundle\Twig\DisplayableVO2max;

final class DisplayableVO2maxTest extends TestCase
{
    #[DataProvider('iconDirections')]
    public function testUsesShapeDifferenceForIconDirection(float $shape, string $expectedDirection): void
    {
        $configuration = $this->configuration(1.0, $shape);
        $value = new DisplayableVO2max(50.0, $configuration);

        $this->assertEqualsWithDelta(50.0, $value->getUncorrectedValue(), PHP_FLOAT_EPSILON);
        $this->assertSame('50.00', $value->getValue());
        $this->assertSame('vo2max-icon small ' . $expectedDirection, $value->getIconClass());
    }

    /**
     * @return iterable<string, array{float, string}>
     */
    public static function iconDirections(): iterable
    {
        yield 'large positive deviation' => [46.0, 'fa-arrow-up'];
        yield 'small positive deviation' => [48.0, 'fa-arrow-up  fa-rotate-45'];
        yield 'similar to current shape' => [50.0, 'fa-arrow-right'];
        yield 'small negative deviation' => [52.0, 'fa-arrow-right  fa-rotate-45'];
        yield 'large negative deviation' => [54.0, 'fa-arrow-down'];
    }

    public function testCorrectsValueBeforeComparingItWithCurrentShape(): void
    {
        $value = new DisplayableVO2max(50.0, $this->configuration(1.2, 56.0));

        $this->assertEqualsWithDelta(50.0, $value->getUncorrectedValue(), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(60.0, $value->getRawValue(), PHP_FLOAT_EPSILON);
        $this->assertSame('60.00', $value->getValue());
        $this->assertSame('vo2max-icon small fa-arrow-up', $value->getIconClass());
    }

    public function testMarksValuesExcludedFromShapeCalculationAsUnimportant(): void
    {
        $value = new DisplayableVO2max(50.0, $this->configuration(1.0, 50.0), false);

        $this->assertSame('vo2max-icon small fa-arrow-right unimportant', $value->getIconClass());
    }

    private function configuration(float $factor, float $shape): RunalyzeConfigurationList
    {
        $configuration = $this->createStub(RunalyzeConfigurationList::class);
        $configuration->method('getVO2maxCorrectionFactor')->willReturn($factor);
        $configuration->method('getCurrentVO2maxShape')->willReturn($shape);

        return $configuration;
    }
}
