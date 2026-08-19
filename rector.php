<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\StrictArrayParamDimFetchRector;

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
    ->withTypeCoverageLevel(72)         # max level 72
    ->withTypeCoverageDocblockLevel(14) # max level 14
    ->withDeadCodeLevel(73)             # max level 73
    ->withCodeQualityLevel(73)          # max level 73
    ->withCodingStyleLevel(18)          # max level 18
    ->withSkip([
        StrictArrayParamDimFetchRector::class => [
            __DIR__ . '/inc/system/class.PDOforRunalyze.php',
        ],
    ])
    ->withComposerBased(
        true,   // symfony
        true,   // doctrine
        true,   // phpunit
        true,   // twig
    )
;
