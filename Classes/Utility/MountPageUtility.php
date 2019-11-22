<?php

namespace FBIT\PageReferences\Utility;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;

class MountPageUtility
{
    protected $contentSelectFields = ['uid', 'colPos', 'sorting', 'sys_language_uid', 'l10n_source', 'header', 'hidden', 'deleted', 'CType'];

    public function getMountPageContentData(ServerRequestInterface $request, ResponseInterface $response)
    {
        $requestParams = $request->getQueryParams();

        $mountPageId = $requestParams['pageId'];

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $pageContentData = $queryBuilder->select(...['uid', 'records', 'colPos'])
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('pid', $mountPageId),
                    $queryBuilder->expr()->eq('sys_language_uid', 0)
                )
            )
            ->execute()
            ->fetchAll();

        $data['pageContentData'] = array_map(
            function ($contentRecord) {
                $contentRecord['records'] = str_replace('tt_content_', '', $contentRecord['records']);
                $contentRecord['records'] = explode(',', $contentRecord['records']);
                return $contentRecord;
            },
            $pageContentData
        );

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $translatedPageContentData = $queryBuilder->select(...['uid'])
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('pid', $mountPageId),
                    $queryBuilder->expr()->gt('sys_language_uid', 0)
                )
            )
            ->execute()
            ->fetchAll();

        $data['translatedContentUids'] = array_map(
            function($contentRecord) {
                return $contentRecord['uid'];
            },
            $translatedPageContentData
        );

        $response->getBody()->write(json_encode($data));

        return $response;
    }

    public function createContentReferences(ServerRequestInterface $request, ResponseInterface $response)
    {
        $requestParams = $request->getQueryParams();

        $mountPageId = $requestParams['pageId'];
        $mountedPageId = BackendUtility::getRecord('pages', $mountPageId)['mount_pid'];

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $pageContentData = $queryBuilder->select(...$this->contentSelectFields)
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('pid', $mountedPageId),
                    $queryBuilder->expr()->eq('sys_language_uid', 0),
                    $queryBuilder->expr()->neq('sorting', 1000000000)
                )
            )
            ->execute()
            ->fetchAll();

        foreach ($pageContentData as $pageContentRecord) {
            $fields = [
                'pid' => $mountPageId,
                'tstamp' => time(),
                'crdate' => time(),
                'cruser_id' => $GLOBALS['BE_USER']->user['uid'],
                'sorting' => $pageContentRecord['sorting'],
                'CType' => 'shortcut',
                'header' => $pageContentRecord['header'],
                'records' => 'tt_content_' . $pageContentRecord['uid'],
                'colPos' => $pageContentRecord['colPos'],
                'sys_language_uid' => $pageContentRecord['sys_language_uid'],
                'l10n_source' => $pageContentRecord['l10n_source'],
                'hidden' => $pageContentRecord['hidden'],
                'deleted' => $pageContentRecord['deleted']
            ];

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
            $queryBuilder->insert('tt_content')
                ->values($fields)
                ->execute();

            $originalLanguagecontentReferenceUid = $queryBuilder->getConnection()->lastInsertId();

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
            $contentRecordTranslations = $queryBuilder->select(...$this->contentSelectFields)
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq('pid', $mountedPageId),
                        $queryBuilder->expr()->gt('sys_language_uid', 0),
                        $queryBuilder->expr()->eq('l10n_source', $pageContentRecord['uid'])
                    )
                )
                ->orderBy('sys_language_uid', Query::ORDER_ASCENDING)
                ->execute()
                ->fetchAll();

            foreach ($contentRecordTranslations as $contentRecordTranslation) {
                $fields['sys_language_uid'] = $contentRecordTranslation['sys_language_uid'];
                $fields['l10n_source'] = $originalLanguagecontentReferenceUid;

                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
                $queryBuilder->insert('tt_content')
                    ->values($fields)
                    ->execute();
            }
        }

        $data['success'] = true;

        $response->getBody()->write(json_encode($data));

        return $response;
    }
}
