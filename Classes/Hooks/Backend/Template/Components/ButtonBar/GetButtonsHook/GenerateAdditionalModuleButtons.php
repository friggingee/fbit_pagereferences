<?php

namespace FBIT\PageReferences\Hooks\Backend\Template\Components\ButtonBar\GetButtonsHook;

use FBIT\PageReferences\Domain\Model\ReferencePage;
use FBIT\PageReferences\Utility\ReferencesUtility;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class GenerateAdditionalModuleButtons
{
    /** @var IconFactory $iconFactory */
    protected $iconFactory;

    public function getButtons(array $params, ButtonBar $buttonBar)
    {
        $buttons = $params['buttons'];
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        $this->generateButtons($buttonBar, $buttons);

        return $buttons;
    }

    public function generateButtons(ButtonBar $buttonBar, &$buttons)
    {
        switch (GeneralUtility::_GET('route')) {
            case '/web/layout/':
            case '/web/list/':
                $currentPage = BackendUtility::getRecord('pages', GeneralUtility::_GET('id'));

                // only show the buttons on pages referencing another page
                if (
                    (
                        $currentPage['content_from_pid'] > 0
                        && $currentPage['doktype'] === ReferencePage::DOKTYPE
                    )
                    || $currentPage['tx_fbit_pagereferences_reference_source_page'] > 0
                ) {
                    $this->generateCreateContentReferencesButton($buttonBar, $buttons);
                    $this->generateConvertReferencesToCopiesButton($buttonBar, $buttons);
                }
                break;
            case '/record/edit':
                $requestParameters = GeneralUtility::_GET();
                if (is_array($requestParameters['edit']) && array_key_first($requestParameters['edit']) === 'pages') {
                    $currentPage = BackendUtility::getRecord('pages', array_key_first(GeneralUtility::_GET('edit')['pages']));

                    $originalLanguagePageData = $currentPage;

                    if (
                        $currentPage['l10n_parent'] > 0
                        && $currentPage['sys_language_uid'] > 0
                    ) {
                        $originalLanguagePageData = BackendUtility::getRecord('pages', $currentPage['l10n_parent']);
                    }

                    $showGoToReferenceSourcePageButton = $originalLanguagePageData['content_from_pid'] > 0;
                    $showGoToReferenceSourcePageButton = $showGoToReferenceSourcePageButton ?: $originalLanguagePageData['tx_fbit_pagereferences_reference_source_page'] > 0;

                    // only show the buttons on pages with a connection to a reference source page
                    if ($showGoToReferenceSourcePageButton) {
                        $this->generateGoToReferenceSourcePageButton($buttonBar, $buttons);
                    }

                    if ($currentPage['doktype'] !== ReferencePage::DOKTYPE) {
                        $sourcePageUid = $currentPage['uid'];

                        if ($currentPage['l10n_parent'] > 0 && $currentPage['sys_language_uid'] > 0) {
                            $sourcePageUid = $currentPage['l10n_parent'];
                        }

                        if (ReferencesUtility::hasReferences((int)$sourcePageUid, (int)$currentPage['sys_language_uid'])) {
//                            $this->generateSaveAndUpdateReferencesButton($buttonBar, $buttons);
                        }
                    }
                }
                break;
        }
    }

    protected function generateCreateContentReferencesButton(ButtonBar $buttonBar, &$buttons)
    {
        $buttonConfig = [];
        $buttonConfig['title'] = 'Create content references';
        $buttonConfig['icon'] = $this->iconFactory->getIcon(
            'actions-link',
            Icon::SIZE_SMALL
        );

        $buttons[ButtonBar::BUTTON_POSITION_LEFT][120][] = $this->generateButton($buttonConfig, $buttonBar);
    }

    protected function generateButton(array $buttonConfig, ButtonBar $buttonBar)
    {
        $title = $buttonConfig['title'];
        $class = 'fbitpagereferences-' . str_replace(' ', '', strtolower($title));

        $button = $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setDataAttributes([
                'toggle' => 'tooltip',
                'placement' => 'bottom',
                'title' => $title
            ])
            ->setTitle($title)
            ->setIcon($buttonConfig['icon'])
            ->setClasses($class);

        if ($buttonConfig['showLabelText']) {
            $button->setShowLabelText($buttonConfig['showLabelText']);
        }

        return $button;
    }

    protected function generateConvertReferencesToCopiesButton(ButtonBar $buttonBar, &$buttons)
    {
        $buttonConfig = [];
        $buttonConfig['title'] = 'Convert references to copies';
        $buttonConfig['icon'] = $this->iconFactory->getIcon(
            'actions-edit-cut',
            Icon::SIZE_SMALL
        );

        $buttons[ButtonBar::BUTTON_POSITION_LEFT][120][] = $this->generateButton($buttonConfig, $buttonBar);
    }

    protected function generateGoToReferenceSourcePageButton(ButtonBar $buttonBar, &$buttons)
    {
        $buttonConfig = [];
        $buttonConfig['title'] = 'Go to reference source page';
        $buttonConfig['icon'] = $this->iconFactory->getIcon(
            'actions-file',
            Icon::SIZE_SMALL,
            'actions-view-paging-previous'
        );
        $buttonConfig['showLabelText'] = true;

        $buttons[ButtonBar::BUTTON_POSITION_LEFT][120][] = $this->generateButton($buttonConfig, $buttonBar);
    }

    protected function generateSaveAndUpdateReferencesButton(ButtonBar $buttonBar, &$buttons)
    {
        $buttonConfig = [];
        $buttonConfig['title'] = 'Save and update references';
        $buttonConfig['icon'] = $this->iconFactory->getIcon(
            'actions-save',
            Icon::SIZE_SMALL,
            'actions-system-refresh'
        );

        $buttons[ButtonBar::BUTTON_POSITION_LEFT][2][] = $buttonBar->makeInputButton()
            ->setForm('EditDocumentController')
            ->setTitle($buttonConfig['title'])
            ->setShowLabelText(true)
            ->setIcon($buttonConfig['icon'])
            ->setClasses('fbitpagereferences-saveandupdatereferences')
            ->setName('_savedokandupdatereferences')
            ->setValue(1)
            ->setDataAttributes([
                'toggle' => 'tooltip',
                'placement' => 'bottom',
                'title' => $buttonConfig['title']
            ]);
    }
}
