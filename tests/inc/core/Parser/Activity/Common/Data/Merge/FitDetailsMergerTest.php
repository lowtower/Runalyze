<?php

namespace Runalyze\Tests\Parser\Activity\Data\Merge;

use Runalyze\Parser\Activity\Common\Data\FitDetails;
use Runalyze\Parser\Activity\Common\Data\Merge\FitDetailsMerger;

class FitDetailsMergerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FitDetails
     */
    protected $FirstDetails;

    /**
     * @var FitDetails
     */
    protected $SecondDetails;

    protected function setUp(): void
    {
        $this->FirstDetails = new FitDetails();
        $this->SecondDetails = new FitDetails();
    }

    public function testThatMergeWorksWithEmptyObjects(): void
    {
        new FitDetailsMerger($this->FirstDetails, $this->SecondDetails)->merge();

        // Properties mirrored from FitDetailsMerger::getPropertiesToMerge() (protected)
        $properties = [
            'VO2maxEstimate',
            'RecoveryTime',
            'HrvAnalysis',
            'TrainingEffect',
            'PerformanceCondition',
            'PerformanceConditionEnd',
        ];

        foreach ($properties as $property) {
            $this->assertNull(
                $this->FirstDetails->{$property},
                sprintf('Property "%s" should remain null after merging two empty objects.', $property)
            );
        }
    }

    public function testMergingPerformanceCondition(): void
    {
        $this->FirstDetails->PerformanceCondition = 3;
        $this->SecondDetails->PerformanceConditionEnd = -1;

        new FitDetailsMerger($this->FirstDetails, $this->SecondDetails)->merge();

        $this->assertSame(3, $this->FirstDetails->PerformanceCondition);
        $this->assertEquals(-1, $this->FirstDetails->PerformanceConditionEnd);
    }
}
