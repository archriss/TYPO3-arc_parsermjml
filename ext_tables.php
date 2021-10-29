<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'Archriss.ArcParsermjml',
    'file',
    'mjml', // Submodule key
    '', // Position
    [
        'Mjml' => 'configure, convert',
    ],
    [
        'access' => 'user,group',
        'icon'   => 'EXT:arc_parsermjml/Resources/Public/Icons/Extension.svg',
        'labels' => 'LLL:EXT:arc_parsermjml/Resources/Private/Language/locallang_module.xlf',
    ]
);
