<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Set\ValueObject\LevelSetList;

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
        LevelSetList::UP_TO_PHP_80,
    ])
    ->withTypeCoverageLevel(75)         # max level 75
    ->withTypeCoverageDocblockLevel(15) # max level 15
    ->withDeadCodeLevel(74)             # max level 74
    ->withCodeQualityLevel(79)          # max level 79
    ->withCodingStyleLevel(21)          # max level 21
    ->withRules([])
    ->withSkip([
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
