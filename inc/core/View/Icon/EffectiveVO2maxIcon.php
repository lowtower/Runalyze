<?php

namespace Runalyze\View\Icon;

use Runalyze\Configuration;

class EffectiveVO2maxIcon extends \Runalyze\View\Icon
{
    /**
     * @var string
     */
    public const BASE_CLASS = 'vo2max-icon small';

    /**
     * @var string
     */
    public const DIRECTION_UP = 'fa-arrow-up';

    /**
     * @var string
     */
    public const DIRECTION_UP_HALF = 'fa-arrow-up  fa-rotate-45';

    /**
     * @var string
     */
    public const DIRECTION_RIGHT = 'fa-arrow-right';

    /**
     * @var string
     */
    public const DIRECTION_DOWN_HALF = 'fa-arrow-right  fa-rotate-45';

    /**
     * @var string
     */
    public const DIRECTION_DOWN = 'fa-arrow-down';

    /**
     * @var bool
     */
    protected $IsTransparent = false;

    /**
     * @var string
     */
    protected $Direction = '';

    /**
     * @param float $value
     * @param float $currentShape
     */
    public function __construct($value = null, $currentShape = null)
    {
        parent::__construct(self::BASE_CLASS);

        if ($value !== null) {
            $this->setDirectionBasedOn($value, $currentShape);
            $this->setTooltipFor($value);
        }
    }

    /**
     * @param float $value
     */
    protected function setTooltipFor($value)
    {
        $this->setTooltip('VO<sub>2</sub>max: ' . number_format($value, 2));
    }

    /**
     * @param float $value
     * @param float $currentShape
     */
    protected function setDirectionBasedOn($value, $currentShape)
    {
        // TODO
        $currentShape ??= Configuration::Data()->vo2max();

        $diff = $value - $currentShape;

        if ($diff > 3.0) {
            $this->setUp();
        } elseif ($diff > 1.0) {
            $this->setUpHalf();
        } elseif ($diff > -1.0) {
            $this->setRight();
        } elseif ($diff > -3.0) {
            $this->setDownHalf();
        } else {
            $this->setDown();
        }
    }

    public function setTransparent(): void
    {
        $this->IsTransparent = true;
    }

    public function setUp(): void
    {
        $this->Direction = self::DIRECTION_UP;
    }

    public function setUpHalf(): void
    {
        $this->Direction = self::DIRECTION_UP_HALF;
    }

    public function setRight(): void
    {
        $this->Direction = self::DIRECTION_RIGHT;
    }

    public function setDownHalf(): void
    {
        $this->Direction = self::DIRECTION_DOWN_HALF;
    }

    public function setDown(): void
    {
        $this->Direction = self::DIRECTION_DOWN;
    }

    public function code(): string
    {
        $this->FontAwesomeName = self::BASE_CLASS . ' ' . $this->Direction;

        if ($this->IsTransparent) {
            $this->FontAwesomeName .= ' unimportant';
        }

        return parent::code();
    }
}
