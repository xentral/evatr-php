<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'trailing_comma_in_multiline' => true,
    ])
    ->setFinder($finder);
