<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\TypeDeclaration\Rector\ClassMethod\StrictArrayParamDimFetchRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/bin',
        __DIR__ . '/config',
        __DIR__ . '/inc',
        __DIR__ . '/lib',
        __DIR__ . '/plugin',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSets([
        LevelSetList::UP_TO_PHP_74,
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
        RenameClassRector::class => [
            __DIR__ . '/src/CoreBundle/EventListener/SwitchUserListener.php',
        ],
    ])
    ->withComposerBased(
        true,   // symfony
        true,   // doctrine
        true,   // phpunit
        true,   // twig
    )
;
