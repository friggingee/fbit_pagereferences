<?php

namespace FBIT\PageReferences\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ReferencesUtility
{
    static public function hasReferences(int $referenceSourcePageUid, int $languageId = 0)
    {
        $hasReferences = false;

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $hasReferences = $queryBuilder->count('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->in('content_from_pid', $referenceSourcePageUid),
                    $queryBuilder->expr()->eq('sys_language_uid', $languageId)
                )
            )
            ->execute()
            ->fetchColumn(0);

        return $hasReferences;
    }

    /**
     * @param int $referenceSourcePageUid
     * @param Site $site
     * @return mixed|null
     * @throws \TYPO3\CMS\Core\Exception\SiteNotFoundException
     */
    static public function getReferenceInSite(int $referenceSourcePageUid, Site $site, int $languageId = 0)
    {
        $referenceInSite = null;
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);

        $allReferences = ReferencesUtility::getReferences($referenceSourcePageUid, $languageId);

        foreach ($allReferences as $reference) {
            $pageIdInDefaultLanguage = (int)($languageId > 0 ? $reference['l10n_parent'] : $reference['uid']);
            if (BackendUtility::getRecord('pages', $pageIdInDefaultLanguage)  !== null
                && $siteFinder->getSiteByPageId($pageIdInDefaultLanguage)->getIdentifier() === $site->getIdentifier()) {
                $referenceInSite = $reference;
                break;
            }
        }

        return $referenceInSite;
    }

    static public function getReferences(int $referenceSourcePageUid, int $languageId = 0)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $referencePages = $queryBuilder->select('uid', 'l10n_parent')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->in('content_from_pid', $referenceSourcePageUid),
                    $queryBuilder->expr()->eq('sys_language_uid', $languageId)
                )
            )
            ->execute()
            ->fetchAll();

        return $referencePages;
    }

    /**
     * @param int $referenceSourcePageUid
     * @param Site $site
     * @return mixed|null
     * @throws \TYPO3\CMS\Core\Exception\SiteNotFoundException
     */
    static public function getRewriteTargetInSite(int $referenceSourcePageUid, Site $site, int $languageId = 0)
    {
        $referenceInSite = null;
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);

        $allReferences = ReferencesUtility::getRewriteTargets($referenceSourcePageUid, $languageId);

        foreach ($allReferences as $reference) {
            if ($siteFinder->getSiteByPageId($reference['uid'])->getIdentifier() === $site->getIdentifier()) {
                $referenceInSite = $reference;
                break;
            }
        }

        return $referenceInSite;
    }

    static public function getRewriteTargets(int $referenceSourcePageUid, int $languageId = 0)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $referencePages = $queryBuilder->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->in('tx_fbit_pagereferences_reference_source_page', $referenceSourcePageUid),
                    $queryBuilder->expr()->eq('sys_language_uid', $languageId)
                )
            )
            ->execute()
            ->fetchAll();

        return $referencePages;
    }
}
