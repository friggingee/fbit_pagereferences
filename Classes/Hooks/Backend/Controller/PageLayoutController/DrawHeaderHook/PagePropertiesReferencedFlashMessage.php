<?php

namespace FBIT\PageReferences\Hooks\Backend\Controller\PageLayoutController\DrawHeaderHook;

use FBIT\PageReferences\Utility\ReferencesUtility;
use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PagePropertiesReferencedFlashMessage
{
    /**
     * @param array $params
     * @param PageLayoutController $pageLayoutController
     * @throws \TYPO3\CMS\Core\Exception\SiteNotFoundException
     */
    public function addPagePropertiesReferencedFlashMessage(array &$params, PageLayoutController &$pageLayoutController)
    {
        $pageRecord = BackendUtility::getRecord('pages', $pageLayoutController->id);

        // If a reference page also references the properties of the source page:
        if ((int)$pageRecord['tx_fbit_pagereferences_reference_page_properties'] === 1) {
            $pageLayoutController->moduleTemplate->addFlashMessage(
                '(see also message below)',
                'Page uses page properties from source page.',
                AbstractMessage::INFO
            );
        }

        // If properties of a regular page is referenced somewhere else:
        if (ReferencesUtility::hasReferences($pageLayoutController->id)) {
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);

            $propertiesUsedOn = [];

            foreach (ReferencesUtility::getReferences($pageLayoutController->id) as $referencePage) {
                $referencePageData = BackendUtility::getRecord('pages', $referencePage['uid']);

                if ($referencePageData['tx_fbit_pagereferences_reference_page_properties']) {
                    $rootPageId = $siteFinder->getSiteByPageId($referencePage['uid'])->getRootPageId();

                    $propertiesUsedOn[$rootPageId]['rootPageData'] = BackendUtility::getRecord(
                        'pages',
                        $rootPageId
                    );

                    $propertiesUsedOn[$rootPageId]['pages'][] = $referencePageData;
                }
            }

            if (!empty($propertiesUsedOn)) {
                $messageBody = '';

                foreach ($propertiesUsedOn as $rootPageId => $pages) {
                    $messageBody .= $pages['rootPageData']['title'] . ' [' . $pages['rootPageData']['uid'] . '] => ';

                    $messageBodyData = [];
                    foreach ($pages['pages'] as $page) {
                        $messageBodyData[] = $page['title'] . ' [' . $page['uid'] . ']';
                    }
                    $messageBody .= implode(', ', $messageBodyData);

                    $messageBody .= ' // ';
                }

                $pageLayoutController->moduleTemplate->addFlashMessage(
                    $messageBody,
                    'Page properties are also used on pages:',
                    AbstractMessage::INFO
                );
            }
        }
    }
}
