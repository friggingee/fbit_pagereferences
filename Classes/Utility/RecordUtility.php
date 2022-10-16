<?php

namespace FBIT\PageReferences\Utility;

use FBIT\PageReferences\Domain\Model\ReferencePage;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RecordUtility
{
    static public function isTranslation(string $tableName, int $uid): bool
    {
        $recordData = BackendUtility::getRecord($tableName, $uid, '*', '', false);

        return isset($recordData['l10n_parent']) && $recordData['l10n_parent'] > 0;
    }

    static public function hardDeleteSuperfluousTranslation(string $tableName, int $uid): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $queryBuilder->delete($tableName)->where($queryBuilder->expr()->eq('uid', $uid))->execute();
    }

    static public function softDeleteSuperfluousTranslation(string $fieldName, int $foreignFieldValue, int $foreignSelectorValueSourceRecordUid): void
    {
        $relationsTableName = $GLOBALS['TCA']['pages']['columns'][$fieldName]['config']['foreign_table'];

        $foreignSelectorValueQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($relationsTableName);
        $foreignSelectorValue = $foreignSelectorValueQueryBuilder
            ->select($GLOBALS['TCA']['pages']['columns'][$fieldName]['config']['foreign_selector'])
            ->from($relationsTableName)
            ->where(
                $foreignSelectorValueQueryBuilder->expr()->eq('uid', $foreignSelectorValueSourceRecordUid)
            )
            ->execute()
            ->fetchOne();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($relationsTableName);

        $relationConstraints = [
            $queryBuilder->expr()->eq(
                $GLOBALS['TCA']['pages']['columns'][$fieldName]['config']['foreign_selector'],
                $queryBuilder->createNamedParameter($foreignSelectorValue)
            ),
            $queryBuilder->expr()->neq(
                $GLOBALS['TCA'][$relationsTableName]['ctrl']['cruser_id'],
                $queryBuilder->createNamedParameter(ReferencePage::CRUSER_ID)
            )
        ];

        if (isset($GLOBALS['TCA']['pages']['columns'][$fieldName]['config']['foreign_field'])) {
            $relationConstraints[] = $queryBuilder->expr()->eq(
                $GLOBALS['TCA']['pages']['columns'][$fieldName]['config']['foreign_field'],
                $queryBuilder->createNamedParameter($foreignFieldValue)
            );
        }
        if (isset($GLOBALS['TCA']['pages']['columns'][$fieldName]['config']['foreign_table_field'])) {
            $relationConstraints[] = $queryBuilder->expr()->eq(
                $GLOBALS['TCA']['pages']['columns'][$fieldName]['config']['foreign_table_field'],
                $queryBuilder->createNamedParameter('pages')
            );
        }
        if (isset($GLOBALS['TCA']['pages']['columns'][$fieldName]['config']['foreign_match_fields'])
            && is_array($GLOBALS['TCA']['pages']['columns'][$fieldName]['config']['foreign_match_fields'])
        ) {
            $foreignMatchFieldsConstraints = [];
            foreach ($GLOBALS['TCA']['pages']['columns'][$fieldName]['config']['foreign_match_fields'] as $fieldName => $fieldValue) {
                $foreignMatchFieldsConstraints[] = $queryBuilder->expr()->eq(
                    $fieldName,
                    $queryBuilder->createNamedParameter($fieldValue)
                );
            }
            $relationConstraints[] = $queryBuilder->expr()->and(...$foreignMatchFieldsConstraints);
        }

        $queryBuilder
            ->update($relationsTableName)
            ->where(
                $queryBuilder->expr()->and(...$relationConstraints)
            )
            ->set('deleted', 1)
            ->execute();
    }

    static public function updateRecordLowlevel(string $tableName, int $uid, array $updateValues): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $queryBuilder->update($tableName)->where($queryBuilder->expr()->eq('uid', $uid));

        foreach ($updateValues as $column => $value) {
            $queryBuilder->set($column, $value);
        }

        $queryBuilder->execute();
    }
}
