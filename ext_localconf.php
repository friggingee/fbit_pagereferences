<?php

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function () {
        // Backend features
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend\Template\Components\ButtonBar']['getButtonsHook'][] =
            \FBIT\PageReferences\Hooks\Backend\Template\Components\ButtonBar\GetButtonsHook\GenerateAdditionalModuleButtons::class . '->getButtons';
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawHeaderHook'][] =
            \FBIT\PageReferences\Hooks\Backend\Controller\PageLayoutController\DrawHeaderHook\PagePropertiesReferencedFlashMessage::class . '->addPagePropertiesReferencedFlashMessage';

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] =
            \FBIT\PageReferences\Hooks\Core\DataHandling\DataHandler\ProcessCmdmapClass\DontHideRecordsWhenConvertingContentReferencesToCopies::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
            \FBIT\PageReferences\Hooks\Core\DataHandling\DataHandler\ProcessDatamapClass\UpdateReferencePageProperties::class;

        if (TYPO3_MODE === 'BE') {
            $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/FbitPagereferences/PageReferences');
        }

        // Frontend features
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['linkHandler']['page'] =
            \FBIT\PageReferences\Overrides\TYPO3\CMS\Core\LinkHandling\PageLinkHandler::class;
    }
);
