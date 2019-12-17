<?php

namespace FBIT\PageReferences\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;

class RecordUtility
{
    static public function isTranslation($uid, $tablename)
    {
        $recordData = BackendUtility::getRecord($tablename, $uid, '*', '', false);

        return $recordData['l10n_parent'] && $recordData['l10n_parent'] > 0;
    }
}
