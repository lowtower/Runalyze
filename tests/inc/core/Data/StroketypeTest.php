<?php

declare(strict_types=1);

namespace Runalyze\Data;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Profile\FitSdk\StrokeTypeProfile;

#[Group('default')]
class StroketypeTest extends TestCase
{
    public function testSetAcceptsValidIdentifierFromCompleteList(): void
    {
        $Stroketype = new Stroketype(StrokeTypeProfile::FREESTYLE);

        $this->assertSame(StrokeTypeProfile::FREESTYLE, $Stroketype->id());
    }

    public function testConstructorThrowsInvalidArgumentExceptionForInvalidIdentifier(): void
    {
        $invalidIdentifier = $this->findInvalidIdentifier();

        $this->expectException(\InvalidArgumentException::class);

        new Stroketype($invalidIdentifier);
    }

    public function testSetThrowsInvalidArgumentExceptionAndKeepsPreviousValueUnchanged(): void
    {
        $Stroketype = new Stroketype(StrokeTypeProfile::FREESTYLE);
        $invalidIdentifier = $this->findInvalidIdentifier();

        try {
            $Stroketype->set($invalidIdentifier);
            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (\InvalidArgumentException) {
            // expected
        }

        $this->assertSame(StrokeTypeProfile::FREESTYLE, $Stroketype->id());
    }

    public function testCompleteListReturnsTheRealStrokeTypeProfileEnum(): void
    {
        $this->assertSame(StrokeTypeProfile::getEnum(), Stroketype::completeList());
    }

    public function testCompleteListContainsAllConstantsUsedInStringMatch(): void
    {
        $list = Stroketype::completeList();

        foreach ([
            StrokeTypeProfile::FREESTYLE,
            StrokeTypeProfile::BACK,
            StrokeTypeProfile::BREAST,
            StrokeTypeProfile::BUTTERFLY,
            StrokeTypeProfile::DRILL,
            StrokeTypeProfile::MIXED,
            StrokeTypeProfile::BREAK,
        ] as $identifier) {
            $this->assertContains($identifier, $list, "Identifier {$identifier} missing from completeList()");
        }
    }

    public function testStringReturnsFullLabelForEachKnownStrokeType(): void
    {
        $this->assertSame('Freestyle', new Stroketype(StrokeTypeProfile::FREESTYLE)->string());
        $this->assertSame('Backstroke', new Stroketype(StrokeTypeProfile::BACK)->string());
        $this->assertSame('Breaststroke', new Stroketype(StrokeTypeProfile::BREAST)->string());
        $this->assertSame('Butterfly', new Stroketype(StrokeTypeProfile::BUTTERFLY)->string());
        $this->assertSame('Drill', new Stroketype(StrokeTypeProfile::DRILL)->string());
        $this->assertSame('Mixed', new Stroketype(StrokeTypeProfile::MIXED)->string());
        $this->assertSame('Break', new Stroketype(StrokeTypeProfile::BREAK)->string());
    }

    public function testShortStringUsesAbbreviatedLabelsForBackAndBreastOnly(): void
    {
        $this->assertSame('Back', new Stroketype(StrokeTypeProfile::BACK)->shortString());
        $this->assertSame('Breast', new Stroketype(StrokeTypeProfile::BREAST)->shortString());
    }

    public function testShortStringMatchesFullStringForAllOtherKnownStrokeTypes(): void
    {
        $this->assertSame('Freestyle', new Stroketype(StrokeTypeProfile::FREESTYLE)->shortString());
        $this->assertSame('Butterfly', new Stroketype(StrokeTypeProfile::BUTTERFLY)->shortString());
        $this->assertSame('Drill', new Stroketype(StrokeTypeProfile::DRILL)->shortString());
        $this->assertSame('Mixed', new Stroketype(StrokeTypeProfile::MIXED)->shortString());
        $this->assertSame('Break', new Stroketype(StrokeTypeProfile::BREAK)->shortString());
    }

    public function testIsBreakIsTrueOnlyForBreakIdentifier(): void
    {
        $this->assertTrue(new Stroketype(StrokeTypeProfile::BREAK)->isBreak());
    }

    public function testIsBreakIsFalseForOtherKnownStrokeTypes(): void
    {
        $this->assertFalse(new Stroketype(StrokeTypeProfile::FREESTYLE)->isBreak());
        $this->assertFalse(new Stroketype(StrokeTypeProfile::BACK)->isBreak());
        $this->assertFalse(new Stroketype(StrokeTypeProfile::BREAST)->isBreak());
        $this->assertFalse(new Stroketype(StrokeTypeProfile::BUTTERFLY)->isBreak());
        $this->assertFalse(new Stroketype(StrokeTypeProfile::DRILL)->isBreak());
        $this->assertFalse(new Stroketype(StrokeTypeProfile::MIXED)->isBreak());
    }

    private function findInvalidIdentifier(): int
    {
        $validIdentifiers = Stroketype::completeList();
        $candidate = PHP_INT_MAX;

        while (in_array($candidate, $validIdentifiers, true)) {
            $candidate--;
        }

        return $candidate;
    }
}
