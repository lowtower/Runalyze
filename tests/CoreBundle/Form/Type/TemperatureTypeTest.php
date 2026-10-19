<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Form\Type;

use Runalyze\Bundle\CoreBundle\Form\Type\TemperatureType;
use Runalyze\Metrics\Temperature\Unit\Fahrenheit;

final class TemperatureTypeTest extends \PHPUnit\Framework\TestCase
{
    public function testReverseTransformWithFahrenheit(): void
    {
        $type = new TemperatureType(new Fahrenheit());

        $this->assertSame(-18, (int) $type->reverseTransform('0'));
        $this->assertEquals(8, $type->reverseTransform('46'));
        $this->assertEquals(9, $type->reverseTransform('48'));
        $this->assertEquals(10, $type->reverseTransform('50'));
        $this->assertEquals(13, $type->reverseTransform('56'));
    }
}
