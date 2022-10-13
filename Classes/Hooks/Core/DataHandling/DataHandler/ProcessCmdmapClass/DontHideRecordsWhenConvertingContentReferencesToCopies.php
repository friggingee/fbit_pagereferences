<?php

namespace FBIT\PageReferences\Hooks\Core\DataHandling\DataHandler\ProcessCmdmapClass;

use TYPO3\CMS\Core\DataHandling\DataHandler;

class DontHideRecordsWhenConvertingContentReferencesToCopies
{
    public function processCmdmap_beforeStart(DataHandler &$dataHandler)
    {
        $this->dontHideRecordsWhenConvertingContentReferencesToCopies($dataHandler);
    }

    protected function dontHideRecordsWhenConvertingContentReferencesToCopies(DataHandler &$dataHandler)
    {
        if (isset($dataHandler->cmdmap['tt_content'])
            && is_array($dataHandler->cmdmap['tt_content'])
            && is_array(reset($dataHandler->cmdmap['tt_content'])['copy'])
            && !empty(reset($dataHandler->cmdmap['tt_content'])['copy']['parentAction'])
            && reset($dataHandler->cmdmap['tt_content'])['copy']['parentAction'] === 'convertReferencesToCopies'
        ) {
            $dataHandler->neverHideAtCopy = true;
        }
    }
}
