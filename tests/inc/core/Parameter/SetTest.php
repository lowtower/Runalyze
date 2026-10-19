<?php

declare(strict_types=1);

namespace Runalyze\Parameter;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class SetTest extends \PHPUnit\Framework\TestCase
{
    protected Set $object;

    protected function setUp(): void
    {
        $this->object = new Set([]);
    }

    public function testDefaultValueIsEmptyArray(): void
    {
        $this->assertSame([], $this->object->value());
        $this->assertSame('', $this->object->valueAsString());
    }

    public function testSetChangesValueAndItsStringRepresentation(): void
    {
        $this->object->set(['a', 'b', 'c']);

        $this->assertSame(['a', 'b', 'c'], $this->object->value());
        $this->assertSame('a,b,c', $this->object->valueAsString());
    }

    public function testSetFromStringSplitsCommaSeparatedStringIntoArray(): void
    {
        $this->object->setFromString('1,2,3');

        $this->assertSame(['1', '2', '3'], $this->object->value());
    }

    public function testSetFromStringTrimsWhitespaceAroundValues(): void
    {
        $this->object->setFromString('a, b , c');

        $this->assertSame(['a', 'b', 'c'], $this->object->value());
    }

    public function testSetFromStringWithEmptyStringResultsInEmptyArray(): void
    {
        $this->object->set(['leftover']);
        $this->object->setFromString('');

        $this->assertSame([], $this->object->value());
    }

    public function testAppendAddsValueToTheEnd(): void
    {
        $this->object->append('one');

        $this->assertSame(['one'], $this->object->value());

        $this->object->append('two');

        $this->assertSame(['one', 'two'], $this->object->value());
    }

    public function testAppendDropsOldestEntryOnceMaxLengthIsExceeded(): void
    {
        $maxString = str_repeat('a', \Runalyze\Parameter::MAX_LENGTH);

        $this->object->append($maxString);
        $this->object->setFromString($this->object->valueAsString());

        $this->assertSame([$maxString], $this->object->value());

        $this->object->append('foo');
        $this->object->setFromString($this->object->valueAsString());

        $this->assertSame(['foo'], $this->object->value());
    }

    public function testArrayToStringReturnsEmptyStringForEmptyArray(): void
    {
        $this->assertSame('', Set::arrayToString([]));
    }

    public function testArrayToStringJoinsValuesWithSeparator(): void
    {
        $this->assertSame('a,b,c', Set::arrayToString(['a', 'b', 'c']));
    }
}
