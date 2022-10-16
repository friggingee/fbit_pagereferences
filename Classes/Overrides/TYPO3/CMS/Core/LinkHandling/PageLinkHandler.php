<?php
declare(strict_types=1);

namespace FBIT\PageReferences\Overrides\TYPO3\CMS\Core\LinkHandling;

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

use FBIT\PageReferences\Utility\ReferencesUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Resolves links to pages and the parameters given
 */
class PageLinkHandler extends \TYPO3\CMS\Core\LinkHandling\PageLinkHandler
{
    /**
     * Returns all relevant information built in the link to a page (see asString())
     *
     * @param array $data
     * @return array
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Core\Exception\SiteNotFoundException
     */
    public function resolveHandlerData(array $data): array
    {
        if (isset($GLOBALS['TSFE'])
            && $GLOBALS['TSFE'] instanceof TypoScriptFrontendController
            && $GLOBALS['TSFE']->page['tx_fbit_pagereferences_rewrite_links'] === 1
        ) {
            $linkTargetPageUid = (int)$data['uid'];

            $currentSite = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId((int)$GLOBALS['TSFE']->id);
            $context = GeneralUtility::makeInstance(Context::class);
            $languageAspect = $context->getAspect('language');

            $newLinkTargetPage = ReferencesUtility::getReferenceInSite($linkTargetPageUid, $currentSite, $languageAspect->getId());
            // if references yielded no target, try rewrite targets
            if ($newLinkTargetPage === null) {
                $newLinkTargetPage = ReferencesUtility::getRewriteTargetInSite($linkTargetPageUid, $currentSite, $languageAspect->getId());
            }

            if (!empty($newLinkTargetPage['uid'])) {
                $data['uid'] = $newLinkTargetPage['uid'];
            }
        }

        $result = parent::resolveHandlerData($data);

        return $result;
    }
}
