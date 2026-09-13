<?php

declare(strict_types=1);

// Placeholder values for PHPStan static analysis only.
// The real values are defined at runtime in Frontend::initConfig()
// from Symfony's ParameterBagInterface (RUNALYZE_VERSION / RUNALYZE_DATE
// parameters), which PHPStan cannot trace statically.
if (! defined('RUNALYZE_VERSION')) {
    define('RUNALYZE_VERSION', '0.0.0');
}
if (! defined('RUNALYZE_DATE')) {
    define('RUNALYZE_DATE', '1970-01-01');
}
