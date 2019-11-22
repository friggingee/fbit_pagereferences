<?php

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function () {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/tslib/class.tslib_menu.php']['filterMenuPages'][] =
            \FBIT\PageReferences\Hooks\Frontend\ContentObject\Menu\AbstractMenuContentObject\FilterMenuPages::class;

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend\Template\Components\ButtonBar']['getButtonsHook'][] =
            \FBIT\PageReferences\Hooks\Backend\Template\Components\ButtonBar\GetButtonsHook::class . '->getButtons';

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = \FBIT\PageReferences\Hooks\Core\DataHandling\DataHandler\ProcessCmdmapClass::class;

        $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/FbitPagereferences/CreateContentReferences');
    }
);
