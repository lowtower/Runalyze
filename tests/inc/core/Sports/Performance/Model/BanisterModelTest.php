<?php

declare(strict_types=1);

namespace Runalyze\Tests\Sports\Performance\Model;

use Runalyze\Sports\Performance\Model\BanisterModel;

class BanisterModelTest extends \PHPUnit\Framework\TestCase
{
    public function testSingleWorkout(): void
    {
        $Model = new BanisterModel([
            0 => 1000,
        ], 42, 7, 1, 2);
        $Model->calculate();

        $this->assertSame(1000, $Model->fitnessAt(0));
        $this->assertSame(1000, $Model->fatigueAt(0));
        $this->assertSame(-1000, $Model->performanceAt(0));
    }

    public function testSimpleExample(): void
    {
        $Model = new BanisterModel([
            0 => 150,
            1 => 50,
            2 => 40,
            5 => 60,
            6 => 30,
            7 => 100,
        ], 42, 7, 1, 2);
        $Model->calculate();

        $this->assertSame(196, $Model->fitnessAt(1));
        $this->assertSame(180, $Model->fatigueAt(1));
        $this->assertSame(-164, $Model->performanceAt(1));

        $this->assertSame(232, $Model->fitnessAt(2));
        $this->assertSame(226, $Model->fitnessAt(3));
        $this->assertSame(221, $Model->fitnessAt(4));
        $this->assertSame(276, $Model->fitnessAt(5));
        $this->assertSame(299, $Model->fitnessAt(6));
        $this->assertSame(392, $Model->fitnessAt(7));

        $this->assertSame(196, $Model->fatigueAt(2));
        $this->assertSame(170, $Model->fatigueAt(3));
        $this->assertSame(147, $Model->fatigueAt(4));
        $this->assertSame(188, $Model->fatigueAt(5));
        $this->assertSame(193, $Model->fatigueAt(6));
        $this->assertSame(267, $Model->fatigueAt(7));
    }

    public function testNoSteadyState(): void
    {
        $Model = new BanisterModel(array_fill(0, 51, 100), 42, 7, 1, 2);
        $Model->calculate();

        $this->assertSame(979, $Model->fitnessAt(10));
        $this->assertSame(1672, $Model->fitnessAt(20));
        $this->assertSame(2219, $Model->fitnessAt(30));
        $this->assertSame(2649, $Model->fitnessAt(40));
        $this->assertSame(2988, $Model->fitnessAt(50));

        $this->assertSame(595, $Model->fatigueAt(10));
        $this->assertSame(714, $Model->fatigueAt(20));
        $this->assertSame(742, $Model->fatigueAt(30));
        $this->assertSame(749, $Model->fatigueAt(40));
        $this->assertSame(751, $Model->fatigueAt(50));
    }

    public function testFutureFitness(): void
    {
        $Model = new BanisterModel([
            0 => 1000,
        ], 42, 7, 1, 2);
        $Model->setRange(0, 100);
        $Model->calculate();

        $this->assertSame(976, $Model->fitnessAt(1));
        $this->assertSame(788, $Model->fitnessAt(10));
        $this->assertSame(551, $Model->fitnessAt(25));
        $this->assertSame(304, $Model->fitnessAt(50));
        $this->assertSame(168, $Model->fitnessAt(75));
        $this->assertSame(92, $Model->fitnessAt(100));
    }

    public function testFutureFatigue(): void
    {
        $Model = new BanisterModel([
            0 => 1000,
        ], 42, 7, 1, 2);
        $Model->setRange(0, 30);
        $Model->calculate();

        $this->assertSame(867, $Model->fatigueAt(1));
        $this->assertSame(490, $Model->fatigueAt(5));
        $this->assertSame(240, $Model->fatigueAt(10));
        $this->assertSame(117, $Model->fatigueAt(15));
        $this->assertSame(57, $Model->fatigueAt(20));
        $this->assertSame(28, $Model->fatigueAt(25));
    }
}
