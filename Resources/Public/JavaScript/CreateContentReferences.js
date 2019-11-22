define(['jquery'], function($) {
    var CreateContentReferences = {
        pageId: null,

        enableCreateContentReferencesButton: function() {
            $('a.fbitpagereferences-createcontentreferences').on('click', function() {
                $.ajax({
                    url: TYPO3.settings.ajaxUrls['create_content_references'],
                    method: 'GET',
                    data: {
                        'pageId': CreateContentReferences.pageId
                    },
                    error: function(xhr, status, error) {
                        // do nothing.
                    },
                    success: function(data, status, xhr) {
                        // reload the module to show the changes.
                        frameElement.src = frameElement.src;
                    }
                });
            });
        },

        enableConvertReferencesToCopiesButton: function() {
            $('a.fbitpagereferences-convertreferencestocopies').on('click', function() {
                $.ajax({
                    url: TYPO3.settings.ajaxUrls['get_mount_page_content_data'],
                    method: 'GET',
                    data: {
                        'pageId': CreateContentReferences.pageId
                    },
                    error: function(xhr, status, error) {
                        // do nothing.
                    },
                    success: function(data, status, xhr) {
                        var pageContentData = data['pageContentData'];
                        var translatedContentUids = data['translatedContentUids'];
                        console.log('got content data', pageContentData);

                        var recordsDeleted = [];
                        var recordsCopied = [];

                        $.each(pageContentData, function(index, recordData) {
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

                            pageContentData[index]['numOfRecordsToCopy'] = recordData.length;

                            require(['TYPO3/CMS/Backend/AjaxDataHandler'], function (DataHandler) {
                                DataHandler.process(parameters).done(function (result) {

                                    console.log('deleted', recordData.uid);
                                    recordsDeleted.push(recordData.uid);

                                    if (!result.hasErrors) {
                                        $.each(recordData.records, function (index, copySourceUid) {
                                            parameters['cmd']['tt_content'] = {};
                                            parameters['cmd']['tt_content'][copySourceUid] = {};
                                            parameters['cmd']['tt_content'][copySourceUid] = {
                                                copy: {
                                                    action: 'paste',
                                                    target: parseInt(CreateContentReferences.pageId),
                                                    update: {
                                                        colPos: parseInt(recordData.colPos),
                                                        sys_language_uid: 0
                                                    },
                                                    parentAction: 'convertReferencesToCopies'
                                                }
                                            };
                                            require(['TYPO3/CMS/Backend/AjaxDataHandler'], function (DataHandler) {
                                                DataHandler.process(parameters).done(function (result) {

                                                    console.log('copied', copySourceUid);
                                                    recordsCopied.push(copySourceUid);

                                                    if (!result.hasErrors) {
                                                    }
                                                });
                                            });
                                        });
                                    }
                                });
                            });
                        });


                        $.each(translatedContentUids, function(index, uid) {
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
                                    console.log('deleted translation', uid);
                                });
                            });
                        });

                        console.log('all done.');
                    }
                });
            });
        }
    };

    CreateContentReferences.init = function() {
        if ($('#PageLayoutController').length > 0 || $('.dblistForm').length > 0) {
            var tableData = $('[data-table]').data();
            this.pageId = tableData['uid'];

            this.enableCreateContentReferencesButton();
            this.enableConvertReferencesToCopiesButton();
        }
    };

    $(document).ready(CreateContentReferences.init());

    // To let the module be a dependency of another module, we return our object
    return CreateContentReferences;
});
