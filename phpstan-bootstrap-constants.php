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

// Same reasoning as above: FRONTEND_PATH is defined at runtime in
// Frontend::initSystem() (__DIR__ . '/'), PREFIX in Frontend::initDatabase()
// (from the app.database_prefix parameter). Both are used throughout legacy
// procedural code (inc/, plugin/) but PHPStan cannot trace their dynamic
// definition inside Frontend's methods.
if (! defined('FRONTEND_PATH')) {
    define('FRONTEND_PATH', __DIR__ . '/inc/');
}
if (! defined('PREFIX')) {
    define('PREFIX', 'runalyze_');
}
