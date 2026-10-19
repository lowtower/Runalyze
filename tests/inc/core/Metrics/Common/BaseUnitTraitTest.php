<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Common;

use Runalyze\Metrics\Common\BaseUnitTrait;

final class BaseUnitTraitTest extends \PHPUnit\Framework\TestCase
{
    public function testThatConversionWorksAsExpected(): void
    {
        $mock = new class() {
            use BaseUnitTrait;
        };

        $this->assertEqualsWithDelta(3.14, $mock->fromBaseUnit(3.14), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(42.195, $mock->toBaseUnit(42.195), PHP_FLOAT_EPSILON);
    }
}
