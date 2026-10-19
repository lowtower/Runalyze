<?php

declare(strict_types=1);

namespace Runalyze\Dataset;

use Runalyze\Model\Activity;

final class ContextTest extends \PHPUnit\Framework\TestCase
{
    public function testThatAllStringMethodsWork(): void
    {
        $Context = new Context([
            Activity\Entity::TIMESTAMP => mktime(12, 0, 0, 10, 30, 2015),
            Activity\Entity::TIME_IN_SECONDS => 3600,
            Activity\Entity::DISTANCE => 12.3,
            Keys\Pace::DURATION_SUM_WITH_DISTANCE_KEY => 42,
        ], 0);

        $Configuration = new DefaultConfiguration();

        foreach ($Configuration->allKeys() as $keyid) {
            $stringValue = Keys::get($keyid)->stringFor($Context);
            $this->assertTrue(
                is_string($stringValue) || $stringValue === null,
                'stringFor() should return a string or null for key ' . $keyid
            );
        }
    }

    public function testContextFromArrayWithoutSportAndType(): void
    {
        $Context = new Context([
            Activity\Entity::TIMESTAMP => mktime(12, 0, 0, 10, 30, 2015),
            Activity\Entity::TIME_IN_SECONDS => 3600,
            Activity\Entity::DISTANCE => 12.3,
            'more-data' => 42,
        ], 0);

        $this->assertSame('30.10.2015', date('d.m.Y', $Context->activity()->timestamp()));
        $this->assertSame(3600, $Context->activity()->duration());
        $this->assertEqualsWithDelta(12.3, $Context->activity()->distance(), PHP_FLOAT_EPSILON);
        $this->assertEquals(42, $Context->data('more-data'));
        $this->assertFalse($Context->hasSport());
        $this->assertFalse($Context->hasType());
        $this->assertInstanceOf(\Runalyze\View\Activity\Dataview::class, $Context->dataview());
        $this->assertInstanceOf(\Runalyze\View\Activity\Linker::class, $Context->linker());
    }

    public function testContextFromObject(): void
    {
        $Context = new Context(new Activity\Entity([
            Activity\Entity::TIMESTAMP => mktime(12, 0, 0, 10, 30, 2015),
        ]), 0);

        $this->assertSame('30.10.2015', date('d.m.Y', $Context->activity()->timestamp()));
    }

    public function testUnknownData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $Context = new Context(new Activity\Entity([
            Activity\Entity::TIMESTAMP => mktime(12, 0, 0, 10, 30, 2015),
        ]), 0);

        $Context->data('non-existing-key');
    }

    public function testUnknownDataWithNull(): void
    {
        $Context = new Context(new Activity\Entity([
            Activity\Entity::TIMESTAMP => mktime(12, 0, 0, 10, 30, 2015),
            'null-data' => null,
        ]), 0);

        $this->assertNull($Context->data('null-data', true));
    }

    public function testWrongConstructor(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Context(0, 0);
    }

    public function testWrongSetActivityData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Context([], 0);
    }

    public function testSettingNewActivityAfterConstructor(): void
    {
        $Context = new Context(new Activity\Entity([
            Activity\Entity::TIMESTAMP => mktime(12, 0, 0, 10, 30, 2015),
        ]), 0);
        $Context->setActivityData([
            Activity\Entity::TIMESTAMP => mktime(12, 0, 0, 11, 11, 2015),
        ]);

        $this->assertSame('11.11.2015', date('d.m.Y', $Context->activity()->timestamp()));
    }
}
