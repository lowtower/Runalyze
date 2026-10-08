<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\PHPUnit\PHPUnit120\Rector\CallLike\CreateStubOverCreateMockArgRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Renaming\Rector\String_\RenameStringRector;
use Rector\Set\ValueObject\LevelSetList;

return RectorConfig::configure()
    ->withPaths([
        # __DIR__ . '/dashboard.php',
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
        LevelSetList::UP_TO_PHP_84,
        # PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        # DoctrineSetList::TYPED_COLLECTIONS,
    ])
    ->withTypeCoverageLevel(75)         # max level 75
    ->withTypeCoverageDocblockLevel(16) # max level 16
    ->withDeadCodeLevel(74)             # max level 74
    ->withCodeQualityLevel(80)          # max level 80
    ->withCodingStyleLevel(21)          # max level 21
    ->withRules([])
    ->withSkip([
        RenameStringRector::class => [
            __DIR__ . '/src/CoreBundle/EventListener/SwitchUserListener.php',
        ],
        CreateStubOverCreateMockArgRector::class => [
            __DIR__ . '/tests/CoreBundle/Validator/Constraints/ContainsDisposableMailAddressValidatorTest.php',
            __DIR__ . '/tests/CoreBundle/Validator/Constraints/IsValidTimezoneValidatorTest.php',
        ],
    ])
    ->withComposerBased(
        true,   // symfony
        true,   // doctrine
        true,   // phpunit
        true,   // twig
    )
    ->withAttributesSets()
;
