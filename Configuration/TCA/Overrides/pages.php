<?php

call_user_func(
    function ($extKey, $table) {
        $tempColumns = [
            'tx_fbit_pagereferences_stop_mountpoint_pagetree' => [
                'label' => 'LLL:EXT:fbit_pagereferences/Resources/Private/Language/locallang_tca.xlf:pages.tx_fbit_pagereferences_stop_mountpoint_pagetree',
                'config' => [
                    'type' => 'check',
                    'default' => '0'
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
