<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\ScalarArgumentToExpectedParamTypeRector;
use Rector\PHPUnit\PHPUnit120\Rector\CallLike\CreateStubOverCreateMockArgRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Renaming\Rector\String_\RenameStringRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Symfony\CodeQuality\Rector\Class_\EventSubscriberToAttributeRector;
use Rector\Symfony\Configs\Rector\Closure\RemoveConstructorAutowireServiceRector;
use Rector\Symfony\Configs\Rector\Closure\ServiceSetStringNameToClassNameRector;
use Rector\Symfony\Configs\Rector\Closure\ServiceSettersToSettersAutodiscoveryRector;
use Rector\Symfony\Set\SymfonySetList;

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
    ->withRootFiles()
    ->withIndent(indentChar: ' ', indentSize: 4)
    ->withTreatClassesAsFinal()
    ->withSymfonyContainerXml(
        __DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.xml'
    )
    ->withSets([
        LevelSetList::UP_TO_PHP_85,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        # DoctrineSetList::TYPED_COLLECTIONS,
    ])
    ->withTypeCoverageLevel(67)         # max level 67
    ->withTypeCoverageDocblockLevel(17) # max level 17
    ->withDeadCodeLevel(72)             # max level 72
    ->withCodeQualityLevel(67)          # max level 67
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
        RemoveConstructorAutowireServiceRector::class => [
            __DIR__ . '/config/services.php',
        ],
        ServiceSettersToSettersAutodiscoveryRector::class => [
            __DIR__ . '/config/services.php',
        ],
        ScalarArgumentToExpectedParamTypeRector::class => [
            __DIR__ . '/tests/inc/core/Calculation/NightDetectorTest.php',
            __DIR__ . '/tests/inc/core/Util/LocalTimeTest.php',
            __DIR__ . '/tests/CoreBundle/Component/Tool/Anova/QueryGroup/EquipmentTypeQueryGroupTest.php',
        ],
        ServiceSetStringNameToClassNameRector::class => [
            __DIR__ . '/config/packages/framework.php',
            __DIR__ . '/config/packages/monolog.php',
            __DIR__ . '/config/services.php',
        ],
        EventSubscriberToAttributeRector::class => [
            __DIR__ . '/src/CoreBundle/EventListener/LocaleListener.php',
        ],
    ])
    ->withComposerBased(
        true,   // symfony
        true,   // doctrine
        true,   // phpunit
        true,   // twig
    )
    ->withAttributesSets()
    ->withSets([
        SymfonySetList::CONFIGS,
    ])
;
