<?php

/**
 * This file contains class::Kernels
 * @package Runalyze\Calculation\Math\MovingAverage\Kernel
 */

namespace Runalyze\Calculation\Math\MovingAverage\Kernel;

use Runalyze\Common\Enum\AbstractEnum;

/**
 * Enum for available kernels
 *
 * @author Hannes Christiansen
 * @package Runalyze\Calculation\Math\MovingAverage\Kernel
 */
final class Kernels extends AbstractEnum
{
    /**
     * @var int
     */
    public const UNIFORM = 1;

    /**
     * @var int
     */
    public const TRIANGULAR = 2;

    /**
     * @var int
     */
    public const EPANECHNIKOV = 3;

    /**
     * @var int
     */
    public const QUARTIC = 4;

    /**
     * @var int
     */
    public const TRIWEIGHT = 5;

    /**
     * @var int
     */
    public const TRICUBE = 6;

    /**
     * @var int
     */
    public const GAUSSIAN = 7;

    /**
     * @var int
     */
    public const COSINE = 8;

    /**
     * @var int
     */
    public const LOGISTIC = 9;

    /**
     * Get exporter
     * @param int $kernelid int from internal enum
     * @throws \InvalidArgumentException
     */
    public static function get(int $kernelid, float $width): AbstractKernel
    {
        $classNames = self::classNamesArray();

        if (! isset($classNames[$kernelid])) {
            throw new \InvalidArgumentException('Invalid kernel id "' . $kernelid . '".');
        }

        $className = 'Runalyze\\Calculation\\Math\\MovingAverage\\Kernel\\' . $classNames[$kernelid];

        return new $className($width);
    }

    /**
     * Get array with class names
     * @return array<int, string>
     */
    private static function classNamesArray(): array
    {
        return [
            self::UNIFORM => 'Uniform',
            self::TRIANGULAR => 'Triangular',
            self::EPANECHNIKOV => 'Epanechnikov',
            self::QUARTIC => 'Quartic',
            self::TRIWEIGHT => 'Triweight',
            self::TRICUBE => 'Tricube',
            self::GAUSSIAN => 'Gaussian',
            self::COSINE => 'Cosine',
            self::LOGISTIC => 'Logistic',
        ];
    }
}
