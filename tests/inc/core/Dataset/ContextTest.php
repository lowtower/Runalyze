<?php

namespace Runalyze\Dataset;

use Runalyze\Model\Activity;

class ContextTest extends \PHPUnit\Framework\TestCase
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

        $this->assertEquals('30.10.2015', date('d.m.Y', $Context->activity()->timestamp()));
        $this->assertEquals(3600, $Context->activity()->duration());
        $this->assertEquals(12.3, $Context->activity()->distance());
        $this->assertEquals(42, $Context->data('more-data'));
        $this->assertFalse($Context->hasSport());
        $this->assertFalse($Context->hasType());
        $this->assertTrue($Context->dataview() instanceof \Runalyze\View\Activity\Dataview);
        $this->assertTrue($Context->linker() instanceof \Runalyze\View\Activity\Linker);
    }

    public function testContextFromObject(): void
    {
        $Context = new Context(new Activity\Entity([
            Activity\Entity::TIMESTAMP => mktime(12, 0, 0, 10, 30, 2015),
        ]), 0);

        $this->assertEquals('30.10.2015', date('d.m.Y', $Context->activity()->timestamp()));
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

        $this->assertEquals('11.11.2015', date('d.m.Y', $Context->activity()->timestamp()));
    }
}
