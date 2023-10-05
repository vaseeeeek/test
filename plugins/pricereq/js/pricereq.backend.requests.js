/**
 * pricereq.backend.requests.js
 * Module pricereqBackendRequests
 */

/*global $, pricereqBackendRequests */

var pricereqBackendRequests = (function () { "use strict";
    //---------------- BEGIN MODULE SCOPE VARIABLES ---------------
    var
        onDoneHandler, onDeleteHandler, initModule;
    //----------------- END MODULE SCOPE VARIABLES ----------------

    //------------------- BEGIN EVENT HANDLERS --------------------
    onDoneHandler = function (event) {
        if(confirm("{_wp('Done?')}")) {
            event.preventDefault();

            var t = $(this);

            var id = t.attr('price-request-id');

            if (id) {
                $.post('?plugin=pricereq&action=requestdone', { 'id': id }, function (response) {
                    if (response.data === true) {
                        var showDone = '{$pricereq_settings.show_done}';
                        var newRequestCountEl = $("#wa-app #mainmenu .tabs").find('li a[href="?plugin=pricereq"] sup');
                        var newRequestCountVal = parseInt(newRequestCountEl.html()) - 1;

                        if (showDone === 'on') {
                            $(".price-request-done[price-request-id='"+id+"']").closest("tr").addClass('gray').find('.human-status').text("{_wp('done')}");
                            t.remove();
                        } else {
                            $(".price-request-done[price-request-id='"+id+"']").closest("tr").hide(600, function () {
                                $(this).show("normal");
                                
                                $(this).remove();

                                if ($('.price-request').length === 0) {
                                    $('#maincontent').html('<div class="block double-padded align-center gray"><strong>{_wp("No requests.")}</strong></div>');
                                }
                            });
                        }

                        if (newRequestCountVal === 0) {
                            newRequestCountEl.remove();
                        } else {
                            newRequestCountEl.html(newRequestCountVal);
                        }
                    }
                }, "json");
            }

        }
    };

    onDeleteHandler = function (event) {
        if(confirm("{_wp('Delete?')}")) {

            event.preventDefault();

            var t = $(this);

            var id = t.attr('price-request-id');

            if (id) {
                $.post('?plugin=pricereq&action=requestdelete', { 'id': id }, function (response) {
                    if (response.data === true) {
                        var newRequestCountEl = $("#wa-app #mainmenu .tabs").find('li a[href="?plugin=pricereq"] sup');
                        var newRequestCountVal = parseInt(newRequestCountEl.html()) - 1;
                        
                        $(".price-request-delete[price-request-id='"+id+"']").closest("tr").hide(600, function () {
                            $(this).show("normal");
                            
                            $(this).remove();

                            if ($('.price-request').length === 0) {
                                $('#maincontent').html('<div class="block double-padded align-center gray"><strong>{_wp("No requests.")}</strong></div>');
                            }
                        });

                        if (!t.closest('tr').hasClass('gray')) {
                            if (newRequestCountVal === 0) {
                                newRequestCountEl.remove();
                            } else {
                                newRequestCountEl.html(newRequestCountVal);
                            }
                        }
                    }
                }, "json");
            }

        }
    };
    //------------------- END EVENT HANDLERS ----------------------

    //------------------- BEGIN PUBLIC METHODS --------------------
    initModule = function () {
        $('.price-request-done').on('click', onDoneHandler);

        $('.price-request-delete').on('click', onDeleteHandler);
    };

    return {
        initModule: initModule
    };
    //------------------- END PUBLIC METHODS ----------------------
}());