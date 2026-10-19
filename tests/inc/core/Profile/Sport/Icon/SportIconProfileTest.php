<?php

declare(strict_types=1);

namespace Runalyze\Profile\Sport\Icon;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Util\InterfaceChoosable;

#[Group('default')]
final class SportIconProfileTest extends TestCase
{
    public function testImplementsInterfaceChoosable(): void
    {
        $this->assertInstanceOf(InterfaceChoosable::class, new SportIconProfile());
    }

    public function testGetChoicesReturnsArrayWhereKeyEqualsValueForEachEntry(): void
    {
        $choices = SportIconProfile::getChoices();

        foreach ($choices as $key => $value) {
            $this->assertSame($key, $value);
        }
    }

    #[DataProvider('provideExpectedIconClasses')]
    public function testGetChoicesContainsIconClass(string $expectedIconClass): void
    {
        $choices = SportIconProfile::getChoices();

        $this->assertArrayHasKey($expectedIconClass, $choices);
        $this->assertSame($expectedIconClass, $choices[$expectedIconClass]);
    }

    /**
     * @return array<string, string[]>
     */
    public static function provideExpectedIconClasses(): array
    {
        return [
            'icons8-Boating' => ['icons8-Boating'],
            'icons8-Bouldern' => ['icons8-Bouldern'],
            'icons8-Climbing' => ['icons8-Climbing'],
            'icons8-CrossCountrySkiing' => ['icons8-CrossCountrySkiing'],
            'icons8-Dancing' => ['icons8-Dancing'],
            'icons8-Exercise' => ['icons8-Exercise'],
            'icons8-Football' => ['icons8-Football'],
            'icons8-Free-Swimming' => ['icons8-Free-Swimming'],
            'icons8-Golf' => ['icons8-Golf'],
            'icons8-Guru' => ['icons8-Guru'],
            'icons8-Handball' => ['icons8-Handball'],
            'icons8-Kayaking' => ['icons8-Kayaking'],
            'icons8-Mountain-Biking' => ['icons8-Mountain-Biking'],
            'icons8-Mountaineering' => ['icons8-Mountaineering'],
            'icons8-Paddling' => ['icons8-Paddling'],
            'icons8-Pilates' => ['icons8-Pilates'],
            'icons8-Pushups' => ['icons8-Pushups'],
            'icons8-Regular-Biking' => ['icons8-Regular-Biking'],
            'icons8-Rock-Climbing' => ['icons8-Rock-Climbing'],
            'icons8-Roller-Skating' => ['icons8-Roller-Skating'],
            'icons8-Rowing' => ['icons8-Rowing'],
            'icons8-Running' => ['icons8-Running'],
            'icons8-SnowBoarding' => ['icons8-SnowBoarding'],
            'icons8-SnowShoeing' => ['icons8-SnowShoeing'],
            'icons8-Sports-Mode' => ['icons8-Sports-Mode'],
            'icons8-Surfing' => ['icons8-Surfing'],
            'icons8-Swimming' => ['icons8-Swimming'],
            'icons8-Tennis' => ['icons8-Tennis'],
            'icons8-Time-Trial-Biking' => ['icons8-Time-Trial-Biking'],
            'icons8-Trekking' => ['icons8-Trekking'],
            'icons8-Walking' => ['icons8-Walking'],
            'icons8-Weightlift' => ['icons8-Weightlift'],
            'icons8-WindSurfing' => ['icons8-WindSurfing'],
            'icons8-Yoga' => ['icons8-Yoga'],
            'icons8-skiing' => ['icons8-skiing'],
        ];
    }

    public function testGetChoicesCurrentlyHasThirtyFiveUniqueEntriesDueToADuplicateSourceListing(): void
    {
        $choices = SportIconProfile::getChoices();

        $this->assertCount(35, $choices);
        $this->assertCount(35, self::provideExpectedIconClasses());
    }

    public function testGetChoicesReturnsNoUnexpectedEntriesBeyondTheKnownThirtyFive(): void
    {
        $choices = SportIconProfile::getChoices();
        $expectedKeys = array_column(self::provideExpectedIconClasses(), 0);

        $this->assertEqualsCanonicalizing($expectedKeys, array_keys($choices));
    }

    public function testGetChoicesReturnsNonEmptyArray(): void
    {
        $choices = SportIconProfile::getChoices();

        $this->assertNotEmpty($choices);
    }
}
