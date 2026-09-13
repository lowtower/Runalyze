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
     * @var string
     */
    private static $CLASS_BALANCE_POSITIVE = 'balance-positive';

    /**
     * HTML class: balance negative
     * @var string
     */
    private static $CLASS_BALANCE_NEGATIVE = 'balance-negative';

    /**
     * HTML class: color blue
     * @var string
     */
    public static $COLOR_BLUE = 'colored-blue';

    /**
     * HTML class: color red
     * @var string
     */
    public static $COLOR_RED = 'colored-red';

    /**
     * HTML class: color green
     * @var string
     */
    public static $COLOR_GREEN = 'colored-green';

    /**
     * HTML class: color yellow
     * @var string
     */
    public static $COLOR_YELLOW = 'colored-yellow';

    /**
     * HTML class: color orange
     * @var string
     */
    public static $COLOR_ORANGE = 'colored-orange';

    /**
     * HTML class: color grey
     * @var string
     */
    public static $COLOR_GREY = 'colored-grey';

    /**
     * HTML class: color light (grey)
     * @var string
     */
    public static $COLOR_LIGHT = 'colored-light';

    /**
     * Width
     * @var int
     */
    protected $Width = 0;

    /**
     * Boolean flag: left balanced?
     * @var bool
     */
    protected $LeftBalanced = false;

    /**
     * Boolean flag: right balanced?
     * @var bool
     */
    protected $RightBalanced = false;

    /**
     * @param int $Width
     * @param string $Color
     * @param mixed $LeftOrRight [optional] Can be 'left' or 'right' to set balance
     */
    public function __construct(
        $Width, /**
     * Color
     */
        protected $Color,
        $LeftOrRight = false
    ) {
        $this->Width = max(0, min(100, (int) $Width));

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

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->Width;
    }

    /**
     * @return string
     */
    public function getClasses()
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
