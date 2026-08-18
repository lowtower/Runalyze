<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/inc',
        __DIR__ . '/lib',
        __DIR__ . '/plugin',
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/web',
    ])
    ->withSets([
        SetList::PHP_74,
    ])
    ->withTypeCoverageLevel(40)         # max level 72
    ->withTypeCoverageDocblockLevel(14) # max level 14
    ->withDeadCodeLevel(40)             # max level 73
    ->withCodeQualityLevel(40)          # max level 73
    ->withCodingStyleLevel(18)          # max level 18
;
