<?php

namespace FBIT\PageReferences\Utility;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\SimpleDataHandlerController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;

class ReferencePageJavaScriptUtility
{
    protected $contentSelectFields = ['uid', 'colPos', 'sorting', 'sys_language_uid', 'l10n_source', 'header', 'hidden', 'deleted', 'CType'];

    public function getReferencePageContentData(ServerRequestInterface $request, ResponseInterface $response)
    {
        $data = [];

        $requestParams = $request->getQueryParams();

        $referencePageId = $requestParams['pageId'];
        $referencePageData = BackendUtility::getRecord('pages', $referencePageId);

        $referencedPageId = $referencePageData['content_from_pid'];
        $referencedPageId = $referencedPageId ?: $referencePageData['tx_fbit_pagereferences_reference_source_page'];

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $referencedPageContentData = $queryBuilder->select('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('pid', $referencedPageId),
                    $queryBuilder->expr()->eq('sys_language_uid', 0)
                )
            )
            ->execute()
            ->fetchAll();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $referencePageContentData = $queryBuilder
            ->select(...[
                'uid',
                'sorting',
                'sys_language_uid',
                'l10n_source',
                'deleted',
                'hidden',
                'records'
            ])
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('pid', $referencePageId),
                $queryBuilder->expr()->eq('CType', '"shortcut"')
            )
            ->execute()
            ->fetchAll();

        foreach ($referencePageContentData as $index => $referenceRecordData) {
            $linkedRecordUid = str_replace('tt_content_', '', $referenceRecordData['records']);

            $referencePageContentData[$index]['records'] = $linkedRecordUid;
        }

        $data['recordsToCopy'] = $referencedPageContentData;
        $data['recordsToAdjust'] = $referencePageContentData;

        $response->getBody()->write(json_encode($data));

        return $response;
    }

    public function callSimpleDataHandler(ServerRequestInterface $request, ResponseInterface $response) {
        $simpleDataHandler = GeneralUtility::makeInstance(SimpleDataHandlerController::class);
        $simpleDataHandlerResponse = $simpleDataHandler->processAjaxRequest($request);

        $tce = $simpleDataHandler->__get('tce');

        $simpleDataHandlerResponseContent = json_decode($simpleDataHandlerResponse->getBody()->getContents(), 1);
        $simpleDataHandlerResponseContent['tce'] = $tce;

        return new JsonResponse($simpleDataHandlerResponseContent);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Core\Exception\SiteNotFoundException
     */
    public function createContentReferences(ServerRequestInterface $request, ResponseInterface $response)
    {
        $requestParams = $request->getQueryParams();

        $referencePageId = $requestParams['pageId'];

        $referencePageData = BackendUtility::getRecord('pages', $referencePageId);

        $referencedPageId = $referencePageData['content_from_pid'];
        $referencedPageId = $referencedPageId ?: $referencePageData['tx_fbit_pagereferences_reference_source_page'];

        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($referencePageId);
        $availableLanguages = $site->getAvailableLanguages($GLOBALS['BE_USER']);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $pageContentData = $queryBuilder->select(...$this->contentSelectFields)
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('pid', $referencedPageId),
                    $queryBuilder->expr()->eq('sys_language_uid', 0)
                )
            )
            ->execute()
            ->fetchAll();

        foreach ($pageContentData as $pageContentRecord) {
            $fields = [
                'pid' => $referencePageId,
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

            $originalLanguageContentReferenceUid = $queryBuilder->getConnection()->lastInsertId();

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
            $contentRecordTranslations = $queryBuilder->select(...$this->contentSelectFields)
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq('pid', $referencedPageId),
                        $queryBuilder->expr()->in(
                            'sys_language_uid',
                            implode(
                                ',',
                                array_map(
                                    function (SiteLanguage $siteLanguage) {
                                        return $siteLanguage->getLanguageId();
                                    },
                                    $availableLanguages
                                )
                            )
                        ),
                        $queryBuilder->expr()->eq('l10n_source', $pageContentRecord['uid'])
                    )
                )
                ->orderBy('sys_language_uid', Query::ORDER_ASCENDING)
                ->execute()
                ->fetchAll();

            foreach ($contentRecordTranslations as $contentRecordTranslation) {
                $fields['sys_language_uid'] = $contentRecordTranslation['sys_language_uid'];
                $fields['l10n_source'] = $originalLanguageContentReferenceUid;
                $fields['records'] = 'tt_content_' . $contentRecordTranslation['uid'];
                $fields['header'] = $contentRecordTranslation['header'];
                $fields['hidden'] = $contentRecordTranslation['hidden'];
                $fields['deleted'] = $contentRecordTranslation['deleted'];

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
