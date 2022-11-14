<?php

call_user_func(
    function ($extKey, $table) {
        $dokType = \FBIT\PageReferences\Domain\Model\ReferencePage::DOKTYPE;

        // Add new page type:
        $GLOBALS['PAGES_TYPES'][$dokType] = [
            'type' => 'web',
            'allowedTables' => '*',
        ];

        // Add the new doktype to the list of types available from the new page menu at the top of the page tree
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
            'options.pageTree.doktypesToShowInNewPageDragArea := addToList(' . $dokType . ')'
        );

        // Add new page type as possible select item:
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
            $table,
            'doktype',
            [
                'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_tca.xlf:pages.doktype.pagereference',
                $dokType,
                'EXT:' . $extKey . '/Resources/Public/Icons/apps-pagetree-page-reference.svg'
            ],
            '1',
            'after'
        );

        \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
            $GLOBALS['TCA'][$table],
            [
                // add icon for new page type:
                'ctrl' => [
                    'typeicon_classes' => [
                        $dokType => 'apps-pagetree-page-reference',
                    ],
                ],
                // add all page standard fields and tabs to the new page type
                'types' => [
                    (string)$dokType => [
                        'showitem' => $GLOBALS['TCA'][$table]['types'][\TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_DEFAULT]['showitem']
                    ]
                ]
            ]
        );

        // define new fields
        $tempColumns = [
            'tx_fbit_pagereferences_reference_source_page' => [
                'label' => 'LLL:EXT:fbit_pagereferences/Resources/Private/Language/locallang_tca.xlf:pages.tx_fbit_pagereferences_reference_source_page',
                'config' => [
                    'type' => 'input',
                ]
            ],
            'tx_fbit_pagereferences_reference_page_properties' => [
                'label' => 'LLL:EXT:fbit_pagereferences/Resources/Private/Language/locallang_tca.xlf:pages.tx_fbit_pagereferences_reference_page_properties',
                'config' => [
                    'type' => 'check',
                    'default' => '0'
                ],
                'onChange' => 'reload',
                'displayCond' => 'FIELD:content_from_pid:REQ:true'
            ],
            'tx_fbit_pagereferences_original_page_properties' => [
                'config' => [
                    'type' => 'passthrough',
                    'default' => ''
                ]
            ],
            'tx_fbit_pagereferences_rewrite_links' => [
                'exclude' => 1,
                'label' => 'LLL:EXT:fbit_pagereferences/Resources/Private/Language/locallang_tca.xlf:pages.tx_fbit_pagereferences_rewrite_links',
                'config' => [
                    'type' => 'check',
                    'default' => '0'
                ]
            ],
        ];

        // add new fields
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $tempColumns);

        // set up field display
        $typelist = implode(',', [$dokType]);

        // first, remove otherwise duplicated "content_from_pid" palette
        $GLOBALS['TCA']['pages']['types'][$dokType]['showitem'] = str_replace(
            '--palette--;;replace,',
            '',
            $GLOBALS['TCA']['pages']['types'][$dokType]['showitem']
        );

        $GLOBALS['TCA']['pages']['types'][$dokType]['columnsOverrides'] = [
            'content_from_pid' => [
                'config' => [
                    'minitems' => 1,
                    'maxitems' => 1,
                ],
            ],
        ];

        // then add new fields to form
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'pages',
            implode(',',
                [
                    '--div--;LLL:EXT:fbit_pagereferences/Resources/Private/Language/locallang_tca.xlf:pages.references',
                    'content_from_pid',
                    'tx_fbit_pagereferences_reference_page_properties',
                    'tx_fbit_pagereferences_rewrite_links',
                ]
            ),
            $typelist,
            'after:subtitle'
        );

        // also show on default pages
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'pages',
            implode(',',
                [
                    '--div--;LLL:EXT:fbit_pagereferences/Resources/Private/Language/locallang_tca.xlf:pages.references',
                    'tx_fbit_pagereferences_rewrite_links',
                    'tx_fbit_pagereferences_reference_source_page',
                ]
            ),
            (string)\TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_DEFAULT,
            'after:subtitle'
        );

        // allow to change the icon if the page is a proper reference
        $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['userFunc'] = \FBIT\PageReferences\UserFuncs\TCA\Pages\Ctrl\TypeiconClasses\UserFunc\ReferencedPage::class . '->overrideIconIfPageIsFullReference';
    },
    'fbit_pagereferences',
    'pages'
);
