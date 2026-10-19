<?php

declare(strict_types=1);

namespace Runalyze\Parameter\Application;

use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class ApplicationTimezoneEnumTest extends TestCase
{
    public function testGetMappingReturnsNonEmptyArray(): void
    {
        $this->assertNotEmpty(Timezone::getMapping());
    }

    public function testGetFullNameByEnumForUtc(): void
    {
        $this->assertSame('UTC', Timezone::getFullNameByEnum(Timezone::UTC));
    }

    public function testGetFullNameByEnumForBerlin(): void
    {
        $this->assertSame('Europe/Berlin', Timezone::getFullNameByEnum(Timezone::BERLIN));
    }

    public function testGetFullNameByEnumForNewYork(): void
    {
        $this->assertSame('America/New_York', Timezone::getFullNameByEnum(Timezone::NEW_YORK));
    }

    public function testGetFullNameByEnumForTokyo(): void
    {
        $this->assertSame('Asia/Tokyo', Timezone::getFullNameByEnum(Timezone::TOKYO));
    }

    public function testGetFullNameByEnumThrowsForUnknownEnumValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Timezone::getFullNameByEnum(999999);
    }

    public function testGetEnumByOriginalNameForBerlin(): void
    {
        $this->assertSame(Timezone::BERLIN, Timezone::getEnumByOriginalName('Europe/Berlin'));
    }

    public function testGetEnumByOriginalNameForUtc(): void
    {
        $this->assertSame(Timezone::UTC, Timezone::getEnumByOriginalName('UTC'));
    }

    public function testGetEnumByOriginalNameThrowsForUnknownName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Timezone::getEnumByOriginalName('Not/ARealZone');
    }

    public function testGetEnumByOriginalNameIsCaseSensitive(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Timezone::getEnumByOriginalName('europe/berlin');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideWellKnownZones')]
    public function testGetFullNameByEnumAndGetEnumByOriginalNameAreInverses(int $enum): void
    {
        $fullName = Timezone::getFullNameByEnum($enum);
        $roundTrippedEnum = Timezone::getEnumByOriginalName($fullName);

        $this->assertSame($enum, $roundTrippedEnum);
    }

    /**
     * @return \Iterator<string, array<int>>
     */
    public static function provideWellKnownZones(): \Iterator
    {
        yield 'UTC' => [Timezone::UTC];
        yield 'Berlin' => [Timezone::BERLIN];
        yield 'New York' => [Timezone::NEW_YORK];
        yield 'Tokyo' => [Timezone::TOKYO];
        yield 'London' => [Timezone::LONDON];
        yield 'Paris' => [Timezone::PARIS];
        yield 'Sydney' => [Timezone::SYDNEY];
    }

    public function testEveryDefinedEnumConstantResolvesToACurrentlyValidIanaIdentifier(): void
    {
        $failures = [];

        foreach (Timezone::getEnum() as $name => $value) {
            try {
                $fullName = Timezone::getFullNameByEnum($value);
            } catch (\InvalidArgumentException) {
                $failures[] = "{$name} ({$value}): no corresponding entry in \$Mapping";

                continue;
            }

            try {
                new \DateTimeZone($fullName);
            } catch (\Exception) {
                $failures[] = "{$name} ({$value}) -> \"{$fullName}\": not accepted by DateTimeZone on this system";
            }
        }

        $this->assertSame([], $failures, "Found inconsistent timezone enum entries:\n" . implode("\n", $failures));
    }

    public function testEveryMappingEntryHasACorrespondingEnumConstant(): void
    {
        $constantNames = array_keys(Timezone::getEnum());
        $orphans = [];

        foreach (array_keys(Timezone::getMapping()) as $mappingKey) {
            $expectedConstantName = strtoupper($mappingKey);

            if (! in_array($expectedConstantName, $constantNames, true)) {
                $orphans[] = "mapping key \"{$mappingKey}\" has no matching constant \"{$expectedConstantName}\"";
            }
        }

        $this->assertSame([], $orphans, "Found orphaned mapping entries:\n" . implode("\n", $orphans));
    }
}
