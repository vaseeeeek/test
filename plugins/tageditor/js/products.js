$(function () {
    var self = {
        index_update_url: '?plugin=tageditor&action=tagIndexUpdate',
        index_update_block: $('<div style="display: none;"></div>'),
        index_update_block_displayed: false,
        index_update_hash_url_product_ids: {},

        init: function () {
            self.ajaxSetup();
        },

        urlRequiresIndexUpdate: function (url) {
            return url.indexOf('?module=products&action=setTypes') > -1
            || url.indexOf('?module=products&action=assignTags') > -1
            || url.indexOf('?module=dialog&action=visibility') > -1
            || url.indexOf('?module=products&action=deleteList') > -1
        },

        indexUpdateComplete: function (content) {
            content.find('h5 .loading').removeClass('loading').addClass('yes');
            content.slideUp('slow', function () {
                $(this).remove();
                self.index_update_block_displayed = false;
            });
        },

        ajaxSetup: function () {
          //auto-update of tag index in product listings
            $.ajaxSetup({
                beforeSend: function () {
                    //request & remember IDs of products matching current hash to submit them for auto index update

                    //return true if no action is required
                    if (this.url === undefined) {
                        return true;
                    }

                    //do nothing if sending POST to plugin's URL
                    if (this.url.indexOf(self.index_update_url) > -1) {
                        return true;
                    }

                    //do nothing if not selected in settings
                    if (tageditor_shop_cloud_auto_update === undefined
                    || !tageditor_shop_cloud_auto_update) {
                        return true;
                    }

                    //do nothing if not POST
                    if (this.type.toUpperCase() != 'POST') {
                        return true;
                    }

                    //do nothing if it's not one of index-affecting URLs
                    if (!self.urlRequiresIndexUpdate(this.url)) {
                        return true;
                    }

                    var post_data = this.data;

                    //do nothing if not all products of a hash have been selected
                    if (post_data.indexOf('hash=') < 0) {
                        return true;
                    }

                    var hash_url = this.url;

                    //clear this hash URL's product IDs
                    self.index_update_hash_url_product_ids[hash_url] = null;

                    $.post(self.index_update_url, post_data + '&get-hash-products=1', function (response) {
                        if (response.hash_product_ids !== undefined) {
                            self.index_update_hash_url_product_ids[hash_url] = response.hash_product_ids;
                        }
                    });
                },

                complete: function () {
                    if (this.url === undefined) {
                        return true;
                    }

                    //do nothing if sending POST to plugin's URL
                    if (this.url.indexOf(self.index_update_url) > -1) {
                        return;
                    }

                    var hash_url = this.url;

                    //update index:
                    // - if enabled in settings
                    // - if POST
                    // - if it's one of index-affecting URLs
                    if (tageditor_shop_cloud_auto_update !== undefined
                    && tageditor_shop_cloud_auto_update
                    && this.type.toUpperCase() == 'POST' &&
                    self.urlRequiresIndexUpdate(this.url)) {
                        //update tag index

                        var post_data = this.data;
                        var product_list_table_container = $('#s-product-list-table-container');
                        var content = $('.tag-index-update-embedded').clone().removeClass('hidden');

                        if (!self.index_update_block_displayed) {
                            self.index_update_block
                                .append(content)
                                .insertBefore(product_list_table_container)
                                .slideDown();
                        }

                        self.index_update_block_displayed = true;

                        var interval_counter = 0;
                        var hash_product_ids_check_interval = setInterval(function () {
                            //emergency stop if interval was not cleared in a normal way
                            if (interval_counter == 100) {
                                clearInterval(hash_product_ids_check_interval);
                                return;
                            }

                            interval_counter++;

                            //skip if product IDs have not been received yet
                            if (post_data.indexOf('hash=') > -1 && !self.index_update_hash_url_product_ids[hash_url]) {
                                return;
                            }

                            //it's not a hash URL or product IDs have been received: clear interval
                            clearInterval(hash_product_ids_check_interval);

                            //it's a hash URL and product IDs have been received: add them to POST data
                            if (post_data.indexOf('hash=') > -1 && self.index_update_hash_url_product_ids[hash_url]) {
                                post_data += '&hash-product-ids=' + self.index_update_hash_url_product_ids[hash_url].join(',');
                            }

                            //it's either a non-hash URL or a hash URL with product IDs in POST data: send POST
                            $.post(self.index_update_url, post_data, function (response) {
                                //skip auto-update if not selected in settings
                                if (response.skip !== undefined && response.skip) {
                                    self.indexUpdateComplete(content);
                                    return;
                                }

                                if (response.long_action) {
                                  //...either start a long action

                                    var processId = undefined;
                                    var step_delay = 1000;

                                    var step = function (delay) {
                                        delay = delay || 1000;
                                        var timer_id = setTimeout(function () {
                                            $.post(
                                                self.index_update_url,
                                                {processId: processId},
                                                function (r) {
                                                    if (!r) {
                                                        step(step_delay);
                                                    } else if (r && r.ready) {
                                                        content.find('.progressbar .progressbar-inner').css({
                                                            width: '100%'
                                                        });

                                                        //complete
                                                        $.post(self.index_update_url, {processId: processId, cleanup: 1}, function (r) {
                                                            self.indexUpdateComplete(content);
                                                        }, 'json');
                                                    } else if (r && r.error) {
                                                        content.find('.errormsg').text(r.error);
                                                    } else {
                                                        if (r && r.progress) {
                                                            var progress = parseFloat(r.progress.replace(/,/, '.'));
                                                            content.find('.progressbar .progressbar-inner').animate({
                                                                'width': progress + '%'
                                                            });
                                                        }
                                                        if (r && r.warning) {
                                                            content.find('.progressbar-description').append('<i class="icon16 exclamation"></i><p>' + r.warning + '</p>');
                                                        }
                                                        step();
                                                    }
                                                },
                                                'json'
                                            ).error(function () {
                                                step(step_delay);
                                            });
                                        }, delay);
                                    };

                                    content.find('.tageditor-tag-index-update-long-action').slideDown('slow', function () {
                                        content.find('.progressbar .progressbar-inner').css('width', '0%');
                                        $.post(self.index_update_url, post_data + '&start-long-action=1', function (r) {
                                            if (r && r.processId) {
                                                processId = r.processId;
                                                step(step_delay);
                                                step();
                                            } else if (r && r.error) {
                                                content.find('.errormsg').text(r.error);
                                            } else {
                                                content.find('.errormsg').text('Server error');
                                            }
                                        }, 'json').error(function () {
                                            content.find('errormsg').text('Server error');
                                        });
                                    });
                                    // end of long action
                                } else {
                                    //... or complete a short action
                                    setTimeout(function () {
                                        self.indexUpdateComplete(content);
                                    }, 1000);
                                 // end of short action
                                }
                            });
                        }, 500);
                    }

                    //restore index update block if product listing area gets refreshed as a result of a POST request
                    if (self.index_update_block_displayed && this.url.indexOf('module=products') > -1 && this.url.indexOf('action=') < 0) {
                        var product_list_table_container = $('#s-product-list-table-container');
                        self.index_update_block.insertBefore(product_list_table_container);
                    }
                }
            });
        }
    };

    self.init();
});