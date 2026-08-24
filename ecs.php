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
        __DIR__ . '/app',
        __DIR__ . '/inc',
        __DIR__ . '/lib',
        __DIR__ . '/plugin',
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/web',
    ])
    // include *.php files in the root directory
    ->withRootFiles()

    // add a single rule
    ->withRules([
        NoUnusedImportsFixer::class,
    ])

    // add sets - group of rules, from easiest to more complex ones
    // uncomment one, apply one, commit, PR, merge and repeat
    // ->withSets([
    //      \Symplify\EasyCodingStandard\ValueObject\Set\SetList::SPACES,
    //      \Symplify\EasyCodingStandard\ValueObject\Set\SetList::NAMESPACES,
    //      \Symplify\EasyCodingStandard\ValueObject\Set\SetList::DOCBLOCKS,
    //      \Symplify\EasyCodingStandard\ValueObject\Set\SetList::ARRAYS,
    //      \Symplify\EasyCodingStandard\ValueObject\Set\SetList::COMMENTS,
    // ])

    // ...but first: take it step by step
#    ->withSpacesLevel(22)               # max level 22
#    ->withArrayLevel(15)                # max level 15
#    ->withControlStructuresLevel(21)    # max level 21
#    ->withDocblockLevel(24)             # max level 24

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
        # SetList::DOCTRINE_ANNOTATIONS,
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
