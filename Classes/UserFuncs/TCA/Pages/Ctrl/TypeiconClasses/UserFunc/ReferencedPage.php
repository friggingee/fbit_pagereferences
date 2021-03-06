<?php

namespace FBIT\PageReferences\UserFuncs\TCA\Pages\Ctrl\TypeiconClasses\UserFunc;

use FBIT\PageReferences\Domain\Model\ReferencePage;

class ReferencedPage
{
    public function overrideIconIfPageIsFullReference(&$params, &$iconFactoryNullReference)
    {
        if ($params['row']['doktype'] === ReferencePage::DOKTYPE) {
            return 'apps-pagetree-page-reference';
        }
    }
}
