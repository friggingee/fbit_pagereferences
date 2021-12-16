<?php

namespace FBIT\PageReferences\Overrides\TYPO3\CMS\Backend\Controller\Page;

use FBIT\PageReferences\Domain\Model\ReferencePage;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

/**
 * Controller providing data to the page tree
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class TreeController extends \TYPO3\CMS\Backend\Controller\Page\TreeController
{
    /**
     * Converts nested tree structure produced by PageTreeRepository to a flat, one level array
     * and also adds visual representation information to the data.
     *
     * @param array $page
     * @param int $entryPoint
     * @param int $depth
     * @param array $inheritedData
     * @return array
     */
    protected function pagesToFlatArray(array $page, int $entryPoint, int $depth = 0, array $inheritedData = []): array
    {
        $backendUser = $this->getBackendUser();
        $pageId = (int)$page['uid'];
        if (in_array($pageId, $this->hiddenRecords, true)) {
            return [];
        }
        if ($pageId === 0 && !$backendUser->isAdmin()) {
            return [];
        }

        $stopPageTree = !empty($page['php_tree_stop']) && $depth > 0;
        $identifier = $entryPoint . '_' . $pageId;
        $expanded = !empty($page['expanded'])
            || (isset($this->expandedState[$identifier]) && $this->expandedState[$identifier])
            || $this->expandAllNodes;

        $backgroundColor = !empty($this->backgroundColors[$pageId]) ? $this->backgroundColors[$pageId] : ($inheritedData['backgroundColor'] ?? '');

        $suffix = '';
        $prefix = '';
        $nameSourceField = 'title';
        $visibleText = $page['title'];
        $tooltip = BackendUtility::titleAttribForPages($page, '', false);
        if ($pageId !== 0) {
            $icon = $this->iconFactory->getIconForRecord('pages', $page, Icon::SIZE_SMALL);
        } else {
            $icon = $this->iconFactory->getIcon('apps-pagetree-root', Icon::SIZE_SMALL);
        }

        if ($this->useNavTitle && trim($page['nav_title'] ?? '') !== '') {
            $nameSourceField = 'nav_title';
            $visibleText = $page['nav_title'];
        }

        // Use title and/or nav title from reference source page if the current page is a page reference
        if (
            $visibleText === null
            && $page['content_from_pid']
            && $page['doktype'] === ReferencePage::DOKTYPE
        ) {
            $referencedPage = BackendUtility::getRecord('pages', $page['content_from_pid']);
            $visibleText = $referencedPage['title'];

            if ($this->useNavTitle && trim($page['nav_title'] ?? '') !== '') {
                $visibleText = $referencedPage['nav_title'];
            }
        }

        if (trim($visibleText) === '') {
            $visibleText = htmlspecialchars('[' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title') . ']');
        }

        if ($this->addDomainName && $page['is_siteroot']) {
            $domain = $this->getDomainNameForPage($pageId);
            $suffix = $domain !== '' ? ' [' . $domain . ']' : '';
        }

        $lockInfo = BackendUtility::isRecordLocked('pages', $pageId);
        if (is_array($lockInfo)) {
            $tooltip .= ' - ' . $lockInfo['msg'];
        }
        if ($this->addIdAsPrefix) {
            $prefix = htmlspecialchars('[' . $pageId . '] ');
        }

        $items = [];
        $item = [
            // Used to track if the tree item is collapsed or not
            'stateIdentifier' => $identifier,
            'identifier' => $pageId,
            'depth' => $depth,
            'tip' => htmlspecialchars($tooltip),
            'icon' => $icon->getIdentifier(),
            'name' => $visibleText,
            'nameSourceField' => $nameSourceField,
            'mountPoint' => $entryPoint,
            'workspaceId' => !empty($page['t3ver_oid']) ? $page['t3ver_oid'] : $pageId,
            'siblingsCount' => $page['siblingsCount'] ?? 1,
            'siblingsPosition' => $page['siblingsPosition'] ?? 1,
            'allowDelete' => $backendUser->doesUserHaveAccess($page, Permission::PAGE_DELETE),
            'allowEdit' => $backendUser->doesUserHaveAccess($page, Permission::PAGE_EDIT)
                && $backendUser->check('tables_modify', 'pages')
                && $backendUser->checkLanguageAccess(0)
        ];

        if (!empty($page['_children']) || $this->getPageTreeRepository()->hasChildren($pageId)) {
            $item['hasChildren'] = true;
            if ($depth >= $this->levelsToFetch) {
                $page = $this->getPageTreeRepository()->getTreeLevels($page, 1);
            }
        }
        if (!empty($prefix)) {
            $item['prefix'] = htmlspecialchars($prefix);
        }
        if (!empty($suffix)) {
            $item['suffix'] = htmlspecialchars($suffix);
        }
        if (is_array($lockInfo)) {
            $item['locked'] = true;
        }
        if ($icon->getOverlayIcon()) {
            $item['overlayIcon'] = $icon->getOverlayIcon()->getIdentifier();
        }
        if ($expanded && is_array($page['_children']) && !empty($page['_children'])) {
            $item['expanded'] = $expanded;
        }
        if ($backgroundColor) {
            $item['backgroundColor'] = htmlspecialchars($backgroundColor);
        }
        if ($stopPageTree) {
            $item['stopPageTree'] = $stopPageTree;
        }
        $class = $this->resolvePageCssClassNames($page);
        if (!empty($class)) {
            $item['class'] = $class;
        }
        if ($depth === 0) {
            $item['isMountPoint'] = true;

            if ($this->showMountPathAboveMounts) {
                $item['readableRootline'] = $this->getMountPointPath($pageId);
            }
        }

        $items[] = $item;
        if (!$stopPageTree && is_array($page['_children']) && !empty($page['_children']) && ($depth < $this->levelsToFetch || $expanded)) {
            $siblingsCount = count($page['_children']);
            $siblingsPosition = 0;
            $items[key($items)]['loaded'] = true;
            foreach ($page['_children'] as $child) {
                $child['siblingsCount'] = $siblingsCount;
                $child['siblingsPosition'] = ++$siblingsPosition;
                $items = array_merge($items, $this->pagesToFlatArray($child, $entryPoint, $depth + 1, ['backgroundColor' => $backgroundColor]));
            }
        }
        return $items;
    }
}
