<?php

call_user_func(
    function ($extKey, $table) {
        $dokType = \FBIT\PageReferences\Domain\Model\ReferencePage::DOKTYPE;

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
                        'showitem' => $GLOBALS['TCA'][$table]['types'][\TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_DEFAULT]['showitem']
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
                    'readOnly' => true,
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
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', '--div--;LLL:EXT:fbit_pagereferences/Resources/Private/Language/locallang_tca.xlf:pages.references', $typelist, 'after:subtitle');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'content_from_pid', $typelist, 'after:--div--;LLL:EXT:fbit_pagereferences/Resources/Private/Language/locallang_tca.xlf:pages.references');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_fbit_pagereferences_reference_page_properties', $typelist, 'after:content_from_pid');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_fbit_pagereferences_rewrite_links', $typelist, 'after:tx_fbit_pagereferences_reference_page_properties');
        // also show on default pages
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_fbit_pagereferences_rewrite_links', '1', 'after:subtitle');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_fbit_pagereferences_reference_source_page', '1', 'after:tx_fbit_pagereferences_rewrite_links');

        // allow to change the icon if the page is a proper reference
        $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['userFunc'] = \FBIT\PageReferences\UserFuncs\TCA\Pages\Ctrl\TypeiconClasses\UserFunc\ReferencedPage::class . '->overrideIconIfPageIsFullReference';

        foreach ($GLOBALS['TCA']['pages']['columns'] as $fieldName => $fieldConfig) {
            if (!in_array($fieldName, \FBIT\PageReferences\Domain\Model\ReferencePage::PROTECTED_PROPERTIES)) {
                // lock referenced fields for editing if "Reference page properties" is set
                $GLOBALS['TCA']['pages']['types'][$dokType]['columnsOverrides'][$fieldName]['displayCond'] =
                    'FIELD:tx_fbit_pagereferences_reference_page_properties:=:0';
            }
        }
    },
    'fbit_pagereferences',
    'pages'
);
