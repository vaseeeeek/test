(function($) {
    $.product_syrattachments = {

        /**
         * {Number}
         */
        product_id: 0,

        tail: null,
        
        counter: $("li.syrattachments span.hint"),

        progressbar: {
            element : null,
            update: function(value) {
                if(this.element.prop("tagName") === 'PROGRESS') {
                    this.element.val(value); this.element.text(value.toString()+'%');
                } else {
                    $(".progressbar-inner", element).css("width", value.toString()+'%');
                }
            }
        },

        /**
         * {Object}
         */
        options: {},

        init: function(options) {
            
            $.shop.trace('$.product_syrattachments.init', 'Init');
            
            this.options = options;
            this.product_id = parseInt(this.options.product_id, 10) || 0;
            
            var tab=$("#s-product-edit-menu .syrattachments");
            tab.find(".hint").text(options.count || (options.attachments && options.attachments.length) || 0);
            $("#s-product-edit-forms .s-product-form.syrattachments").addClass('ajax');
            
            this.initAttachmentsList(options);
            this.initAttachDeleteAction();
            this.initProgressBar();
            this.initListEditable();
            
            $.product.editTabSyrattachmentsBlur = function(path){
                $("#s-plugin-syrattach-fileupload").fileupload('destroy');
            };
            
            $.product.editTabSyrattachmentsAction = function(path) {
                $.shop.trace('$.product_syrattachments.tail', $.product_syrattachments.tail);
                if($.product_syrattachments.tail !== null) {
                    var url = '?plugin=syrattach&module=attachments&id=' + path.id;
                    if(path.tail) {
                        url += '&param[]=' + path.tail;
                    }
                    
                    $.get(url, function(html){
                        $("#s-product-edit-forms .s-product-form.syrattachments").html(html);
                    });
                }
                $.product_syrattachments.tail = path.tail;
            };
        },
        
        initAttachmentsList: function(options) {
            this.attachments_list = $(options.attachments_list || '#s-plugin-syrattach-product-files-list');
            this.attachments_list.html(tmpl('template-syrattach-attachments', {
                attachments: options.attachments,
                formatFileSize: this._formatFileSize,
                placeholder: options.placeholder
            }));
        },
        
        initProgressBar: function() {
            // Modern browser
            if(document.createElement('progress').max !== undefined) {
                $("#s-plugin-syrattach-upload-progress .progressbar").replaceWith('<progress>0%</progress>');
                this.progressbar.element = $("#s-plugin-syrattach-upload-progress progress");
                this.progressbar.element.attr("max", 100);
            } else {
                this.progressbar.element = $("#s-plugin-syrattach-upload-progress .progressbar");
            }
            this.progressbar.update(0);
        },
        
        initAttachDeleteAction: function() {
            $("#s-plugin-syrattach-product-files-list").on("click", ".s-plugin-syrattach-delete-action", function(){
                $.shop.trace("Delete Action", $(this));
                var id=$(this).data('id');
                var list_item = $(this).closest("li");
                $.post("?plugin=syrattach&module=attachments&action=delete", {
                    '_csrf' : $("input[name=_csrf]").val(),
                    'id':id
                }, function(data){
                    if(data.status === 'ok') {
                        list_item.slideUp(500, function(){
                            list_item.remove();
                            var cnt = parseInt($.product_syrattachments.counter.text());
                            cnt--;
                            if(cnt >0) {
                                $.product_syrattachments.counter.text(cnt);
                            } else {
                                $.product_syrattachments.counter.text(' ');
                            }
                        });
                    }
                }, 'json');
            });
        },
        
        initListEditable: function() {
            this.attachments_list.off('click', '.editable').on('click', '.editable', function() {
                $(this).inlineEditable({
                    inputType: 'textarea',
                    makeReadableBy: ['esc'],
                    updateBy: ['ctrl+enter'],
                    placeholderClass: 'gray',
                    placeholder: $.product_syrattachments.options.placeholder,
                    minSize: {
                        height: 40
                    },
                    allowEmpty: true,
                    beforeMakeEditable: function(input) {
                        var self = $(this);

                        input.css({
                            'font-size': self.css('font-size'),
                            'line-height': self.css('line-height')
                        }).width(
                            //self.parents('li:first').find('img').width()
                            '95%'
                        );

                        var button_id = this.id + '-button';
                        var button = $('#' + button_id);
                        if (!button.length) {
                            input.after('<br><input type="button" id="' + button_id + '" value="' + $_('Save') + '"> <em class="hint" id="' + this.id + '-hint">Ctrl+Enter</em>');
                            $('#' + button_id).click(function() {
                                self.trigger('readable');
                            });
                        }
                        $('#'+this.id+'-hint').show();
                        button.show();
                    },
                    afterBackReadable: function(input, data) {
                        var self = $(this);
                        var attachment_id = parseInt(self.parents('li:first').attr('data-attachment-id'), 10);
                        var value = $(input).val();
                        var prefix = '#'+this.id+'-';

                        $(prefix + 'button').hide();
                        $(prefix + 'hint').hide();
                        if (data.changed) {
                            $.products.jsonPost('?plugin=syrattach&module=attachments&action=descriptionsave', {
                                id: attachment_id,
                                data: {
                                    description: value
                                }
                            });
                        }
                    }
                }).trigger('editable');
            });
        },

        _formatFileSize: function (bytes) {

            if (typeof bytes === 'string') {
                bytes = parseInt(bytes);
            }

            if (typeof bytes !== 'number') {
                return '';
            }
            if (bytes >= 1000000000) {
                return (bytes / 1000000000).toFixed(2) + ' GB';
            }
            if (bytes >= 1000000) {
                return (bytes / 1000000).toFixed(2) + ' MB';
            }
            return (bytes / 1000).toFixed(2) + ' KB';
        }
    };

    var syrattachupload = $("#s-plugin-syrattach-fileupload");

    syrattachupload.fileupload({
        formData: $("#s-plugin-syrattach-fileupload input[type=hidden]").serializeArray(),
        dropZone: $(".s-plugin-syrattach-upload-dropzone"),
        maxFileSize: $.product_syrattachments.options.maxFileSize,
        start: function (e) {
            $.product_syrattachments.progressbar.update(0);
            $.product_syrattachments.progressbar.element.parent().show();
            $.shop.trace('File upload starts', '');
        },
        stop: function (e) {
            $.product_syrattachments.progressbar.element.parent().hide();
            $.shop.trace('File upload ends', '');
            $.get("?plugin=syrattach&module=attachments&action=list", {product_id: $.product_syrattachments.product_id})
                .success(function (data) {
                    if (data.status == 'ok') {
                        $.product_syrattachments.options.attachments = data.response.attachments;
                        $.product_syrattachments.attachments_list.html(tmpl('template-syrattach-attachments', {
                            attachments: $.product_syrattachments.options.attachments,
                            formatFileSize: $.product_syrattachments._formatFileSize,
                            placeholder: $.product_syrattachments.options.placeholder
                        }));
                        $.product_syrattachments.counter.text(data.response.count);
                    }
                });
        },
        fail: function (e, data) {
            $.shop.trace('Fail called', data);
        },
        done: function (e, data) {
            $.shop.trace('Done called', data);
        }
    });
})(jQuery);