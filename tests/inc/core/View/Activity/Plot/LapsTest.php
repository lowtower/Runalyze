<?php

declare(strict_types=1);

namespace Runalyze\View\Activity\Plot;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Metrics\Velocity\Unit\PaceEnum;
use Runalyze\Model\Activity\Entity as Activity;
use Runalyze\Model\Sport\Entity as Sport;
use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\View\Activity\Context;

final class LapsTest extends TestCase
{
    #[DataProvider('paceFormats')]
    public function testRendersBarsAndThresholds(int $unit, string $barValue, int $target, int $manualAverage, int $computedAverage, string $label, string $axisFormat): void
    {
        $context = $this->createStub(Context::class);
        $context->method('activity')->willReturn(new Activity([
            Activity::DISTANCE => 2.0,
            Activity::TIME_IN_SECONDS => 616,
            Activity::SPLITS => '1.2|6:10',
            Activity::TITLE => 'Run in 5:00',
        ]));
        $context->method('sport')->willReturn(new Sport([
            Sport::PACE_UNIT => $unit,
        ]));
        $context->method('trackdata')->willReturn(new Trackdata([
            Trackdata::TIME => [0, 308, 616],
            Trackdata::DISTANCE => [0, 1, 2],
        ]));

        $manual = (string) new LapsManual($context)->templateData()['js'];
        $computed = (string) new LapsComputed($context)->templateData()['js'];

        $this->assertStringContainsString('"data":[[0,' . $barValue . ']]', $manual);
        $this->assertStringContainsString('"data":[[0,' . $barValue . '],[1,' . $barValue . ']]', $computed);
        $this->assertStringContainsString('"color":"rgb(180,0,0)","lineWidth":1,"yaxis":{"from":' . $target . ',"to":' . $target . '}', $manual);
        $this->assertStringContainsString('"y":' . $manualAverage . ',"text":' . json_encode(__('avg:') . ' ' . $label), $manual);
        $this->assertStringContainsString('"y":' . $computedAverage . ',"text":' . json_encode(__('avg:') . ' ' . $label), $computed);
        $this->assertStringContainsString($axisFormat, $manual);
        $this->assertStringContainsString($axisFormat, $computed);
    }

    public static function paceFormats(): iterable
    {
        yield 'minutes per mile' => [PaceEnum::SECONDS_PER_MILE, '496000', 482803, 496214, 495678, '8:16', '"timeformat":"%M:%S"'];
        yield 'kilometers per hour' => [PaceEnum::KILOMETER_PER_HOUR, '11.7', 12, 12, 12, '11,7', "' km/h'"];
        yield 'unknown unit' => [999, '11.7', 12, 12, 12, '11,7', "' km/h'"];
    }
}
