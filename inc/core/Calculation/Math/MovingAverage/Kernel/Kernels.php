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
    public const int UNIFORM = 1;

    public const int TRIANGULAR = 2;

    public const int EPANECHNIKOV = 3;

    public const int QUARTIC = 4;

    public const int TRIWEIGHT = 5;

    public const int TRICUBE = 6;

    public const int GAUSSIAN = 7;

    public const int COSINE = 8;

    public const int LOGISTIC = 9;

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
