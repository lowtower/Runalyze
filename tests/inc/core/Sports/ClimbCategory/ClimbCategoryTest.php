<?php

namespace Runalyze\Tests\Sports\ClimbCategory;

use Runalyze\Sports\ClimbCategory\ClimbCategory;

class ClimbCategoryTest extends \PHPUnit\Framework\TestCase
{
    public function testThatAllCategoriesCanBeConvertedToString(): void
    {
        $object = new ClimbCategory();

        foreach (ClimbCategory::getOptions() as $category) {
            $object->setCategory($category);

            $this->assertSame((string) $object, $object->getString());
        }
    }

    public function testThatInvalidCategoryIsFetched(): void
    {
        $object = new ClimbCategory();

        $this->expectException(\InvalidArgumentException::class);

        $object->setCategory(-1);
    }

    public function testCategoryMapping(): void
    {
        $categoryLimits = [10.0, 7.5, 5.0, 3.0, 2.0, 1.0];

        $this->assertSame('hc', ClimbCategory::getCategoryFor(12.3, $categoryLimits)->getString());
        $this->assertEquals('1', ClimbCategory::getCategoryFor(9.9, $categoryLimits)->getString());
        $this->assertEquals('2', ClimbCategory::getCategoryFor(5.0, $categoryLimits)->getString());
        $this->assertEquals('3', ClimbCategory::getCategoryFor(3.3, $categoryLimits)->getString());
        $this->assertEquals('4', ClimbCategory::getCategoryFor(2.7, $categoryLimits)->getString());
        $this->assertEquals('5', ClimbCategory::getCategoryFor(1.6, $categoryLimits)->getString());
        $this->assertSame('-', ClimbCategory::getCategoryFor(0.4, $categoryLimits)->getString());
    }

    public function testThatCategoryMappingIgnoresAdditionalLimits(): void
    {
        $this->assertFalse(ClimbCategory::getCategoryFor(0.5, [10, 7, 5, 3, 2, 1, 0, -2])->isClassified());
    }
}
