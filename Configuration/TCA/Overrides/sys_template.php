<?php

call_user_func(
    function ($extKey) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('FBIT.' . $extKey, 'Configuration/TypoScript', 'Page References Management');
    },
    'fbit_pagereferences'
);