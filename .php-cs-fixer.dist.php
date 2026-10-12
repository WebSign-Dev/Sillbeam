<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
    ->in(__DIR__.'/apps/hybrid')
    ->in(__DIR__.'/apps/modules-only')
    ->in(__DIR__.'/apps/multi-project')
    ->in(__DIR__.'/apps/project-only')
    ->in(__DIR__.'/apps/single-project')
    ->in(__DIR__.'/apps/symfony-only')
    ->append([__DIR__.'/tools/test-consumer.php', __DIR__.'/tools/consumer-smoke.php', __DIR__.'/tools/prepare-compatibility.php', __DIR__.'/tools/test-flex-recipe.php', __DIR__.'/tools/benchmark-architecture.php', __DIR__.'/tools/test-release-package.php'])
    ->exclude('Fixtures/Application/var')
    ->notPath([
        'config/reference.php',
        'Fixtures/Application/config/reference.php',
    ]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'declare_strict_types' => true,
        'ordered_imports' => true,
    ])
    ->setFinder($finder);
