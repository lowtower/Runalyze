<?php

namespace Runalyze;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Parameter\Application\Timezone as TimezoneEnum;

class TimezoneTest extends TestCase
{
    private function invokeStaticPrivate(string $method, array $args): mixed
    {
        $reflection = new \ReflectionClass(Timezone::class);
        $refMethod = $reflection->getMethod($method);

        return $refMethod->invoke(null, ...$args);
    }

    #[DataProvider('provideValidTimezoneIdentifiers')]
    public function testIsValidTimezoneReturnsTrueForValidIanaIdentifiers(string $timezone): void
    {
        $this->assertTrue(Timezone::isValidTimezone($timezone));
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideValidTimezoneIdentifiers(): array
    {
        return [
            'UTC' => ['UTC'],
            'Europe/Berlin' => ['Europe/Berlin'],
            'America/New_York' => ['America/New_York'],
            'Asia/Tokyo' => ['Asia/Tokyo'],
            'Pacific/Auckland' => ['Pacific/Auckland'],
            'GMT' => ['GMT'],
        ];
    }

    #[DataProvider('provideInvalidTimezoneIdentifiers')]
    public function testIsValidTimezoneReturnsFalseForInvalidIdentifiers(string $timezone): void
    {
        $this->assertFalse(Timezone::isValidTimezone($timezone));
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideInvalidTimezoneIdentifiers(): array
    {
        return [
            'completely made up identifier' => ['Not/AValidZone'],
            'empty string' => [''],
            'plain garbage text' => ['this-is-not-a-timezone'],
            'looks like a city but is not a real IANA zone' => ['Europe/Atlantis'],
        ];
    }

    public function testIsValidTimezoneAcceptsLowercaseIdentifierOnThisBuild(): void
    {
        $this->assertTrue(Timezone::isValidTimezone('europe/berlin'));
    }

    public function testGetChoicesReturnsNonEmptyArray(): void
    {
        $this->assertNotEmpty(Timezone::getChoices());
    }

    public function testGetChoicesValuesAreValidEnumIdsAndKeysMatchExpectedLabelFormat(): void
    {
        $choices = Timezone::getChoices();
        $labelPattern = '/^\(UTC[+-]\d{2}:\d{2}\) .+ - \d{1,2}:\d{2} (AM|PM)$/';

        foreach ($choices as $label => $enumId) {
            $this->assertTrue(
                TimezoneEnum::isValidValue($enumId),
                "Expected {$enumId} to be a valid TimezoneEnum value for label \"{$label}\""
            );
            $this->assertMatchesRegularExpression($labelPattern, $label);
        }
    }

    public function testListTimezonesKeysAreValidEnumIds(): void
    {
        foreach (array_keys(Timezone::listTimezones()) as $enumId) {
            $this->assertTrue(TimezoneEnum::isValidValue($enumId));
        }
    }

    public function testListPrettyTimezonesFormatsPositiveOffsetCorrectly(): void
    {
        $result = $this->invokeStaticPrivate('listPrettyTimezones', [
            [
                7200 => ['Europe/Berlin'],
            ],
            [
                'Europe/Berlin' => 'berlin',
            ],
        ]);

        $this->assertArrayHasKey(TimezoneEnum::BERLIN, $result);
        $this->assertStringStartsWith('(UTC+02:00) Europe/Berlin - ', $result[TimezoneEnum::BERLIN]);
    }

    public function testListPrettyTimezonesFormatsNegativeOffsetCorrectly(): void
    {
        $result = $this->invokeStaticPrivate('listPrettyTimezones', [
            [
                -18000 => ['America/New_York'],
            ],
            [
                'America/New_York' => 'new_york',
            ],
        ]);

        $this->assertArrayHasKey(TimezoneEnum::NEW_YORK, $result);
        $this->assertStringStartsWith('(UTC-05:00)', $result[TimezoneEnum::NEW_YORK]);
    }

    public function testListPrettyTimezonesFormatsHalfHourOffsetCorrectly(): void
    {
        $result = $this->invokeStaticPrivate('listPrettyTimezones', [
            [
                19800 => ['Asia/Kolkata'],
            ],
            [
                'Asia/Kolkata' => 'kolkata',
            ],
        ]);

        $this->assertStringStartsWith('(UTC+05:30)', $result[TimezoneEnum::KOLKATA]);
    }

    public function testListPrettyTimezonesFormatsZeroOffsetCorrectly(): void
    {
        $result = $this->invokeStaticPrivate('listPrettyTimezones', [
            [
                0 => ['UTC'],
            ],
            [
                'UTC' => 'utc',
            ],
        ]);

        $this->assertStringStartsWith('(UTC+00:00) UTC - ', $result[TimezoneEnum::UTC]);
    }

    public function testListPrettyTimezonesRemovesUnderscoresWithoutSeparatorInLabel(): void
    {
        $result = $this->invokeStaticPrivate('listPrettyTimezones', [
            [
                -14400 => ['America/New_York'],
            ],
            [
                'America/New_York' => 'new_york',
            ],
        ]);

        $label = $result[TimezoneEnum::NEW_YORK];

        $this->assertStringContainsString('America/NewYork', $label);
        $this->assertStringNotContainsString('_', $label);
        $this->assertStringNotContainsString('New York', $label);
    }

    public function testListPrettyTimezonesHandlesMultipleZonesAtSameOffset(): void
    {
        $result = $this->invokeStaticPrivate('listPrettyTimezones', [
            [
                3600 => ['Europe/Berlin', 'Europe/Paris'],
            ],
            [
                'Europe/Berlin' => 'berlin',
                'Europe/Paris' => 'paris',
            ],
        ]);

        $this->assertArrayHasKey(TimezoneEnum::BERLIN, $result);
        $this->assertArrayHasKey(TimezoneEnum::PARIS, $result);
    }
}
