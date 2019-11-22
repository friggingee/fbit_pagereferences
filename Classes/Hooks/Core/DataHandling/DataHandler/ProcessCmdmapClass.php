<?php

namespace FBIT\PageReferences\Hooks\Core\DataHandling\DataHandler;

use TYPO3\CMS\Core\DataHandling\DataHandler;

class ProcessCmdmapClass
{
    public function processCmdmap_beforeStart(DataHandler &$dataHandler)
    {
        if (
            is_array($dataHandler->cmdmap['tt_content'])
            && is_array(reset($dataHandler->cmdmap['tt_content'])['copy'])
            && !empty(reset($dataHandler->cmdmap['tt_content'])['copy']['parentAction'])
            && reset($dataHandler->cmdmap['tt_content'])['copy']['parentAction'] === 'convertReferencesToCopies'
        ) {
            $dataHandler->neverHideAtCopy = true;
        }
    }
}
