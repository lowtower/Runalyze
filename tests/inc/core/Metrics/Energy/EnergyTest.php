<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Energy;

use Runalyze\Metrics\Energy\Energy;

final class EnergyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Energy
     */
    protected $Energy;

    protected function setUp(): void
    {
        $this->Energy = new Energy();
    }

    public function testSettingMetabolicEquivalent(): void
    {
        $this->assertEqualsWithDelta(40.0, $this->Energy->setByMetabolicEquivalent(5.0, 80.0, 0.1)->getValue(), PHP_FLOAT_EPSILON);
    }
}
