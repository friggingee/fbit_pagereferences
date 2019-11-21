<?php

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function () {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/tslib/class.tslib_menu.php']['filterMenuPages'][] =
            \FBIT\PageReferences\Hooks\ContentObject\Menu\AbstractMenuContentObject\FilterMenuPages::class;
    }
);
