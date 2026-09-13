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
