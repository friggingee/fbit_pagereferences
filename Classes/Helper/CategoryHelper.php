<?php

namespace FBIT\PageReferences\Helper;

use PDO;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class CategoryHelper
 * Helpers to handle TYPO3's sys_category
 * @package FBIT\PageReferences\Helper
 */
class CategoryHelper implements SingletonInterface
{
    /**
     * @var string
     */
    protected static $tableItem = 'pages';

    /**
     * @var string
     */
    protected static $tableSysCategory = 'sys_category';

    /**
     * @var string
     */
    protected static $tableSysCategoryMM = 'sys_category_record_mm';

    /**
     * @var QueryBuilder
     */
    private $queryBuilder = null;

    /**
     * @var QueryBuilder
     */
    private $queryBuilderPagesCategories = null;

    /**
     * @return CategoryHelper
     */
    public static function getInstance()
    {
        /** @var CategoryHelper $instance */
        $instance = GeneralUtility::makeInstance(__CLASS__);
        return $instance;
    }

    /**
     * Get category records linked to single page
     *
     * @param int $pageUid UID of page to get categories from
     * @param string $fieldNameCategories Field name of category column in pages table
     * @param bool $uidsOnly Return only array of UIDs, not complete category records?
     * @return array List of category UIDs or category records
     */
    public function getPagesCategories(
        int $pageUid,
        string $fieldNameCategories = 'categories',
        bool $uidsOnly = false
    ) {
        $queryBuilder = $this->getPreparedQueryBuilderPagesCategories();
        $queryBuilder->andWhere(
        // for current pageUid
            $queryBuilder->expr()->eq(
                static::$tableItem . '.uid',
                $queryBuilder->createNamedParameter($pageUid, PDO::PARAM_INT)
            ),
            // filter MM field name 'categories'
            $queryBuilder->expr()->eq(
                static::$tableSysCategoryMM . '.fieldname',
                $queryBuilder->createNamedParameter($fieldNameCategories, PDO::PARAM_STR)
            )
        );

        // execute statement and get result
        $result = $queryBuilder->execute()->fetchAll();

        // if wanted, return only array of uids, otherwise return complete result
        if ($uidsOnly) {
            $uids = [];
            foreach ($result as $resultItem) {
                if (!isset($resultItem['uid'])) {
                    continue;
                }
                $uids[] = (int)$resultItem['uid'];
            }
            return $uids;
        } else {
            return $result;
        }
    }

    /**
     * @return QueryBuilder
     */
    protected function getQueryBuilder()
    {
        if ($this->queryBuilder === null) {
            /** @var ConnectionPool $connectionPool */
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
            /** @var QueryBuilder queryBuilder */
            $this->queryBuilder = $connectionPool->getQueryBuilderForTable(static::$tableSysCategory);
            $this->queryBuilder->getRestrictions()->removeAll();
        }
        return $this->queryBuilder;
    }

    /**
     * @return QueryBuilder
     */
    protected function getPreparedQueryBuilderPagesCategories()
    {
        if ($this->queryBuilderPagesCategories == null) {
            $this->queryBuilderPagesCategories = $this->getQueryBuilder()
                // select sys_category.*
                ->select(static::$tableSysCategory . '.*')
                // ... from sys_category
                ->from(static::$tableSysCategory)
                // ... join sys_category_record_mm
                ->join(
                    static::$tableSysCategory,
                    static::$tableSysCategoryMM,
                    static::$tableSysCategoryMM,
                    $this->getQueryBuilder()->expr()->eq(
                        static::$tableSysCategoryMM . '.uid_local',
                        $this->getQueryBuilder()->quoteIdentifier(static::$tableSysCategory . '.uid')
                    )
                )
                // ... join item table (pages)
                ->join(
                    static::$tableSysCategoryMM,
                    static::$tableItem,
                    static::$tableItem,
                    $this->getQueryBuilder()->expr()->eq(
                        static::$tableSysCategoryMM . '.uid_foreign',
                        $this->getQueryBuilder()->quoteIdentifier(static::$tableItem . '.uid')
                    )
                )
                ->where(
                // filter MM table names 'pages'
                    $this->getQueryBuilder()->expr()->eq(
                        static::$tableSysCategoryMM . '.tablenames',
                        $this->getQueryBuilder()->createNamedParameter(static::$tableItem, PDO::PARAM_STR)
                    ),
                    // exclude deleted categories
                    $this->getQueryBuilder()->expr()->eq(
                        static::$tableSysCategory . '.deleted',
                        $this->getQueryBuilder()->createNamedParameter(0, PDO::PARAM_INT)
                    )
                );
        }
        return $this->queryBuilderPagesCategories;
    }
}
