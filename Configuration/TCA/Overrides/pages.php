<?php

call_user_func(
    function ($extKey, $table) {
        $tempColumns = [
            'tx_fbit_pagereferences_stop_mountpoint_pagetree' => [
                'exclude' => 1,
                'label' => 'LLL:EXT:fbit_pagereferences/Resources/Private/Language/locallang_tca.xlf:pages.tx_fbit_pagereferences_stop_mountpoint_pagetree',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectTree',
                    'foreign_table' => 'pages',
                    'foreign_table_where' => 'pages.pid IN (###REC_FIELD_mount_pid###) AND pages.sys_language_uid IN (###REC_FIELD_sys_language_uid###) ORDER BY pages.sorting',
                    'size' => 20,
                    'treeConfig' => [
                        'dataProvider' => \FBIT\PageReferences\Overrides\TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider::class,
                        'parentField' => 'pid',
                        'rootUid' => '###REC_FIELD_mount_pid###',
                        'appearance' => [
                            'expandAll' => true,
                            'showHeader' => true,
                        ],
                    ],
                ],
            ],
            'tx_fbit_pagereferences_rewrite_links' => [
                'exclude' => 1,
                'label' => 'LLL:EXT:fbit_pagereferences/Resources/Private/Language/locallang_tca.xlf:pages.tx_fbit_pagereferences_rewrite_links',
                'config' => [
                    'type' => 'check',
                    'default' => '0'
                ],
            ]
        ];

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $tempColumns);

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', '--div--;LLL:EXT:fbit_pagereferences/Resources/Private/Language/locallang_tca.xlf:pages.references', \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT, 'after:subtitle');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_fbit_pagereferences_stop_mountpoint_pagetree', \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT, 'after:mount_pid');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_fbit_pagereferences_rewrite_links', \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT, 'after:tx_fbit_pagereferences_stop_mountpoint_pagetree');

    },
    'fbit_pagereferences',
    'pages'
);
