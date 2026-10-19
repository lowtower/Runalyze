<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Common\Unit;

use Runalyze\Metrics\Common\Unit\None;

final class NoneTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeEasyValues(): void
    {
        $unit = new None();

        $this->assertEqualsWithDelta(1.23, $unit->fromBaseUnit(1.23), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(3.14, $unit->toBaseUnit(3.14), PHP_FLOAT_EPSILON);
        $this->assertSame('', $unit->getAppendix());
    }
}
