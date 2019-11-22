<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function ($extKey) {
        // Provide icon for page tree, list view, ... :
        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class)
            ->registerIcon(
                'apps-pagetree-page-reference',
                TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
                [
                    'source' => 'EXT:' . $extKey . '/Resources/Public/Icons/apps-pagetree-page-reference.svg',
                ]
            );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('FBIT.' . $extKey, 'Configuration/TypoScript', 'Page References Management');
    },
    'fbit_pagereferences'
);
