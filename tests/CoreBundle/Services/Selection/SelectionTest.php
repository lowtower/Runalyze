<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services\Selection;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Services\Selection\Selection;

#[\PHPUnit\Framework\Attributes\Group('default')]
class SelectionTest extends TestCase
{
    public function testEmptySelection(): void
    {
        $list = new Selection([]);

        $this->assertEquals([], $list->getList());
        $this->assertNull($list->getCurrentKey());
        $this->assertFalse($list->hasCurrentKey());
    }

    public function testSelectionWithoutCurrentKey(): void
    {
        $list = new Selection(['foo', 'bar']);

        $this->assertEquals(['foo', 'bar'], $list->getList());
        $this->assertNull($list->getCurrentKey());
        $this->assertFalse($list->hasCurrentKey());
        $this->assertEquals('foo', $list->getCurrentLabel());
    }

    public function testSelectionWithCurrentKey(): void
    {
        $list = new Selection(['foo', 'bar'], 1);

        $this->assertEquals(1, $list->getCurrentKey());
        $this->assertTrue($list->hasCurrentKey());
        $this->assertEquals('bar', $list->getCurrentLabel());
    }

    public function testUnknownCurrentKey(): void
    {
        $list = new Selection([
            'foo' => 'bar',
        ], 'baz');

        $this->assertFalse($list->hasCurrentKey());
    }

    public function testGetListReturnsTheConstructorArgumentUnchanged(): void
    {
        $list = [
            'a' => 'Alpha',
            'b' => 'Beta',
        ];
        $selection = new Selection($list);

        $this->assertSame($list, $selection->getList());
    }

    public function testHasNoCurrentKeyByDefault(): void
    {
        $selection = new Selection([
            'a' => 'Alpha',
        ]);

        $this->assertFalse($selection->hasCurrentKey());
        $this->assertNull($selection->getCurrentKey());
    }

    public function testHasCurrentKeyWhenConstructedWithAKnownKey(): void
    {
        $selection = new Selection([
            'a' => 'Alpha',
            'b' => 'Beta',
        ], 'b');

        $this->assertTrue($selection->hasCurrentKey());
        $this->assertSame('b', $selection->getCurrentKey());
    }

    public function testHasNoCurrentKeyWhenConstructedWithAnUnknownKey(): void
    {
        $selection = new Selection([
            'a' => 'Alpha',
        ], 'unknown');

        $this->assertFalse($selection->hasCurrentKey());
    }

    public function testSetCurrentKeyChangesTheCurrentKey(): void
    {
        $selection = new Selection([
            'a' => 'Alpha',
            'b' => 'Beta',
        ]);

        $selection->setCurrentKey('b');

        $this->assertTrue($selection->hasCurrentKey());
        $this->assertSame('b', $selection->getCurrentKey());
    }

    public function testSetCurrentKeyToAnUnknownKeyMakesHasCurrentKeyFalse(): void
    {
        $selection = new Selection([
            'a' => 'Alpha',
        ], 'a');

        $selection->setCurrentKey('unknown');

        $this->assertFalse($selection->hasCurrentKey());
    }

    public function testGetCurrentLabelReturnsTheLabelForTheCurrentKey(): void
    {
        $selection = new Selection([
            'a' => 'Alpha',
            'b' => 'Beta',
        ], 'b');

        $this->assertSame('Beta', $selection->getCurrentLabel());
    }

    public function testGetCurrentLabelFallsBackToTheFirstEntryWithoutACurrentKey(): void
    {
        $selection = new Selection([
            'a' => 'Alpha',
            'b' => 'Beta',
        ]);

        $this->assertSame('Alpha', $selection->getCurrentLabel());
    }

    public function testGetCurrentLabelFallsBackToTheFirstEntryForAnUnknownCurrentKey(): void
    {
        $selection = new Selection([
            'a' => 'Alpha',
            'b' => 'Beta',
        ], 'unknown');

        $this->assertSame('Alpha', $selection->getCurrentLabel());
    }

    public function testGetCurrentLabelReturnsNullForAnEmptyList(): void
    {
        $selection = new Selection([]);

        $this->assertNull($selection->getCurrentLabel());
    }
}
