<?php

namespace FBIT\PageReferences\Hooks\Backend\Controller\PageLayoutController\DrawHeaderHook;

use FBIT\PageReferences\Utility\ReferencesUtility;
use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper;

class PagePropertiesReferencedFlashMessage
{
    /**
     * @param array $params
     * @param PageLayoutController $pageLayoutController
     * @throws \TYPO3\CMS\Core\Exception\SiteNotFoundException
     */
    public function addPagePropertiesReferencedFlashMessage(array &$params, PageLayoutController &$pageLayoutController)
    {
        $content = '';

        $pageRecord = BackendUtility::getRecord('pages', $pageLayoutController->id);

        // If a reference page also references the properties of the source page:
        if ((int)$pageRecord['tx_fbit_pagereferences_reference_page_properties'] === 1) {
            $view = GeneralUtility::makeInstance(StandaloneView::class);
            $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/InfoBox.html'));

            $view->assignMultiple([
                'title' => 'Page uses page properties from source page.',
                'message' => '(see also message above)',
                'state' => InfoboxViewHelper::STATE_INFO
            ]);

            $content .= $view->render();
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

                    if (count($messageBodyData) > 1) {
                        $messageBody .= ' // ';
                    }
                }

                $view = GeneralUtility::makeInstance(StandaloneView::class);
                $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/InfoBox.html'));

                $view->assignMultiple([
                    'title' => 'Page properties are also used on pages:',
                    'message' => $messageBody,
                    'state' => InfoboxViewHelper::STATE_INFO
                ]);

                $content .= $view->render();
            }
        }

        return $content;
    }
}
