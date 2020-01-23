define(['jquery'], function ($) {
    var PageReferences = {
        pageId: null,
        progressCount: {},
        conversionDoneEvent: {},
        CONVERT_REFERENCES_TO_COPIES_COMPLETE: 'convertReferencesToCopiesComplete',
        uiBlockTemplate: '   <div id="t3js-ui-block" class="ui-block fbitpagereferences-convertreferencestocopies-progress">' +
            '       <span class="t3js-icon icon icon-size-large icon-state-default icon-spinner-circle-light icon-spin" data-identifier="spinner-circle-light">' +
            '           <span class="icon-markup">' +
            '               <img src="/typo3/sysext/core/Resources/Public/Icons/T3Icons/spinner/spinner-circle-light.svg" width="48" height="48">' +
            '           </span>' +
            '       </span>' +
            '       <div class="progress progress-striped progress-copy" style="width:80%;margin:15px auto">' +
            '           <div class="progress-bar progress-bar-success success"><span class="done"></span></div>' +
            '           <div class="progress-bar progress-bar-danger failed"><span class="failed"></span></div>' +
            '           <span class="all"></span>' +
            '       </div>' +
            '       <div class="progress progress-striped progress-adjust" style="width:80%;margin:15px auto">' +
            '           <div class="progress-bar progress-bar-info success"><span class="done"></span></div>' +
            '           <div class="progress-bar progress-bar-danger failed"><span class="failed"></span></div>' +
            '           <span class="all"></span>' +
            '       </div>' +
            '       <div class="progress progress-striped progress-delete" style="width:80%;margin:15px auto">' +
            '           <div class="progress-bar progress-bar-warning success"><span class="done"></span></div>' +
            '           <div class="progress-bar progress-bar-danger failed"><span class="failed"></span></div>' +
            '           <span class="all"></span>' +
            '       </div>' +
            '   </div>',

        enableCreateContentReferencesButton: function () {
            $('a.fbitpagereferences-createcontentreferences').on('click', function () {
                $.ajax({
                    url: TYPO3.settings.ajaxUrls['create_content_references'],
                    method: 'GET',
                    data: {
                        'pageId': PageReferences.pageId
                    },
                    error: function (xhr, status, error) {
                        // do nothing.
                    },
                    success: function (data, status, xhr) {
                        // reload the module to show the changes.
                        frameElement.src = frameElement.src;
                    }
                });
            });
        },

        enableConvertReferencesToCopiesButton: function () {
            $('a.fbitpagereferences-convertreferencestocopies').on('click', function () {
                $('.module-body').prepend(PageReferences.uiBlockTemplate);

                $.ajax({
                    url: TYPO3.settings.ajaxUrls['get_reference_page_content_data'],
                    method: 'GET',
                    data: {
                        'pageId': PageReferences.pageId
                    },
                    error: function (xhr, status, error) {
                        // do nothing.
                    },
                    success: function (data, status, xhr) {
                        var recordsToCopy = data['recordsToCopy'];
                        var recordsToAdjust = data['recordsToAdjust'];

                        var copyCount = recordsToCopy.length;
                        var adjustCount = recordsToAdjust.length;
                        var deleteCount = recordsToAdjust.length;

                        PageReferences.initializeProgressBar('copy', copyCount);
                        PageReferences.initializeProgressBar('adjust', adjustCount);
                        PageReferences.initializeProgressBar('delete', deleteCount);

                        $.ajaxSetup({
                            async: false
                        });
                        $(document).ajaxError(function (event, jqxhr, settings, thrownError) {
                            if (settings.url.indexOf('paste') > -1) {
                                PageReferences.updateProgressBar('copy', copyCount, false);
                            } else if (settings.url.indexOf('adjust') > -1) {
                                adjustCount--;
                                PageReferences.updateProgressBar('adjust', adjustCount, false);
                            } else if (settings.url.indexOf('delete') > -1) {
                                copyCount--;
                                PageReferences.updateProgressBar('delete', copyCount, false);
                            }
                        });

                        // Three step process:
                        //  1. copy original record.
                        //      This creates the original record's copy as well as all translations.
                        //  2. adjust copied record(s)
                        //      This makes sure that sorting, hidden state and deleted state are respected
                        //  3. delete previously existing content reference records
                        $.each(recordsToCopy, function(index, originalRecordData) {
                            var parameters = {};
                            parameters['cmd'] = {};
                            parameters['data'] = {};

                            parameters['cmd']['tt_content'] = {};
                            parameters['data']['tt_content'] = {};

                            parameters['cmd']['tt_content'][originalRecordData.uid] = {};
                            parameters['cmd']['tt_content'][originalRecordData.uid] = {
                                copy: {
                                    target: parseInt(PageReferences.pageId),
                                    parentAction: 'convertReferencesToCopies',
                                }
                            };
                            require(['TYPO3/CMS/Backend/AjaxDataHandler'], function (DataHandler) {
                                DataHandler.process(parameters).done(function (result) {
                                    console.log('copy', originalRecordData.uid, parameters, result);
                                    if (!result.hasErrors) {
                                        PageReferences.updateProgressBar('copy', copyCount, true);

                                        $.each(result.tce.copyMappingArray_merged.tt_content, function(originalRecordUid, copiedRecordNewUid) {
                                            $.each(recordsToAdjust, function(index, adjustRecordData) {
                                                if (parseInt(adjustRecordData.records) === parseInt(originalRecordUid)) {
                                                    parameters['cmd']['tt_content'] = {};
                                                    parameters['data']['tt_content'] = {};

                                                    if (parseInt(adjustRecordData.deleted) === 1) {
                                                        parameters['cmd']['tt_content'][copiedRecordNewUid] = {
                                                            delete: {
                                                                action: 'delete',
                                                                table: 'tt_content',
                                                                uid: copiedRecordNewUid
                                                            }
                                                        }
                                                    } else {
                                                        parameters['data']['tt_content'][copiedRecordNewUid] = {
                                                            sorting: adjustRecordData.sorting,
                                                            hidden: adjustRecordData.hidden
                                                        };
                                                    }

                                                    require(['TYPO3/CMS/Backend/AjaxDataHandler'], function (DataHandler) {
                                                        DataHandler.process(parameters).done(function (result) {
                                                            console.log('adjusted: ' + (adjustRecordData.deleted ? 'deleted' : 'updated'), copiedRecordNewUid, parameters, result);
                                                            if (!result.hasErrors) {
                                                                PageReferences.updateProgressBar('adjust', adjustCount, true);

                                                                parameters['cmd']['tt_content'] = {};
                                                                parameters['data']['tt_content'] = {};

                                                                parameters['cmd']['tt_content'][adjustRecordData.uid] = {
                                                                    delete: {
                                                                        action: 'delete',
                                                                        table: 'tt_content',
                                                                        uid: adjustRecordData.uid
                                                                    }
                                                                };

                                                                require(['TYPO3/CMS/Backend/AjaxDataHandler'], function (DataHandler) {
                                                                    DataHandler.process(parameters).done(function (result) {
                                                                        console.log('delete', adjustRecordData.uid, parameters, result);
                                                                        if (!result.hasErrors) {
                                                                            PageReferences.updateProgressBar('delete', deleteCount, true);
                                                                        } else {
                                                                            PageReferences.updateProgressBar('delete', deleteCount, false);
                                                                        }
                                                                    });
                                                                });
                                                            } else {
                                                                PageReferences.updateProgressBar('adjust', adjustCount, false);
                                                            }
                                                        });
                                                    });
                                                }
                                            });
                                        });
                                    } else {
                                        PageReferences.updateProgressBar('copy', copyCount, false);
                                    }
                                });
                            });
                        });
                    }
                });
            });
        },

        disableLastReferenceSourcePageField: function () {
            var $lastReferenceSourcePageIdField = $('[name*=tx_fbit_pagereferences_reference_source_page]');
            var lastReferenceSourcePageId = $lastReferenceSourcePageIdField.val();
            $lastReferenceSourcePageIdField.remove();
            $('[data-formengine-input-name*=tx_fbit_pagereferences_reference_source_page]')
                .attr('readonly', 'readonly').attr('disabled', 'disabled').val(lastReferenceSourcePageId);
        },

        enableGoToReferenceSourcePageButton: function () {
            $('a.fbitpagereferences-gotoreferencesourcepage').on('click', function () {
                var referencedPageIdParameter = '%5B' + PageReferences.pageId + '%5D';
                var referenceSourcePageIdParameter = $('[name*=content_from_pid]').val().split('_')[1];
                referenceSourcePageIdParameter = referenceSourcePageIdParameter || $('[data-formengine-input-name*=tx_fbit_pagereferences_reference_source_page]').val();

                var currentFrameSrc = new URL(frameElement.src);
                var newFramePath = $('#EditDocumentController').attr('action').replace(referencedPageIdParameter, '%5B' + referenceSourcePageIdParameter + '%5D');

                var newFrameSrc = new URL(currentFrameSrc.origin + newFramePath);
                newFrameSrc.searchParams.set('returnUrl', frameElement.src);

                frameElement.src = newFrameSrc.toString();
            });
        },

        initializeProgressBar: function (which, allCount) {
            PageReferences.progressCount[which] = {
                success: 0,
                failed: 0
            };

            $('.fbitpagereferences-convertreferencestocopies-progress .progress-' + which + ' .all').text(allCount);
        },

        updateProgressBar: function (which, allCount, success) {
            if (success) {
                PageReferences.progressCount[which].success++;
            } else {
                PageReferences.progressCount[which].failed++;
            }

            $('.fbitpagereferences-convertreferencestocopies-progress .progress-' + which + ' .progress-bar.success')
                .css('width', (PageReferences.progressCount[which].success / allCount * 100).toFixed(2) + '%')
                .attr('aria-valuenow', PageReferences.progressCount[which].success + '/' + allCount);
            $('.fbitpagereferences-convertreferencestocopies-progress .progress-' + which + ' .done').text(
                PageReferences.progressCount[which].success
            );
            $('.fbitpagereferences-convertreferencestocopies-progress .progress-' + which + ' .progress-bar.failed')
                .css('width', (PageReferences.progressCount[which].failed / allCount * 100).toFixed(2) + '%')
                .attr('aria-valuenow', PageReferences.progressCount[which].failed + '/' + allCount);
            $('.fbitpagereferences-convertreferencestocopies-progress .progress-' + which + ' .failed').text(
                (PageReferences.progressCount[which].failed > 0 ? PageReferences.progressCount[which].failed : '')
            );

            $('.fbitpagereferences-convertreferencestocopies-progress .progress-' + which + ' .all').text(
                allCount - PageReferences.progressCount[which].success - PageReferences.progressCount[which].failed
            );

            // reload if all done
            if (PageReferences.progressCount.delete.success + PageReferences.progressCount.delete.failed >= allCount) {
                $.ajaxSetup({
                    async: true
                });
                frameElement.src = frameElement.src;
            }
        }
    };

    PageReferences.init = function () {
        if ($('#PageLayoutController').length > 0 || $('.dblistForm').length > 0) {
            var tableData = $('[data-table]').data();
            this.pageId = tableData['uid'];

            this.enableCreateContentReferencesButton();
            this.enableConvertReferencesToCopiesButton();
        }
        if ($('#EditDocumentController').length > 0) {
            var tableData = $('[data-table]').data();
            this.pageId = tableData['uid'];

            this.disableLastReferenceSourcePageField();
            this.enableGoToReferenceSourcePageButton();
        }
    };

    $(document).ready(PageReferences.init());

    // To let the module be a dependency of another module, we return our object
    return PageReferences;
});
