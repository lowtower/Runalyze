<?php

declare(strict_types=1);

namespace Runalyze\Activity;

class PerformanceConditionTest extends \PHPUnit\Framework\TestCase
{
    public function testFormattingValues(): void
    {
        $this->assertSame('', PerformanceCondition::format(null));
        $this->assertEquals('+2', PerformanceCondition::format(102));
        $this->assertSame('&plusmn;0', PerformanceCondition::format(100));
        $this->assertEquals('-3', PerformanceCondition::format(97));
    }

    public function testUnknownValue(): void
    {
        $Condition = new PerformanceCondition();

        $this->assertFalse($Condition->isKnown());
        $this->assertSame('', $Condition->string());
        $this->assertNull($Condition->value());
    }

    public function testThatInvalidValuesAreTreatedAsUnknown(): void
    {
        $Condition = new PerformanceCondition();

        $this->assertNull($Condition->set(PerformanceCondition::LOWER_LIMIT - 1)->value());
        $this->assertNotNull($Condition->set(PerformanceCondition::LOWER_LIMIT)->value());
        $this->assertNotNull($Condition->set(PerformanceCondition::UPPER_LIMIT)->value());
        $this->assertNull($Condition->set(PerformanceCondition::UPPER_LIMIT + 1)->value());
    }
}
