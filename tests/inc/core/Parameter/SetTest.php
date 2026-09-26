<?php

declare(strict_types=1);

namespace Runalyze\Parameter;

#[\PHPUnit\Framework\Attributes\Group('default')]
class SetTest extends \PHPUnit\Framework\TestCase
{
    protected Set $object;

    protected function setUp(): void
    {
        $this->object = new Set([]);
    }

    public function testDefaultValueIsEmptyArray(): void
    {
        self::assertSame([], $this->object->value());
        self::assertSame('', $this->object->valueAsString());
    }

    public function testSetChangesValueAndItsStringRepresentation(): void
    {
        $this->object->set(['a', 'b', 'c']);

        self::assertSame(['a', 'b', 'c'], $this->object->value());
        self::assertSame('a,b,c', $this->object->valueAsString());
    }

    public function testSetFromStringSplitsCommaSeparatedStringIntoArray(): void
    {
        $this->object->setFromString('1,2,3');

        self::assertSame(['1', '2', '3'], $this->object->value());
    }

    public function testSetFromStringTrimsWhitespaceAroundValues(): void
    {
        $this->object->setFromString('a, b , c');

        self::assertSame(['a', 'b', 'c'], $this->object->value());
    }

    public function testSetFromStringWithEmptyStringResultsInEmptyArray(): void
    {
        $this->object->set(['leftover']);
        $this->object->setFromString('');

        self::assertSame([], $this->object->value());
    }

    public function testAppendAddsValueToTheEnd(): void
    {
        $this->object->append('one');

        self::assertSame(['one'], $this->object->value());

        $this->object->append('two');

        self::assertSame(['one', 'two'], $this->object->value());
    }

    public function testAppendDropsOldestEntryOnceMaxLengthIsExceeded(): void
    {
        $maxString = str_repeat('a', \Runalyze\Parameter::MAX_LENGTH);

        $this->object->append($maxString);
        $this->object->setFromString($this->object->valueAsString());

        self::assertSame([$maxString], $this->object->value());

        $this->object->append('foo');
        $this->object->setFromString($this->object->valueAsString());

        self::assertSame(['foo'], $this->object->value());
    }

    public function testArrayToStringReturnsEmptyStringForEmptyArray(): void
    {
        self::assertSame('', Set::arrayToString([]));
    }

    public function testArrayToStringJoinsValuesWithSeparator(): void
    {
        self::assertSame('a,b,c', Set::arrayToString(['a', 'b', 'c']));
    }
}
