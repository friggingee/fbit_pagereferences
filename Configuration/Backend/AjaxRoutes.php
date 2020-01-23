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
    'record_process' => [
        'path' => '/record/process',
        'target' => \FBIT\PageReferences\Utility\ReferencePageJavaScriptUtility::class . '::callSimpleDataHandler'
    ],
];
