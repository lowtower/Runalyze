<?php

declare(strict_types=1);

namespace Runalyze\Parameter\Application;

class WeightUnitTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Runalyze\Parameter\Application\WeightUnit
     */
    protected $object;

    protected function setUp(): void
    {
        $this->object = new WeightUnit();
    }

    public function testKilograms(): void
    {
        $this->object->set(WeightUnit::KG);

        $this->assertTrue($this->object->isKG());
        $this->assertFalse($this->object->isPounds());
        $this->assertFalse($this->object->isStones());
    }

    public function testPounds(): void
    {
        $this->object->set(WeightUnit::POUNDS);

        $this->assertFalse($this->object->isKG());
        $this->assertTrue($this->object->isPounds());
        $this->assertFalse($this->object->isStones());
    }

    public function testStones(): void
    {
        $this->object->set(WeightUnit::STONES);

        $this->assertFalse($this->object->isKG());
        $this->assertFalse($this->object->isPounds());
        $this->assertTrue($this->object->isStones());
    }
}
