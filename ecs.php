<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

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
    //->withSets([
    //      \Symplify\EasyCodingStandard\ValueObject\Set\SetList::SPACES,
    //      \Symplify\EasyCodingStandard\ValueObject\Set\SetList::NAMESPACES,
    //      \Symplify\EasyCodingStandard\ValueObject\Set\SetList::DOCBLOCKS,
    //      \Symplify\EasyCodingStandard\ValueObject\Set\SetList::ARRAYS,
    //      \Symplify\EasyCodingStandard\ValueObject\Set\SetList::COMMENTS,
    //])

    // ...but first: take it step by step
    ->withSpacesLevel(8)
    ->withArrayLevel(8)
    ->withControlStructuresLevel(8)
    ->withDocblockLevel(8)
;
