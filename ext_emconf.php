<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Arc Parsermjml',
    'description' => 'A simple mjml extension. Backend module that help generate html template from mjml',
    'category' => 'be',
    'author' => '',
    'author_email' => '',
    'author_company' => 'Archriss',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'version' => '10.4.3',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-10.4.99',
            'mask' => '7.0.0-7.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
