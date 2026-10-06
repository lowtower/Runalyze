<?php

namespace Runalyze\Tests\Parser\Activity\Common\Data\Round;

use Runalyze\Parser\Activity\Common\Data\Round\Round;
use Runalyze\Parser\Activity\Common\Data\Round\RoundCollection;

class RoundCollectionTest extends \PHPUnit\Framework\TestCase
{
    protected RoundCollection $Collection;

    protected function setUp(): void
    {
        $this->Collection = new RoundCollection();
    }

    public function testConstructorWithSomeElements(): void
    {
        $this->Collection = new RoundCollection([
            new Round(1.0, 285),
            new Round(1.0, 260),
        ]);

        $this->assertCount(2, $this->Collection);
        $this->assertSame(2.0, $this->Collection->getTotalDistance());
        $this->assertEquals(545, $this->Collection->getTotalDuration());
    }

    public function testDefaultConstructor(): void
    {
        $this->assertCount(0, $this->Collection);
        $this->assertSame(0.0, $this->Collection->getTotalDistance());
        $this->assertEquals(0, $this->Collection->getTotalDuration());
        $this->assertTrue($this->Collection->isEmpty());
        $this->assertFalse($this->Collection->offsetExists(0));
        $this->assertEmpty($this->Collection->getElements());
    }

    public function testDefaultArrayAccessors(): void
    {
        $this->assertCount(0, $this->Collection);
        $this->assertEmpty($this->Collection);

        $this->Collection[] = new Round(1.0, 285);
        $this->Collection[] = new Round(1.0, 260);

        $this->assertCount(2, $this->Collection);
        $this->assertNotEmpty($this->Collection);

        $this->assertInstanceOf(Round::class, $this->Collection[0]);
        $this->assertEquals(260, $this->Collection[1]->getDuration());
    }

    public function testAddingRounds(): void
    {
        $this->Collection->add(new Round(1.0, 285));
        $this->Collection->add(new Round(1.0, 260));

        $this->assertCount(2, $this->Collection);
    }

    public function testRoundingDurations(): void
    {
        $this->Collection->add(new Round(1.0, 285.12));
        $this->Collection->add(new Round(1.0, 260.74));
        $this->Collection->roundDurations();

        $this->assertEquals(285, $this->Collection[0]->getDuration());
        $this->assertEquals(261, $this->Collection[1]->getDuration());
    }

    public function testConstructorWithBadTypes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RoundCollection([
            new Round(1.0, 255),
            'foobar',
        ]);
    }

    public function testOffsetSetWithBadType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        // @phpstan-ignore offsetAssign.dimType, offsetAssign.valueType
        $this->Collection[] = 'foobar';
    }

    public function testComparison(): void
    {
        $this->Collection->add(new Round(1.0, 285));
        $this->Collection->add(new Round(1.0, 260));

        $this->assertTrue($this->Collection->isEqualTo(clone $this->Collection));
        $this->assertFalse($this->Collection->isEqualTo(new RoundCollection()));
    }

    public function testClearingCollection(): void
    {
        $this->Collection->add(new Round(1.0, 285));
        $this->Collection->clear();

        $this->assertTrue($this->Collection->isEmpty());
        $this->assertCount(0, $this->Collection);
    }
}
