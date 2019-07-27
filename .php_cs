<?php

declare(strict_types=1);

$toYear = \date('Y');
$header = <<<HEADER
DoctrineAuditBundle
HEADER;

$rules = [
    '@Symfony'                                      => true,
    '@Symfony:risky'                                => true,
    'array_syntax'                                  => [
        'syntax' => 'short',
    ],
    'binary_operator_spaces'                        => [
        'default' => 'align',
    ],
    'combine_consecutive_issets'                    => true,
    'combine_consecutive_unsets'                    => true,
     'header_comment'                                => [
         'header' => $header,
     ],
    'no_extra_blank_lines'                          => true,
    'explicit_string_variable'                      => true,
    'no_php4_constructor'                           => true,
    'no_useless_else'                               => true,
    'no_useless_return'                             => true,
    'ordered_class_elements'                        => true,
    'ordered_imports'                               => true,
    'phpdoc_order'                                  => true,
    'phpdoc_types_order'                            => true,
    '@PHP56Migration'                               => true,
    '@PHP56Migration:risky'                         => true,
    '@PHPUnit57Migration:risky'                     => true,
    '@PHP70Migration'                               => true,
    '@PHP70Migration:risky'                         => true,
    '@PHPUnit60Migration:risky'                     => true,
    '@PHP71Migration'                               => true,
    '@PHP71Migration:risky'                         => true,
    'compact_nullable_typehint'                     => true,
    'strict_comparison'                             => true,
    'phpdoc_var_without_name'                       => false,
    'native_function_invocation'                    => true,
    'native_constant_invocation'                    => true,
    'no_superfluous_phpdoc_tags'                    => true,
    'fully_qualified_strict_types'                  => true,
    'linebreak_after_opening_tag'                   => true,
    'method_chaining_indentation'                   => true,
    'no_alternative_syntax'                         => true,
    'no_null_property_initialization'               => true,
    'phpdoc_trim_consecutive_blank_line_separation' => true,
    'phpdoc_add_missing_param_annotation'           => true,
    'no_short_echo_tag'                             => true,
    'multiline_whitespace_before_semicolons'        => [
        'strategy' => 'new_line_for_chained_calls',
    ],
    'array_indentation'                             => true,
];

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules($rules)
;
