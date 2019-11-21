<?php
namespace FBIT\PageReferences\Overrides\TYPO3\CMS\Core\Tree\TableConfiguration;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeNode;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * TCA tree data provider
 */
class DatabaseTreeDataProvider extends \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider
{
    /** @var array $currentValue */
    protected $currentValue;

    public function __construct($tcaConfiguration, $table, $field, array $currentValue)
    {
        $this->currentValue = $currentValue;
    }

    public function setRootUid($rootUid)
    {
        parent::setRootUid($rootUid);

        if (strstr($rootUid, '###REC_FIELD_')) {
            preg_match('/###REC_FIELD_(.*?)###/', $rootUid, $fieldNameMatches);

            $this->rootUid = $this->currentValue[$fieldNameMatches[1]];
        }
    }

    /**
     * Builds a complete node including childs
     *
     * @param \TYPO3\CMS\Backend\Tree\TreeNode $basicNode
     * @param \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeNode|null $parent
     * @param int $level
     * @return \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeNode Node object
     */
    protected function buildRepresentationForNode(\TYPO3\CMS\Backend\Tree\TreeNode $basicNode, DatabaseTreeNode $parent = null, $level = 0)
    {
        $node = parent::buildRepresentationForNode($basicNode, $parent, $level);
        $node->setSelected(true);

        return $node;
    }
}
