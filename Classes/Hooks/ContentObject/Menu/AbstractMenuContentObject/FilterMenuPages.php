<?php

namespace FBIT\PageReferences\Hooks\ContentObject\Menu\AbstractMenuContentObject;

use TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject;
use TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuFilterPagesHookInterface;

class FilterMenuPages implements AbstractMenuFilterPagesHookInterface
{
    public function processFilter(array &$data, array $banUidArray, $spacer, AbstractMenuContentObject $obj)
    {
        $includePage = true;

        if ($obj->MP_array[0]) {
            $mountPageUid = explode('-', $obj->MP_array[0])[1];

            if ($obj->getSysPage()->getPage($mountPageUid)['tx_fbit_pagereferences_stop_mountpoint_pagetree']) {
                $includePage = false;
            }
        }

        return $includePage;
    }
}
