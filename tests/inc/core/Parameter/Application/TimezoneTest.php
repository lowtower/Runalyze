<?php

namespace Runalyze\Parameter\Application;

class TimezoneTest extends \PHPUnit\Framework\TestCase
{
    public function testInvalidEnum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Timezone::getFullNameByEnum(-1);
    }

    public function testThatThereIsAMapping(): void
    {
        $this->assertNotEmpty(Timezone::getMapping());
    }

    public function testThatMappingIsCompleteAndValid(): void
    {
        $enums = Timezone::getEnum();
        $this->assertNotEmpty($enums, 'Timezone::getEnum() should not be empty.');

        // We can't be sure which timezone database version is used
        $unknownTimezones = [];

        foreach ($enums as $enum) {
            $identifier = Timezone::getFullNameByEnum($enum);

            $this->assertNotSame('', $identifier, sprintf('Timezone::getFullNameByEnum(%d) returned an empty string.', $enum));

            try {
                new \DateTimeZone($identifier);
            } catch (\Exception $e) {
                $unknownTimezones[] = $identifier;
            }
        }

        if ($unknownTimezones !== []) {
            $this->markTestSkipped('Unknown timezones: ' . implode(', ', $unknownTimezones));
        }
    }

    public function testThatAllEnumsCanBeFoundByOriginalName(): void
    {
        foreach (Timezone::getEnum() as $enum) {
            $identifier = Timezone::getFullNameByEnum($enum);

            $on = Timezone::getEnumByOriginalName($identifier);
            $this->assertEquals($enum, $on, 'Problem with ' . $identifier . ': ' . $enum . ' not equal ' . $on);
        }
    }

    public function testInvalidOriginalName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Timezone::getEnumByOriginalName('Horsehead_Nebula/Magrathea');
    }
}
