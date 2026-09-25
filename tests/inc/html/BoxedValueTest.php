<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('plugin')]
class BoxedValueTest extends TestCase
{
    public function testGetCodeForEmptyValueHasNoIconUnitOrInfo(): void
    {
        $Value = new BoxedValue();

        $this->assertSame(
            '<div class="boxed-value-container"><div class="boxed-value"></div></div>',
            $Value->getCode()
        );
    }

    public function testGetCodeIncludesUnitAndInfoWithGlossaryLink(): void
    {
        $Value = new BoxedValue('42', 'km', 'Distance', '', 'distance-glossary');

        $this->assertSame(
            '<div class="boxed-value-container">'
            . '<div class="boxed-value">42 <div class="boxed-value-unit">km</div></div>'
            . '<div class="boxed-value-info">'
            . '<a class="window left" href="glossary/distance-glossary"><i class="fa far fa-question-circle"></i></a>'
            . 'Distance</div></div>',
            $Value->getCode()
        );
    }

    public function testGetCodeSwitchesContainerClassWhenIconIsSet(): void
    {
        $Value = new BoxedValue('10', '', '', '<i class="my-icon"></i>');

        $this->assertSame(
            '<div class="boxed-value-container with-icon"><i class="my-icon"></i><div class="boxed-value">10</div></div>',
            $Value->getCode()
        );
    }

    public function testGetCodeWrapsInFloatingBlockWithWidthClass(): void
    {
        $Value = new BoxedValue('5');
        $Value->defineAsFloatingBlock('w50');

        $this->assertSame(
            '<div class="boxed-value-outer w50"><div class="boxed-value-container"><div class="boxed-value">5</div></div></div>',
            $Value->getCode()
        );
    }

    public function testGetCodeWrapsInFloatingBlockWithFixedWidthForTwoBlocks(): void
    {
        $Value = new BoxedValue('5');
        $Value->defineAsFloatingBlockWithFixedWidth(2);

        $this->assertSame(
            '<div class="boxed-value-outer" style="width:50%;"><div class="boxed-value-container"><div class="boxed-value">5</div></div></div>',
            $Value->getCode()
        );
    }

    public function testDefineAsFloatingBlockWithFixedWidthRoundsToTwoDecimalsForThreeBlocks(): void
    {
        $Value = new BoxedValue('5');
        $Value->defineAsFloatingBlockWithFixedWidth(3);

        $this->assertStringContainsString('width:33.33%;', $Value->getCode());
    }

    public function testAddClassAppendsSingleClassWithLeadingSpace(): void
    {
        $Value = new BoxedValue('5');
        $Value->addClass('colored-orange');

        $this->assertSame(
            '<div class="boxed-value-container colored-orange"><div class="boxed-value">5</div></div>',
            $Value->getCode()
        );
    }

    public function testAddClassCalledTwiceSeparatesClassesWithSpace(): void
    {
        $Value = new BoxedValue('5');
        $Value->addClass('foo');
        $Value->addClass('bar');

        $this->assertSame(
            '<div class="boxed-value-container foo bar"><div class="boxed-value">5</div></div>',
            $Value->getCode()
        );
    }

    public function testGetWrappedValuesSurroundsGivenHtmlInContainerDiv(): void
    {
        $this->assertSame(
            '<div class="boxed-values">XYZ</div>',
            BoxedValue::getWrappedValues('XYZ')
        );
    }

    public function testDisplayEchoesGetCode(): void
    {
        $Value = new BoxedValue('7');

        $this->expectOutputString($Value->getCode());
        $Value->display();
    }
}
