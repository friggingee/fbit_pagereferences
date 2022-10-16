<?php

namespace FBIT\PageReferences\Hooks\Core\DataHandling\DataHandler\ProcessDatamapClass;

use FBIT\PageReferences\Domain\Model\ReferencePage;
use FBIT\PageReferences\Utility\RecordUtility;
use FBIT\PageReferences\Utility\ReferencesUtility;
use TYPO3\CMS\Backend\Controller\FormSlugAjaxController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Category\CategoryRegistry;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class UpdateReferencePageProperties
{
    protected $updateReferencePagesOnSourcePageSave = false;
    protected $updateReferencePageOnEnablingPropertiesReferencing = false;
    protected $resetReferencePageOnDisablingPropertiesReferencing = false;

    protected $fullCurrentPageData = [];
    protected $fullReferenceSourcePageData = [];
    protected $fullReferencePageData = [];

    protected $createdInlineRelations = [];

    /**
     * Adds the field which triggers the copying of field values from a source page to a reference to the list of fields
     * which trigger a reloading of the page tree in order to update the page title display after copying.
     *
     * @param DataHandler $dataHandler
     */
    public function processDatamap_beforeStart(DataHandler $dataHandler)
    {
        $dataHandler->pagetreeRefreshFieldsFromPages[] = 'tx_fbit_pagereferences_reference_page_properties';
    }

    /**
     * Provides functions to copy field values from a source page to a reference page and to reset these changes.
     *
     * @param array $incomingFieldArray
     * @param string $table
     * @param $pageUid
     * @param $dataHandler
     */
    public function processDatamap_preProcessFieldArray(array &$incomingFieldArray, string $table, $pageUid, DataHandler &$dataHandler)
    {
        if ($table === 'pages') {

            // Check if there's anything to do for us.
            if ($this->doUpdateReferencePageProperties($incomingFieldArray, (int)$pageUid)) {
                // Fetch the full data of the currently edited page - we possibly need more than is included in the $incomingFieldArray.
                $this->fullCurrentPageData = BackendUtility::getRecord('pages', $pageUid);

                if ($this->updateReferencePagesOnSourcePageSave) {
                    // Check if the currently edited page has references in the current language.
                    if (ReferencesUtility::hasReferences($pageUid, $this->fullCurrentPageData['sys_language_uid'])) {
                        $this->fullReferenceSourcePageData = $this->fullCurrentPageData;
                        // Get all reference page IDs in the current language.
                        $referencePages = ReferencesUtility::getReferences($pageUid, $this->fullCurrentPageData['sys_language_uid']);

                        foreach ($referencePages as $referencePage) {
                            $this->fullReferencePageData = BackendUtility::getRecord('pages', $referencePage['uid']);

                            // Override reference page fields with source page values.
                            $dataHandlerDataMap = [];
                            $dataHandlerDataMap['pages'] = [];
                            $dataHandlerDataMap['pages'][$referencePage['uid']] = $this->overrideReferencePageFieldsWithSourcePageValues($referencePage['uid'], true);

                            $this->processThroughDataHandler($dataHandlerDataMap, []);
                        }

                        // trigger page tree update
                        $dataHandler->pagetreeRefreshFieldsFromPages[] = 'tstamp';
                    }
                }

                // update from source page
                if ($this->updateReferencePageOnEnablingPropertiesReferencing && $this->fullCurrentPageData['content_from_pid']) {
                    if ($this->fullCurrentPageData['sys_language_uid'] > 0) {
                        $this->fullReferenceSourcePageData = BackendUtility::getRecordLocalization(
                            'pages',
                            $this->fullCurrentPageData['content_from_pid'],
                            $this->fullCurrentPageData['sys_language_uid']
                        )[0];
                    } else {
                        $this->fullReferenceSourcePageData = BackendUtility::getRecord('pages', $this->fullCurrentPageData['content_from_pid']);
                    }

                    $incomingFieldArray = $this->overrideReferencePageFieldsWithSourcePageValues($pageUid, false);
                }

                // reset to original values
                if ($this->resetReferencePageOnDisablingPropertiesReferencing) {
                    $incomingFieldArray = unserialize($this->fullCurrentPageData['tx_fbit_pagereferences_original_page_properties']);
                    $incomingFieldArray['tx_fbit_pagereferences_reference_page_properties'] = '0';
                    $incomingFieldArray['tx_fbit_pagereferences_original_page_properties'] = '';

                    $this->deleteRelationsCreatedOnLastBackupCreation($incomingFieldArray);
                    $incomingFieldArray = $this->resolveRelations($incomingFieldArray, $this->fullCurrentPageData['uid'], false, true);
                }
            }
        }
    }

    /**
     * @param array $incomingFieldArray
     * @param int $pageUid
     * @return bool
     */
    protected function doUpdateReferencePageProperties(array $incomingFieldArray, int $pageUid)
    {
        // reset flags because this hook might be called multiple times but will not be reinitialized each time before being called
        $this->updateReferencePagesOnSourcePageSave = false;
        $this->updateReferencePageOnEnablingPropertiesReferencing = false;
        $this->resetReferencePageOnDisablingPropertiesReferencing = false;

        // when clicking "Save and update references" on a reference source page
        $this->updateReferencePagesOnSourcePageSave = GeneralUtility::_POST('_savedokandupdatereferences') && count($incomingFieldArray);

        // when changing the "Reference page properties" field on a reference page
        $triggerReferencePageOverride = !$this->updateReferencePagesOnSourcePageSave
            && count($incomingFieldArray)
            && GeneralUtility::_GET('route') === '/record/edit'
            && is_array(GeneralUtility::_GET('edit'));

        if ($triggerReferencePageOverride && isset($incomingFieldArray['tx_fbit_pagereferences_reference_page_properties'])) {
            $pageData = BackendUtility::getRecord('pages', $pageUid);

            $currentSetting = (int)$pageData['tx_fbit_pagereferences_reference_page_properties'];
            $submittedSetting = (int)$incomingFieldArray['tx_fbit_pagereferences_reference_page_properties'];

            $this->updateReferencePageOnEnablingPropertiesReferencing = $triggerReferencePageOverride
                && $currentSetting === 0
                && $submittedSetting === 1;

            $this->resetReferencePageOnDisablingPropertiesReferencing = $triggerReferencePageOverride
                && $currentSetting === 1
                && $submittedSetting === 0;
        }

        return ($this->updateReferencePagesOnSourcePageSave || $this->updateReferencePageOnEnablingPropertiesReferencing || $this->resetReferencePageOnDisablingPropertiesReferencing);
    }

    /**
     * @param int $referencePageUid
     * @param bool $fromSourcePage
     * @return array|mixed
     */
    protected function overrideReferencePageFieldsWithSourcePageValues(int $referencePageUid, $fromSourcePage = false)
    {
        $referenceSourcePageData = $this->fullReferenceSourcePageData;
        $referenceTargetPageData = $fromSourcePage ? $this->fullReferencePageData : $this->fullCurrentPageData;

        // Remove protected keys and values of "sleeping" (deleted) database columns from data which to update in reference pages.
        $pageData = array_diff_key(
            $referenceSourcePageData,
            array_flip(ReferencePage::PROTECTED_PROPERTIES)
        );
        $pageData = array_filter(
            $pageData,
            function ($key) {
                return strpos($key, 'zzz_') === false;
            },
            ARRAY_FILTER_USE_KEY
        );

        // Store original page properties as backup.
        if (!empty($referenceTargetPageData['tx_fbit_pagereferences_original_page_properties'])) {
            // Don't overwrite old backup
            $originalPageData = unserialize($referenceTargetPageData['tx_fbit_pagereferences_original_page_properties']);
        } else {
            $originalPageData = array_filter(
                $referenceTargetPageData,
                function ($key) {
                    return !in_array($key, ['uid', 'pid']) && strpos($key, 'zzz_') === false;
                },
                ARRAY_FILTER_USE_KEY
            );
            $originalPageData = $this->resolveRelations($originalPageData, $referenceTargetPageData['uid'], false);
        }

        // Process inline relations after saving the backup.
        // If we attempt this before, we will be seeing relations in the backup which only just have been created from
        // the reference source page.
        $pageData = $this->resolveRelations($pageData, $referenceSourcePageData['uid'], true);

        // Add created inline relations to backup array. This way we know which ones to delete when restoring the backup.
        $originalPageData['createdInlineRelations'] = array_merge(
            $originalPageData['createdInlineRelations'] ?? [],
            $this->createdInlineRelations
        );
        $pageData['tx_fbit_pagereferences_original_page_properties'] = serialize($originalPageData);

        $pageData['slug'] = $this->recreateSlug($pageData, $referencePageUid, $referenceTargetPageData['pid']);

        // Set the flags again because they've been unset in the array_diff_key call above.
        $pageData['tx_fbit_pagereferences_reference_page_properties'] = 1;
        $pageData['tx_fbit_pagereferences_rewrite_links'] = $referenceTargetPageData['tx_fbit_pagereferences_rewrite_links'];

        return $pageData;
    }

    /**
     * @param array $recordData
     * @param int $recordPid
     * @param bool $createMissingRelations
     * @param bool $modeReset Are we in reset mode?
     * @return array
     */
    protected function resolveRelations(array $recordData, int $recordPid, $createMissingRelations = false, bool $modeReset = false)
    {
        foreach ($recordData as $fieldName => $value) {
            $recordData[$fieldName] = $this->resolveRelationField($fieldName, $recordPid, '', $createMissingRelations, $modeReset) ?: $value;
        }

        return $recordData;
    }

    /**
     * @param string $fieldName
     * @param int $recordPid
     * @param string $fieldKey
     * @param bool $createMissingRelations
     * @param bool $modeReset Are we in reset mode? Categories (stored as CSIs or arrays) are handled as conventional fields and ignored then
     * @return string|null
     */
    protected function resolveRelationField(string $fieldName, int $recordPid, $fieldKey = '', $createMissingRelations = false, bool $modeReset = false)
    {
        $fieldValue = null;

        $fieldName = $fieldKey ?: $fieldName;

        if (!isset($GLOBALS['TCA']['pages']['columns'][$fieldName]['config']['type'])) {
            return $fieldValue;
        }

        switch ($GLOBALS['TCA']['pages']['columns'][$fieldName]['config']['type']) {
            case 'inline':
                // clean instance per field
                $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
                // resolve inline relations, fetch their IDs
                $relationHandler->start(
                    '',
                    'pages',
                    '',
                    $recordPid,
                    '',
                    $GLOBALS['TCA']['pages']['columns'][$fieldName]['config']
                );

                $relatedRecords = $relationHandler->getValueArray(true);

                if ($relatedRecords && $createMissingRelations) {
                    $relatedRecordsType = substr($relatedRecords[0], 0, strrpos($relatedRecords[0], '_'));

                    $relationCopyCmdMap = [];
                    $relationCopyCmdMap[$relatedRecordsType] = [];

                    foreach ($relatedRecords as $recordDataString) {
                        $relatedRecordSourceId = substr($recordDataString, strrpos($recordDataString, '_') + 1, strlen($recordDataString));

                        $relationCopyCmdMap[$relatedRecordsType][$relatedRecordSourceId] = [
                            'copy' => [
                                'action' => 'paste',
                                'target' => $this->fullCurrentPageData['uid'],
                                'update' => [
                                    'uid_foreign' => $this->fullCurrentPageData['uid']
                                ],
                            ],
                        ];
                    }

                    $temporaryDataHandler = $this->processThroughDataHandler([], $relationCopyCmdMap);

                    $newlyCreatedInlineRelations = $temporaryDataHandler->copyMappingArray_merged;

                    // DataHandler creates copies of localizations of any record without any option to switch this off.
                    // We're dealing with each language separately so we need to clean up afterwards.
                    foreach ($newlyCreatedInlineRelations as $tableName => $recordData) {
                        foreach ($recordData as $originalUid => $newUid) {
                            $recordTypeLanguageField = $GLOBALS['TCA'][$tableName]['ctrl']['languageField'];

                            if ($this->fullReferenceSourcePageData['sys_language_uid'] !== BackendUtility::getRecord($tableName, $newUid)[$recordTypeLanguageField]) {
                                // To not litter the database with dead records, we use a hard delete for any translations created
                                // by DataHandler which do not correspond to the current reference source page language.
                                RecordUtility::hardDeleteSuperfluousTranslation($tableName, $newUid);
                                unset($newlyCreatedInlineRelations[$tableName][$originalUid]);
                            } else {
                                // Mark records in the same language with our special cruser_id so we know who created them.
                                RecordUtility::updateRecordLowlevel($tableName, $newUid, ['cruser_id' => ReferencePage::CRUSER_ID]);
                                if ($this->fullReferenceSourcePageData['sys_language_uid'] > 0) {
                                    // Soft delete records possibly automatically created when this translation was created.
                                    // Soft delete is used to be able to restore them when resetting the reference page properties.
                                    RecordUtility::softDeleteSuperfluousTranslation($fieldName, $this->fullCurrentPageData['uid'], $newUid);
                                }
                            }
                        }
                    }

                    $this->createdInlineRelations = array_merge_recursive($this->createdInlineRelations, $newlyCreatedInlineRelations);
                    $fieldValue = implode(',', $newlyCreatedInlineRelations[$relatedRecordsType]);
                } else {
                    $fieldValue = implode(',', $relationHandler->getValueArray());
                }
                break;
            case 'select':
                if (!$modeReset && CategoryRegistry::getInstance()->isRegistered('pages', $fieldName)) {
                    // current field is sys_category reference
                    $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
                    $relationHandler->start(
                        '',
                        'sys_category',
                        $GLOBALS['TCA']['pages']['columns'][$fieldName]['config']['MM'],
                        $recordPid,
                        'pages',
                        $GLOBALS['TCA']['pages']['columns'][$fieldName]['config']
                    );
                    $fieldValue = $relationHandler->getValueArray();
                }
        }

        return $fieldValue;
    }

    /**
     * @param array $dataMap
     * @param array $cmdMap
     * @return object|DataHandler
     */
    protected function processThroughDataHandler($dataMap = [], $cmdMap = [])
    {
        $temporaryDataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $temporaryDataHandler->start($dataMap, $cmdMap);
        $temporaryDataHandler->process_datamap();
        $temporaryDataHandler->process_cmdmap();

        return $temporaryDataHandler;
    }

    /**
     * @param array $pageData
     * @param int $referencePageUid
     * @param int $referencePagePid
     * @return string
     */
    protected function recreateSlug(array $pageData, int $referencePageUid, int $referencePagePid)
    {
        // Re-create slug based on possibly changed page title
        $request = GeneralUtility::makeInstance(ServerRequest::class);

        $requestQueryParameters = [
            'tableName' => 'pages',
            'pageId' => $referencePageUid,
            'recordId' => $referencePageUid,
            'language' => $pageData['sys_language_uid'],
            'fieldName' => 'slug',
            'command' => '',
            'parentPageId' => $referencePagePid,
        ];

        $requestQueryParameters['signature'] = GeneralUtility::hmac(
            implode('', $requestQueryParameters),
            FormSlugAjaxController::class
        );

        $requestQueryParameters['mode'] = 'recreate';
        $requestQueryParameters['values'] = ['title' => $pageData['title']];

        $request = $request->withParsedBody($requestQueryParameters);

        $response = GeneralUtility::makeInstance(FormSlugAjaxController::class)->suggestAction($request);
        $slugSuggestionData = json_decode($response->getBody());
        $slug = $slugSuggestionData->proposal;

        return $slug;
    }

    /**
     * @param array $pageData
     */
    protected function deleteRelationsCreatedOnLastBackupCreation(array $pageData)
    {
        $createdInlineRelations = $pageData['createdInlineRelations'];

        $relationDeleteCmdMap = [];

        if (!empty($createdInlineRelations)) {
            foreach ($createdInlineRelations as $tableName => $idMap) {
                $createdRelationsIds = array_values($idMap);

                $relationDeleteCmdMap[$tableName] = [];

                foreach ($createdRelationsIds as $createdRelationId) {
                    if (!RecordUtility::isTranslation($tableName, $createdRelationId)) {
                        $relationDeleteCmdMap[$tableName][$createdRelationId] = [
                            'delete' => [
                                'action' => 'delete',
                                'table' => $tableName,
                                'uid' => $createdRelationId
                            ]
                        ];

                        $this->processThroughDataHandler([], $relationDeleteCmdMap);
                    }
                }
            }
        }
    }

    public function processDatamap_postProcessFieldArray(string $status, string $table, $id, array &$fieldArray, DataHandler $dataHandler)
    {
        if ($table === 'pages' && MathUtility::canBeInterpretedAsInteger($id)) {
            if (!empty($fieldArray['content_from_pid'])) {
                if (BackendUtility::getRecord('pages', $id)['doktype'] === ReferencePage::DOKTYPE) {
                    // Save the last source page ID to a field which won't be reset automatically.
                    // This allows us to rewrite links even if a reference page has been set to a different doktype
                    // later on.
                    $fieldArray['tx_fbit_pagereferences_reference_source_page'] = $fieldArray['content_from_pid'];
                }
            }
        }
    }

    public function processDatamap_afterAllOperations(DataHandler $dataHandler) {
        $foo = 'bar';
    }
}
