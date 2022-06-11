<?php

declare(strict_types=1);

return (new PhpCsFixer\Config())
    ->setRules([
        'native_function_invocation' => [
            'exclude' => [
                '_'
            ],
            'include' => ['@all'],
        ],
    ])
    ->setRiskyAllowed(true)
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__ . '/src')
            ->in(__DIR__ . '/tests')
            ->append([__FILE__])
    )
;
