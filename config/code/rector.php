<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Catch_\ThrowWithPreviousExceptionRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Privatization\Rector\Class_\FinalizeClassesWithoutChildrenRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonySetList;

return static function (RectorConfig $rectorConfig): void {
    $dir = __DIR__.'/../../';
    $rectorConfig->paths([
        $dir.'/config',
        $dir.'/public',
        $dir.'/src',
        $dir.'/tests',
    ]);

    $rectorConfig->skip([
        // Overrides are not needed
        AddOverrideAttributeToOverriddenMethodsRector::class,

        // catch should be like this (SomeException $e)
        CatchExceptionNameMatchingTypeRector::class,

        // and without changing catch throw argument(adding code)
        ThrowWithPreviousExceptionRector::class,

        // Creates problem, im too lazy to describe it
        RemoveUnusedVariableAssignRector::class,
    ]);

    $rectorConfig->symfonyContainerXml(
        $dir.'/var/cache/dev/App_KernelDevDebugContainer.xml'
    );
    $rectorConfig->phpstanConfig($dir.'/config/code/phpstan.neon');
    $rectorConfig->importShortClasses();
    $rectorConfig->importNames();
    $rectorConfig->removeUnusedImports();

    // register rules
    $rectorConfig->rules([
        FinalizeClassesWithoutChildrenRector::class,
        ReadOnlyPropertyRector::class,
        ReadOnlyClassRector::class,
    ]);

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_83,

        SymfonySetList::SYMFONY_63,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        SymfonySetList::CONFIGS,

        SetList::DEAD_CODE,
        SetList::CODE_QUALITY,
        SetList::TYPE_DECLARATION,
        SetList::CODING_STYLE,
        SetList::EARLY_RETURN,
        SetList::INSTANCEOF,

        // Unneeded code
        //SetList::PRIVATIZATION,

        // Change variables names: Dangerous!
        //SetList::NAMING,

        // Generates too many code in if cases
        //SetList::STRICT_BOOLEANS,
    ]);
};
