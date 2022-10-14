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
            && isset($dataHandler->cmdmap['tt_content']['copy'])
            && is_array(reset($dataHandler->cmdmap['tt_content'])['copy'])
            && isset($dataHandler->cmdmap['tt_content']['copy']['parentAction'])
            && !empty(reset($dataHandler->cmdmap['tt_content'])['copy']['parentAction'])
            && reset($dataHandler->cmdmap['tt_content'])['copy']['parentAction'] === 'convertReferencesToCopies'
        ) {
            $dataHandler->neverHideAtCopy = true;
        }
    }
}
