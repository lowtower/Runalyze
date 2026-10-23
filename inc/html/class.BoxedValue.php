<?php

declare(strict_types=1);

/**
 * This file contains class::BoxedValue
 * @package Runalyze\HTML
 */
/**
 * @author Hannes Christiansen
 * @package Runalyze\HTML
 */
class BoxedValue
{
    public static string $SURROUNDING_DIV = 'boxed-values';

    public static string $CONTAINER_FLOATING_OUTER_DIV = 'boxed-value-outer';

    public static string $CONTAINER_DIV = 'boxed-value-container';

    public static string $CONTAINER_DIV_WITH_ICON = 'boxed-value-container with-icon';

    public static string $VALUE_DIV = 'boxed-value';

    public static string $VALUE_UNIT_DIV = 'boxed-value-unit';

    /**
     * @var string
     */
    public static $VALUE_INFO_DIV = 'boxed-value-info';

    protected string $Value;

    protected string $Icon;

    protected string $Unit;

    protected string $Info;

    protected string $Glossary;

    protected string $AdditionalClasses = '';

    protected bool $AsFloatingBlock = false;

    /**
     * e.g. "w10", "w25", "w33", "w50"
     */
    protected string $AsFloatingBlockClass = '';

    protected string $AsFloatingBlockWidth = '';

    public function __construct(string $Value = '', string $Unit = '', string $Info = '', string $Icon = '', string $Glossary = '')
    {
        $this->setValue($Value);
        $this->setUnit($Unit);
        $this->setInfo($Info);
        $this->setIcon($Icon);
        $this->setGlossary($Glossary);
    }

    public function setValue(string $Value): void
    {
        $this->Value = $Value;
    }

    public function setUnit(string $Unit): void
    {
        $this->Unit = $Unit;
    }

    public function setIcon(string $Icon): void
    {
        $this->Icon = $Icon;
    }

    public function setInfo(string $Info): void
    {
        $this->Info = $Info;
    }

    public function setGlossary(string $Glossary): void
    {
        $this->Glossary = $Glossary;
    }

    public function addClass(string $Class): void
    {
        $this->AdditionalClasses .= ' ' . $Class;
    }

    /**
     * Multiple classes can be set, e.g. "w100 flexible-height".
     *
     * @param string $widthClass e.g. "w25", "w33", "w50"
     */
    public function defineAsFloatingBlock(string $widthClass): void
    {
        $this->AsFloatingBlock = true;
        $this->AsFloatingBlockClass = $widthClass;
    }

    public function defineAsFloatingBlockWithFixedWidth(int $numberOfBlocks): void
    {
        $this->AsFloatingBlock = true;
        $this->AsFloatingBlockWidth = round(100 / $numberOfBlocks, 2) . '%';
    }

    public function display(): void
    {
        echo $this->getCode();
    }

    public function getCode(): string
    {
        $Code = '';

        if ($this->AsFloatingBlock) {
            if ($this->AsFloatingBlockWidth) {
                $Code .= '<div class="' . self::$CONTAINER_FLOATING_OUTER_DIV . '" style="width:' . $this->AsFloatingBlockWidth . ';">';
            } else {
                $Code .= '<div class="' . self::$CONTAINER_FLOATING_OUTER_DIV . ' ' . $this->AsFloatingBlockClass . '">';
            }
        }

        $Code .= '<div class="' . $this->getDivClass() . '">';
        $Code .= $this->getDivForIcon();
        $Code .= $this->getDivForValue();
        $Code .= $this->getDivForInfo();
        $Code .= '</div>';

        if ($this->AsFloatingBlock) {
            $Code .= '</div>';
        }

        return $Code;
    }

    protected function getDivClass(): string
    {
        $Class = empty($this->Icon) ? self::$CONTAINER_DIV : self::$CONTAINER_DIV_WITH_ICON;

        return $Class . $this->AdditionalClasses;
    }

    protected function getDivForIcon(): string
    {
        return $this->Icon;
    }

    protected function getDivForValue(): string
    {
        $Code = '<div class="' . self::$VALUE_DIV . '">';
        $Code .= $this->Value;
        $Code .= $this->getDivForUnit();

        return $Code . '</div>';
    }

    protected function getDivForUnit(): string
    {
        if (empty($this->Unit)) {
            return '';
        }

        $Code = ' <div class="' . self::$VALUE_UNIT_DIV . '">';
        $Code .= str_replace(' ', '&nbsp;', $this->Unit);

        return $Code . '</div>';
    }

    protected function getDivForInfo(): string
    {
        if (empty($this->Info)) {
            return '';
        }

        $Code = '<div class="' . self::$VALUE_INFO_DIV . '">';
        if (! empty($this->Glossary)) {
            $Code .= '<a class="window left" href="glossary/' . $this->Glossary . '"><i class="fa far fa-question-circle"></i></a>';
        }

        $Code .= str_replace(' ', '&nbsp;', $this->Info);

        return $Code . '</div>';
    }

    public static function wrapValues(string $ValuesString): void
    {
        echo self::getWrappedValues($ValuesString);
    }

    public static function getWrappedValues(string $ValuesString): string
    {
        return '<div class="' . self::$SURROUNDING_DIV . '">' . $ValuesString . '</div>';
    }
}
