<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/packages/core/src')
    ->in(__DIR__.'/packages/kernel/src')
    ->in(__DIR__.'/packages/entity/src')
    ->in(__DIR__.'/packages/doctrine/src')
    ->in(__DIR__.'/packages/api/src')
    ->in(__DIR__.'/packages/security/src')
    ->in(__DIR__.'/packages/security-mfa/src')
    ->in(__DIR__.'/packages/security-passkey/src')
    ->in(__DIR__.'/packages/user/src')
    ->in(__DIR__.'/apps/test-app/src')
    ->in(__DIR__.'/tests');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'declare_strict_types' => true,
        'ordered_imports' => true,
    ])
    ->setFinder($finder);
