<?php

declare(strict_types=1);

namespace Runalyze\Tests\Calculation\Math;

use PHPUnit\Framework\TestCase;
use Runalyze\Calculation\Math\SubSegmentMaximization;

class SubSegmentMaximizationTest extends TestCase
{
    public function testConstructorThrowsWhenDataIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Input data must not be empty.');

        new SubSegmentMaximization([], [], [3]);
    }

    public function testConstructorThrowsWhenSegmentLengthsIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Input data must not be empty.');

        new SubSegmentMaximization([1, 2, 3], [1, 1, 1], []);
    }

    public function testConstructorThrowsWhenDataAndIndexDataSizesDiffer(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Input data and index data must be of same size.');

        new SubSegmentMaximization([1, 2, 3], [1, 1], [3]);
    }

    public function testConstructorDoesNotThrowWhenSegmentLengthsCountDiffersFromDataCount(): void
    {
        $instance = new SubSegmentMaximization([1, 2, 3], [1, 1, 1], [1, 2, 3, 4, 5]);

        $this->assertInstanceOf(SubSegmentMaximization::class, $instance);
    }

    public function testMaximizeFindsExpectedWindowForEquidistantData(): void
    {
        $data = [1, 5, 2, 8, 1, 1, 9, 2];
        $index = array_fill(0, 8, 1);

        $instance = new SubSegmentMaximization($data, $index, [3]);
        $instance->maximize();

        $this->assertTrue($instance->hasMaximumForLengthIndex(0));
        $this->assertSame(15.0, $instance->getMaximumForLengthIndex(0));
        $this->assertSame([1, 3], $instance->getIndizesOfMaximumForLengthIndex(0));
    }

    public function testMaximizeReportsNoMaximumWhenTargetLengthIsNeverReached(): void
    {
        $instance = new SubSegmentMaximization([1, 1], [1, 1], [10]);
        $instance->maximize();

        $this->assertFalse($instance->hasMaximumForLengthIndex(0));
        $this->assertSame([false, false], $instance->getIndizesOfMaximumForLengthIndex(0));
        $this->assertSame([], $instance->getAvailableSegmentLengths());
    }

    public function testMaximizeReturnsExactSumWhenWindowLengthMatchesTargetExactly(): void
    {
        $instance = new SubSegmentMaximization([10, 10, 10], [1, 1, 1], [3]);
        $instance->maximize();

        $this->assertSame(30.0, $instance->getMaximumForLengthIndex(0));
    }

    public function testMaximizeInterpolatesValueDownForOverlengthWindowsByDefault(): void
    {
        $instance = new SubSegmentMaximization([100], [20], [10]);
        $instance->maximize();

        $this->assertSame(50.0, $instance->getMaximumForLengthIndex(0));
    }

    public function testMaximizeReturnsRawSumForOverlengthWindowWhenInterpolationIsDisabled(): void
    {
        $instance = new SubSegmentMaximization([100], [20], [10]);
        $instance->disableInterpolationForOverlength();
        $instance->maximize();

        $this->assertSame(100.0, $instance->getMaximumForLengthIndex(0));
    }

    public function testDisableInterpolationForOverlengthCalledWithTrueActuallyReEnablesInterpolation(): void
    {
        $instance = new SubSegmentMaximization([100], [20], [10]);
        $instance->disableInterpolationForOverlength(true);
        $instance->maximize();

        $this->assertSame(50.0, $instance->getMaximumForLengthIndex(0));
    }

    public function testMinimizeFindsExpectedMinimalWindow(): void
    {
        $data = [5, 1, 8, 2, 1, 9];
        $index = array_fill(0, 6, 1);

        $instance = new SubSegmentMaximization($data, $index, [3]);
        $instance->minimize();

        $this->assertTrue($instance->hasMaximumForLengthIndex(0));
        $this->assertSame(11.0, $instance->getMaximumForLengthIndex(0));
        $this->assertSame([1, 3], $instance->getIndizesOfMaximumForLengthIndex(0));
    }

    public function testMinimizeReportsNoMaximumWhenTargetLengthIsNeverReached(): void
    {
        $instance = new SubSegmentMaximization([1, 1], [1, 1], [10]);
        $instance->minimize();

        $this->assertFalse($instance->hasMaximumForLengthIndex(0));
    }

    public function testMaximizeTracksMultipleSegmentLengthsIndependently(): void
    {
        $data = [1, 5, 2, 8, 1, 1, 9, 2, 3, 4];
        $index = array_fill(0, 10, 1);

        $instance = new SubSegmentMaximization($data, $index, [3, 5]);
        $instance->maximize();

        $this->assertSame(15.0, $instance->getMaximumForLengthIndex(0));
        $this->assertSame(21.0, $instance->getMaximumForLengthIndex(1));
        $this->assertSame([3, 5], $instance->getAvailableSegmentLengths());
    }

    public function testGetAvailableSegmentLengthsExcludesOnlyUnreachedLengths(): void
    {
        $data = array_fill(0, 5, 1.0);
        $index = array_fill(0, 5, 1);

        $instance = new SubSegmentMaximization($data, $index, [2, 1000]);
        $instance->maximize();

        $this->assertSame([2], $instance->getAvailableSegmentLengths());
    }
}
