<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoPackageFixer;
use Symplify\CodingStandard\Fixer\Commenting\RemoveDeadVarThisFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/dashboard.php',
        __DIR__ . '/bin',
        __DIR__ . '/config',
        __DIR__ . '/inc',
        __DIR__ . '/lib',
        __DIR__ . '/plugin',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    // include *.php files in the root directory
    ->withRootFiles()

    // add a single rule
    ->withRules([
        NoUnusedImportsFixer::class,
    ])

    ->withSets([
        SetList::PSR_12,
        SetList::CLEAN_CODE,
        SetList::ARRAY,
        SetList::COMMON,
        SetList::COMMENTS,
        SetList::CONTROL_STRUCTURES,
        SetList::DOCBLOCK,
        SetList::NAMESPACES,
        SetList::SPACES,
        SetList::DOCTRINE_ANNOTATIONS,
    ])

    ->withSkip([
        GeneralPhpdocAnnotationRemoveFixer::class,
        PhpdocNoPackageFixer::class,
        RemoveDeadVarThisFixer::class => [
            __DIR__ . '/inc/tpl/*.php',
            __DIR__ . '/plugin/RunalyzePluginStat_Trainingszeiten/Plot.Daytime.php',
            __DIR__ . '/plugin/RunalyzePluginStat_Trainingszeiten/Plot.Weekday.php',
            __DIR__ . '/plugin/RunalyzePluginStat_Wettkampf/Plot.Bestzeit.php',
        ],
    ])
;
