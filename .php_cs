<?php

$header = <<<'EOF'
This file is part of websocket-connection.

@link     https://github.com/friendofhyperf/websocket-connection
@document https://github.com/friendofhyperf/websocket-connection/blob/main/README.md
@contact  huangdijia@gmail.com
@license  https://github.com/friendofhyperf/websocket-connection/blob/main/LICENSE
EOF;

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR2'                                  => true,
        '@Symfony'                               => true,
        '@DoctrineAnnotation'                    => true,
        '@PhpCsFixer'                            => true,
        'header_comment'                         => [
            'commentType' => 'PHPDoc',
            'header'      => $header,
            'separate'    => 'none',
            'location'    => 'after_declare_strict',
        ],
        'array_syntax'                           => [
            'syntax' => 'short',
        ],
        'list_syntax'                            => [
            'syntax' => 'short',
        ],
        'concat_space'                           => [
            'spacing' => 'one',
        ],
        'blank_line_before_statement'            => [
            'statements' => [
                'declare',
            ],
        ],
        'general_phpdoc_annotation_remove'       => [
            'annotations' => [
                'author',
            ],
        ],
        'ordered_imports'                        => [
            'imports_order'  => [
                'class', 'function', 'const',
            ],
            'sort_algorithm' => 'alpha',
        ],
        'single_line_comment_style'              => [
            'comment_types' => [
            ],
        ],
        'yoda_style'                             => [
            'always_move_variable' => false,
            'equal'                => false,
            'identical'            => false,
        ],
        'phpdoc_align'                           => [
            'align' => 'left',
        ],
        'multiline_whitespace_before_semicolons' => [
            'strategy' => 'no_multi_line',
        ],
        'constant_case'                          => [
            'case' => 'lower',
        ],
        'class_attributes_separation'            => true,
        'combine_consecutive_unsets'             => true,
        'declare_strict_types'                   => true,
        'linebreak_after_opening_tag'            => true,
        'lowercase_static_reference'             => true,
        'no_useless_else'                        => true,
        'no_unused_imports'                      => true,
        'not_operator_with_successor_space'      => true,
        'not_operator_with_space'                => false,
        'ordered_class_elements'                 => true,
        'php_unit_strict'                        => false,
        'phpdoc_separation'                      => false,
        'single_quote'                           => true,
        'standardize_not_equals'                 => true,
        'multiline_comment_opening_closing'      => true,
        // 'binary_operator_spaces'                 => ['default' => 'align_single_space'],
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->exclude('vendor')
            ->in(__DIR__)
    )
    ->setUsingCache(false);
