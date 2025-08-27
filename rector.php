<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;


return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/core',
        __DIR__ . '/public/module',
    ])
    // Enable these rules to manage 'use' statements
    ->withImportNames() // adds fully qualified class imports
    // Optional: Add specific sets if needed
    // ->withSets([LevelSetList::UP_TO_PHP_83])
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0)
    ->withIndent(indentSize: 2);
