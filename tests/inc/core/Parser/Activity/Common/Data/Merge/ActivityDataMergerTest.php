<?php

declare(strict_types=1);

namespace Runalyze\Tests\Parser\Activity\Data\Merge;

use Runalyze\Parser\Activity\Common\Data\ActivityData;
use Runalyze\Parser\Activity\Common\Data\Merge\ActivityDataMerger;

final class ActivityDataMergerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ActivityData
     */
    protected $FirstData;

    /**
     * @var ActivityData
     */
    protected $SecondData;

    protected function setUp(): void
    {
        $this->FirstData = new ActivityData();
        $this->SecondData = new ActivityData();
    }

    public function testWithEmptySecondObject(): void
    {
        $this->FirstData->Duration = 321;

        new ActivityDataMerger($this->FirstData, $this->SecondData)->merge();

        $this->assertEquals(321, $this->FirstData->Duration);
    }

    public function testWithEmptyFirstObject(): void
    {
        $this->FirstData->Duration = 321;

        new ActivityDataMerger($this->FirstData, $this->SecondData)->merge();

        $this->assertEquals(321, $this->FirstData->Duration);
    }
}
