<?php
namespace FBIT\PageReferences\Overrides\TYPO3\CMS\Frontend\Service;

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
 * This class provides basic functionality to encode and decode typolink strings
 */
class TypoLinkCodecService extends \TYPO3\CMS\Frontend\Service\TypoLinkCodecService
{
    /**
     * Decodes a TypoLink string into its parts
     *
     * @param string $typoLink The properly encoded TypoLink string
     * @return array Associative array of TypoLink parts with the keys url, target, class, title, additionalParams
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     * @throws \TYPO3\CMS\Core\Exception\SiteNotFoundException
     */
    public function decode($typoLink)
    {
        $typoLink = trim($typoLink);
        if ($typoLink !== '') {
            $parts = str_replace(['\\\\', '\\"'], ['\\', '"'], str_getcsv($typoLink, static::$partDelimiter));
        } else {
            $parts = '';
        }

        if (isset($GLOBALS['TSFE'])
            && $GLOBALS['TSFE'] instanceof TypoScriptFrontendController
            && $GLOBALS['TSFE']->page['tx_fbit_pagereferences_rewrite_links'] === 1
            && is_array($parts)
        ) {
            preg_match('/(t3:\/\/page\?uid=)?(\d+)/', $parts[0], $linkTargetPageUid);

            if ((int)end($linkTargetPageUid) > 0) {
                $currentSite = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId((int)$GLOBALS['TSFE']->id);
                $context = GeneralUtility::makeInstance(Context::class);
                $languageAspect = $context->getAspect('language');

                $newLinkTargetPage = ReferencesUtility::getReferenceInSite(end($linkTargetPageUid), $currentSite, $languageAspect->getId());
                // if references yielded no target, try rewrite targets
                if ($newLinkTargetPage === null) {
                    $newLinkTargetPage = ReferencesUtility::getRewriteTargetInSite(end($linkTargetPageUid), $currentSite, 0);
                }

                if (!empty($newLinkTargetPage['uid'])) {
                    $parts[0] = preg_replace('/(t3:\/\/page\?uid=)?(\d+)(.*?)$/', '${1}' . $newLinkTargetPage['uid'] . '${3}', $parts[0]);
                }
            }
        }

        // The order of the entries is crucial!!
        $typoLinkParts = [
            'url' => isset($parts[0]) ? trim($parts[0]) : '',
            'target' => isset($parts[1]) && $parts[1] !== static::$emptyValueSymbol ? trim($parts[1]) : '',
            'class' => isset($parts[2]) && $parts[2] !== static::$emptyValueSymbol ? trim($parts[2]) : '',
            'title' => isset($parts[3]) && $parts[3] !== static::$emptyValueSymbol ? trim($parts[3]) : '',
            'additionalParams' => isset($parts[4]) && $parts[4] !== static::$emptyValueSymbol ? trim($parts[4]) : ''
        ];

        return $typoLinkParts;
    }
}
