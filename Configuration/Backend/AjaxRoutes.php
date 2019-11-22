<?php
return [
    'create_content_references' => [
        'path' => '/fbit_pagereferences/create_content_references',
        'target' => \FBIT\PageReferences\Utility\MountPageUtility::class . '::createContentReferences',
    ],
    'get_mount_page_content_data' => [
        'path' => '/fbit_pagereferences/get_mount_page_content_data',
        'target' => \FBIT\PageReferences\Utility\MountPageUtility::class . '::getMountPageContentData',
    ],
];
