<?php

declare(strict_types=1);

namespace Runalyze\Parameter\Application;

class PaceAxisType extends \Runalyze\Parameter\Select
{
    public const LINEAR = 'LINEAR';

    public const REVERSE = 'REVERSE';

    public const AS_SPEED = 'AS_SPEED';

    public function __construct()
    {
        parent::__construct(self::AS_SPEED, [
            'options' => [
                self::LINEAR => __('linear'),
                self::REVERSE => __('reversed linear'),
                self::AS_SPEED => __('as speed'),
            ],
        ]);
    }
}
