<?php

namespace Runalyze\Sports\ClimbCategory;

class ClimbCategory implements \Stringable
{
    public const int CATEGORY_HC = 0;

    public const int CATEGORY_1 = 1;

    public const int CATEGORY_2 = 2;

    public const int CATEGORY_3 = 3;

    public const int CATEGORY_4 = 4;

    public const int CATEGORY_5 = 5;

    public const int CATEGORY_NONE = 6;

    /**
     * @param int $Category
     */
    public function __construct(
        public $Category = self::CATEGORY_NONE
    ) {}

    /**
     * @param int $category
     */
    public function setCategory($category): self
    {
        if (! in_array($category, self::getOptions())) {
            throw new \InvalidArgumentException(sprintf('Invalid climb category "%s".', (string) $category));
        }

        $this->Category = $category;

        return $this;
    }

    public function isClassified(): bool
    {
        return $this->Category != self::CATEGORY_NONE;
    }

    public function getString(): string
    {
        $strings = ['hc', '1', '2', '3', '4', '5', '-'];

        return $strings[$this->Category];
    }

    public function __toString(): string
    {
        return $this->getString();
    }

    /**
     * @return int[]
     */
    public static function getOptions(): array
    {
        return [
            self::CATEGORY_HC,
            self::CATEGORY_1,
            self::CATEGORY_2,
            self::CATEGORY_3,
            self::CATEGORY_4,
            self::CATEGORY_5,
            self::CATEGORY_NONE,
        ];
    }

    /**
     * @param int|float $score
     * @param int[]|float[] $lowerLimits lower limits for as many categories as supported (see static::getOptions)
     */
    public static function getCategoryFor($score, array $lowerLimits): self
    {
        $categoryIndex = self::CATEGORY_NONE;

        foreach ($lowerLimits as $i => $limit) {
            if ($score >= $limit && $i < self::CATEGORY_NONE) {
                $categoryIndex = $i;

                break;
            }
        }

        return new self($categoryIndex);
    }
}
