<?php
$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
;
return (new PhpCsFixer\Config())
    ->setUsingCache(false)
    ->setRules([
        '@PSR2' => true,
        'array_syntax' => ['syntax' => 'short'],
        'concat_space' => ['spacing' => 'none'],
        'no_leading_import_slash' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'length'],
        'phpdoc_align' => true,
        'phpdoc_no_empty_return' => true,
        'psr_autoloading' => true,
        'return_type_declaration' => ['space_before' => 'none'],
        'single_blank_line_before_namespace' => true,
        'single_quote' => true,
        'ternary_operator_spaces' => true,
        'trailing_comma_in_multiline' => true,
        'trim_array_spaces' => true,
        'whitespace_after_comma_in_array' => true,
        'visibility_required' => true,
    ])
    ->setFinder($finder)
    ;

