<?php

namespace FBIT\PageReferences\Hooks\Backend\Template\Components\ButtonBar;

use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

class GetButtonsHook
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

                // only show the button on mount points
                if (
                    $currentPage['doktype'] === PageRepository::DOKTYPE_MOUNTPOINT
                    && $currentPage['mount_pid'] > 0
                ) {
                    $this->generateCreateContentReferencesButton($buttonBar, $buttons);
                    $this->generateConvertReferencesToCopiesButton($buttonBar, $buttons);
                }
                break;
        }
    }

    protected function generateCreateContentReferencesButton(ButtonBar $buttonBar, &$buttons)
    {
        $createContentReferencesButtonTitle = 'Create Content References';
        $createContentReferencesButtonIcon = $this->iconFactory->getIcon(
            'actions-link',
            Icon::SIZE_SMALL
        );

        $createContentReferencesButton = $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setDataAttributes([
                'toggle' => 'tooltip',
                'placement' => 'bottom',
                'title' => $createContentReferencesButtonTitle
            ])
            ->setTitle($createContentReferencesButtonTitle)
            ->setIcon($createContentReferencesButtonIcon)
            ->setClasses('fbitpagereferences-createcontentreferences');

        $buttons[ButtonBar::BUTTON_POSITION_LEFT][120][] = $createContentReferencesButton;
    }

    protected function generateConvertReferencesToCopiesButton(ButtonBar $buttonBar, &$buttons)
    {
        $convertReferencesToCopiesButtonTitle = 'Convert References To Copies';
        $convertReferencesToCopiesButtonIcon = $this->iconFactory->getIcon(
            'actions-edit-cut',
            Icon::SIZE_SMALL
        );

        $convertReferencesToCopiesButton = $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setDataAttributes([
                'toogle' => 'tooltip',
                'placement' => 'bottom',
                'title' => $convertReferencesToCopiesButtonTitle
            ])
            ->setTitle($convertReferencesToCopiesButtonTitle)
            ->setIcon($convertReferencesToCopiesButtonIcon)
            ->setClasses('fbitpagereferences-convertreferencestocopies');

        $buttons[ButtonBar::BUTTON_POSITION_LEFT][120][] = $convertReferencesToCopiesButton;
    }
}
