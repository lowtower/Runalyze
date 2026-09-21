<?php

declare(strict_types=1);

/**
 * This file contains class::ProgressBarSingle
 * @package Runalyze\HTML
 */
/**
 * Single progressbar (with HTML/CSS)
 *
 * @author Hannes Christiansen
 * @package Runalyze\HTML
 */
class ProgressBarSingle
{
    /**
     * HTML class: balance positive
     */
    private static string $CLASS_BALANCE_POSITIVE = 'balance-positive';

    /**
     * HTML class: balance negative
     */
    private static string $CLASS_BALANCE_NEGATIVE = 'balance-negative';

    /**
     * HTML class: color blue
     */
    public static string $COLOR_BLUE = 'colored-blue';

    /**
     * HTML class: color red
     */
    public static string $COLOR_RED = 'colored-red';

    /**
     * HTML class: color green
     */
    public static string $COLOR_GREEN = 'colored-green';

    /**
     * HTML class: color yellow
     */
    public static string $COLOR_YELLOW = 'colored-yellow';

    /**
     * HTML class: color orange
     */
    public static string $COLOR_ORANGE = 'colored-orange';

    /**
     * HTML class: color grey
     */
    public static string $COLOR_GREY = 'colored-grey';

    /**
     * HTML class: color light (grey)
     */
    public static string $COLOR_LIGHT = 'colored-light';

    /**
     * Width
     */
    protected int $Width;

    /**
     * Boolean flag: left balanced?
     */
    protected bool $LeftBalanced = false;

    /**
     * Boolean flag: right balanced?
     */
    protected bool $RightBalanced = false;

    /**
     * @param mixed $LeftOrRight Can be 'left' or 'right' to set balance
     */
    public function __construct(
        int $Width,
        protected string $Color,
        $LeftOrRight = false
    ) {
        $this->Width = max(0, min(100, $Width));

        if ($LeftOrRight == 'left') {
            $this->setLeftBalanced();
        }

        if ($LeftOrRight == 'right') {
            $this->setRightBalanced();
        }
    }

    public function setLeftBalanced(): void
    {
        $this->LeftBalanced = true;
    }

    public function setRightBalanced(): void
    {
        $this->RightBalanced = true;
    }

    public function getWidth(): int
    {
        return $this->Width;
    }

    public function getClasses(): string
    {
        $Class = $this->Color;

        if ($this->LeftBalanced) {
            $Class .= ' ' . self::$CLASS_BALANCE_NEGATIVE;
        }

        if ($this->RightBalanced) {
            $Class .= ' ' . self::$CLASS_BALANCE_POSITIVE;
        }

        return $Class;
    }
}
