<?php

namespace Runalyze\Tests\View;

use Runalyze\View\RpeColor;

class RpeColorTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyObject()
    {
        $object = new RpeColor();

        $this->assertNull($object->value());
        $this->assertEquals('transparent', $object->borderColor());
    }

    public function testSetting()
    {
        $viaConstructor = new RpeColor(7);
        $this->assertEquals(7, $viaConstructor->value());

        $viaSet = new RpeColor();
        $this->assertEquals(9, $viaSet->setValue(9)->value());
    }

    public function testBackgroundColor()
    {
        $rpe = new RpeColor();

        $this->assertEquals('transparent', $rpe->setValue(0)->borderColor());

        $this->assertEquals('#225ea8', $rpe->setValue(1)->borderColor());
        $this->assertEquals('#41b6c4', $rpe->setValue(2)->borderColor());
        $this->assertEquals('#41b6c4', $rpe->setValue(3)->borderColor());
        $this->assertEquals('#a1dab4', $rpe->setValue(4)->borderColor());
        $this->assertEquals('#a1dab4', $rpe->setValue(5)->borderColor());
        $this->assertEquals('#fecc5c', $rpe->setValue(6)->borderColor());
        $this->assertEquals('#fecc5c', $rpe->setValue(7)->borderColor());
        $this->assertEquals('#fd8d3c', $rpe->setValue(8)->borderColor());
        $this->assertEquals('#fd8d3c', $rpe->setValue(9)->borderColor());
        $this->assertEquals('#e31a1c', $rpe->setValue(10)->borderColor());

        $this->assertEquals('transparent', $rpe->setValue(11)->borderColor());
    }

    public function testInvalidValues()
    {
        $rpe = new RpeColor();

        $this->assertNull($rpe->setValue(null)->value());
        $this->assertNull($rpe->setValue(0)->value());
        $this->assertNull($rpe->setValue(11)->value());
        $this->assertNull($rpe->setValue('invalid')->value());
    }
}
