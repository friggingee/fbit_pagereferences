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
            '       <div class="progress progress-striped progress-delete" style="width:80%;margin:15px auto">' +
            '           <div class="progress-bar progress-bar-warning success"><span class="done"></span></div>' +
            '           <div class="progress-bar progress-bar-danger failed"><span class="failed"></span></div>' +
            '           <span class="all"></span>' +
            '       </div>' +
            '       <div class="progress progress-striped progress-copy" style="width:80%;margin:15px auto">' +
            '           <div class="progress-bar progress-bar-success success"><span class="done"></span></div>' +
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
                        var pageContentData = data['pageContentData'];
                        var translatedContentUids = data['translatedContentUids'];

                        var contentCount = pageContentData.length;
                        var translationCount = translatedContentUids.length;
                        var toDeleteCount = contentCount + translationCount;
                        var toCopyCount = contentCount;

                        PageReferences.initializeProgressBar('delete', toDeleteCount);
                        PageReferences.initializeProgressBar('copy', toCopyCount);

                        $(document).ajaxError(function (event, jqxhr, settings, thrownError) {
                            if (settings.url.indexOf('paste') > -1) {
                                PageReferences.updateProgressBar('copy', contentCount, false);
                            } else if (settings.url.indexOf('delete') > -1) {
                                toCopyCount--;
                                PageReferences.updateProgressBar('delete', toCopyCount, false);
                            }
                        });

                        $.each(pageContentData, function (index, recordData) {
                            var parameters = {};
                            parameters['cmd'] = {};
                            parameters['data'] = {};

                            parameters['cmd']['tt_content'] = {};
                            parameters['data']['tt_content'] = {};

                            parameters['cmd']['tt_content'][recordData.uid] = {
                                delete: {
                                    action: 'delete',
                                    table: 'tt_content',
                                    uid: recordData.uid
                                }
                            };

                            require(['TYPO3/CMS/Backend/AjaxDataHandler'], function (DataHandler) {
                                DataHandler.process(parameters).done(function (result) {
                                    if (!result.hasErrors) {
                                        PageReferences.updateProgressBar('delete', toDeleteCount, true);

                                        $.each(recordData.records, function (index, copySourceUid) {
                                            parameters['cmd']['tt_content'] = {};
                                            parameters['cmd']['tt_content'][copySourceUid] = {};
                                            parameters['cmd']['tt_content'][copySourceUid] = {
                                                copy: {
                                                    action: 'paste',
                                                    target: parseInt(PageReferences.pageId),
                                                    update: {
                                                        colPos: parseInt(recordData.colPos),
                                                        sys_language_uid: 0
                                                    },
                                                    parentAction: 'convertReferencesToCopies'
                                                }
                                            };
                                            require(['TYPO3/CMS/Backend/AjaxDataHandler'], function (DataHandler) {
                                                DataHandler.process(parameters).done(function (result) {
                                                    if (!result.hasErrors) {
                                                        PageReferences.updateProgressBar('copy', toCopyCount, true);
                                                    } else {
                                                        PageReferences.updateProgressBar('copy', toCopyCount, false);
                                                    }
                                                });
                                            });
                                        });
                                    } else {
                                        PageReferences.updateProgressBar('delete', toDeleteCount, false);
                                    }
                                });
                            });
                        });


                        $.each(translatedContentUids, function (index, uid) {
                            var parameters = {};
                            parameters['cmd'] = {};
                            parameters['data'] = {};

                            parameters['cmd']['tt_content'] = {};
                            parameters['data']['tt_content'] = {};

                            parameters['cmd']['tt_content'][uid] = {
                                delete: {
                                    action: 'delete',
                                    table: 'tt_content',
                                    uid: uid
                                }
                            };

                            require(['TYPO3/CMS/Backend/AjaxDataHandler'], function (DataHandler) {
                                DataHandler.process(parameters).done(function (result) {
                                    if (!result.hasErrors) {
                                        PageReferences.updateProgressBar('delete', toDeleteCount, true);
                                    } else {
                                        PageReferences.updateProgressBar('delete', toDeleteCount, false);
                                    }
                                });
                            });
                        });
                    }
                });
            });
        },

        disableLastReferenceSourcePageField: function () {
            var lastReferenceSourcePageId = $('[name*=tx_fbit_pagereferences_reference_source_page]').val();
            $('[name*=tx_fbit_pagereferences_reference_source_page]').remove();
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
            if (PageReferences.progressCount.copy.success + PageReferences.progressCount.copy.failed >= allCount) {
                frameElement.src = frameElement.src;
            }
        },

        enableFlashMessageControls: function () {
            $('.typo3-messages .alert-title').each(function (index, item) {
                if (item.innerText === 'Page properties are also used on pages:') {
                    $(item).parent().find('.alert-message').hide('fast');
                    item.outerHTML = '<h4 class="alert-title">' +
                        '<a href="#" class="fbit-accordion-title">' +
                        item.innerText +
                        '</a>' +
                        '</h4>';
                }
            });

            $('.fbit-accordion-title').click(function (event) {
                var affectedMessageContainer = $(event.target).parents('.media-body').find('.alert-message');

                if (affectedMessageContainer.is(':visible')) {
                    affectedMessageContainer.hide('fast');
                } else {
                    affectedMessageContainer.show('fast');
                }
            });
        }
    };

    PageReferences.init = function () {
        if ($('#PageLayoutController').length > 0 || $('.dblistForm').length > 0) {
            var tableData = $('[data-table]').data();
            this.pageId = tableData['uid'];

            this.enableCreateContentReferencesButton();
            this.enableConvertReferencesToCopiesButton();
            this.enableFlashMessageControls();
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
