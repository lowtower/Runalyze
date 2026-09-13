<?php

namespace Runalyze\Data\Weather;

class WindDegreeTest extends \PHPUnit\Framework\TestCase
{
    public function testNonNumericValue(): void
    {
        $this->expectException(\TypeError::class);
        new WindDegree('foobar');
    }

    public function testNull(): void
    {
        $Degrees = new WindDegree();

        $this->assertEquals(null, $Degrees->value());
        $this->assertEquals('', $Degrees->string());
        $this->assertTrue($Degrees->isUnknown());
    }

    public function testValue(): void
    {
        $Degrees = new WindDegree(120);

        $this->assertEquals(120, $Degrees->value());
        $this->assertEquals(180, $Degrees->set(180)->value());
        $this->assertEquals('180', $Degrees->string(false));
    }
}
