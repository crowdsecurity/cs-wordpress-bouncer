<?php

if (!file_exists(__DIR__ . '/../../../src')) {
    exit(1);
}

$config = new PhpCsFixer\Config("crowdsec-capi-php-client");
return $config
    ->setRules([
        '@Symfony' => true,
        '@PSR12:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'fopen_flags' => false,
        'protected_to_private' => false,
        'native_constant_invocation' => true,
        'combine_nested_dirname' => true,
        'phpdoc_to_comment' => false,
        'concat_space' => ['spacing'=> 'one'],
        'types_spaces' => [ 'space_multiple_catch' => 'single']
    ])
    ->setRiskyAllowed(true)
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__ . '/../../../src')
            ->in(__DIR__ . '/../../../tests')
    )
;