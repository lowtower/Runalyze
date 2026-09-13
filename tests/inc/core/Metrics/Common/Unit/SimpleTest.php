<?php

declare(strict_types=1);

namespace Runalyze\Tests\Metrics\Common\Unit;

use Runalyze\Metrics\Common\Unit\Simple;

class SimpleTest extends \PHPUnit\Framework\TestCase
{
    public function testSomeEasyValues(): void
    {
        $unit = new Simple('foo');

        $this->assertEquals(0.79, $unit->fromBaseUnit(0.79));
        $this->assertEquals(42, $unit->toBaseUnit(42));
        $this->assertEquals('foo', $unit->getAppendix());
    }
}
