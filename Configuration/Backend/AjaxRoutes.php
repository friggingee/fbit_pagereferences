<?php
return [
    'create_content_references' => [
        'path' => '/fbit_pagereferences/create_content_references',
        'target' => \FBIT\PageReferences\Utility\ReferencePageJavaScriptUtility::class . '::createContentReferences',
    ],
    'get_reference_page_content_data' => [
        'path' => '/fbit_pagereferences/get_reference_page_content_data',
        'target' => \FBIT\PageReferences\Utility\ReferencePageJavaScriptUtility::class . '::getReferencePageContentData',
    ],
    'page_tree_data' => [
        'path' => '/page/tree/fetchData',
        'target' => \FBIT\PageReferences\Overrides\TYPO3\CMS\Backend\Controller\Page\TreeController::class . '::fetchDataAction'
    ],
];
