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

use FBIT\PageReferences\Overrides\TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider;

/**
 * Builds a \TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider
 * object based on some TCA configuration
 */
class TreeDataProviderFactory extends \TYPO3\CMS\Core\Tree\TableConfiguration\TreeDataProviderFactory
{
    /**
     * Gets the data provider, depending on TCA configuration
     *
     * @param array $tcaConfiguration
     * @param $table
     * @param $field
     * @param array $currentValue The current database row, handing over 'uid' is enough
     * @return DatabaseTreeDataProvider
     * @throws \InvalidArgumentException
     */
    public static function getDataProvider(array $tcaConfiguration, $table, $field, $currentValue)
    {
        $dataProvider = parent::getDataProvider($tcaConfiguration, $table, $field, $currentValue);

        $treeConfiguration = $tcaConfiguration['treeConfig'];

        if (isset($treeConfiguration['rootUid'])) {
            $dataProvider->setRootUid($treeConfiguration['rootUid']);
        }

        return $dataProvider;
    }
}
