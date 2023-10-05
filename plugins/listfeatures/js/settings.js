$(function () {

    $.wa.listfeatures = {
        init: function () {
            this.initEvents();
            this.loadSettlementOptions();
        },

        loadSettlementOptions: function () {
            var self = this;
            $('.listfeatures [name="settlement"]').after('<i class="icon16 loading"></i>');
            $.post(
                '?plugin=listfeatures&module=settings&action=settlementOptions',
                {settlement: self.getSelectedSettlement()},
                function (html) {
                    $('.listfeatures .field.sets .value').empty();
                    $('.listfeatures .loading').remove();
                    $('.listfeatures .field.sets .value').append(html);
                    self.loadSetOptions();
                }
            );
        },

        loadSetOptions: function (settlement) {
            var self = this;
            $('.listfeatures .field.settlement-sets .value').append('<i class="icon16 loading"></i>');
            var set_id = self.getSelectedSetId();
            $.post(
                '?plugin=listfeatures&module=settings&action=setOptions',
                {settlement: self.getSelectedSettlement(), set_id: set_id},
                function (html) {
                    $('.listfeatures .field.set-options').remove();
                    $('.listfeatures .loading').remove();
                    $('.listfeatures .field.sets').after(html);

                    self.customizeFeaturesGroupboxData();
                    self.customizeFeaturesGroupboxNative();

                    self.updateFeatureEditIcons($('.listfeatures .field.features .value :checkbox')
                        .not('[name="features[features]"]'));
                    self.customizeTemplateSelect();

                    if (set_id) {
                        $('.add-set').removeClass('hidden');
                    }
                }
            );
        },

        getSelectedSettlement: function () {
            return $('.listfeatures [name="settlement"]').val();
        },

        getSelectedSetId: function () {
            return $('.listfeatures .field.sets .set.selected').attr('data-set-id');
        },

        updateFeatureEditIcons: function (checkboxes) {
            checkboxes.each(function () {
                var checkbox = $(this);
                if (checkbox.is(':checked')) {
                    var li = checkbox.closest('li');
                    var id_match = checkbox.attr('name').match(/^[^\[]+\[([^\]]+)\]$/);
                    $('<i class="icon10 edit left-margin"></i>')
                        .attr('data-feature-id', id_match[1])
                        .attr('data-feature-name', li.find('label').text())
                        .appendTo(li);
                } else {
                    checkbox.closest('li').find('.edit').remove();
                }
            });
        },

        customizeFeaturesGroupbox: function (container) {
            if (container.find(':checkbox').length > 0) {
                var items = container.html().split('<br>');
                var ul = $('<ul></ul>');
                $.each(items, function (i) {
                    var li = $('<li></li>');
                    $(this.toString()).each(function () {
                        li.append($(this));
                    });
                    ul.append(li);
                });
                container.empty().append(ul);
            }
        },

        customizeFeaturesGroupboxData: function () {
            var container = $('.field.features.data .value');
            this.customizeFeaturesGroupbox(container);
            container.find(':checkbox').each(function (i) {
                var checkbox = $(this);
                var id_match = checkbox.attr('name').match(/^[^\[]+\[([^\]]+)\]$/);
                checkbox.closest('li').prepend($('<input type="hidden" name="data_sort[' + id_match[1] + ']">').val(i));
                checkbox.closest('li').prepend($('<i class="icon16 sort"></i>'));
            });
            var ul = container.find('ul');
            ul.sortable({
                distance: 5,
                handle: '.sort',
                cursor: 'move',
                update: function () {
                    ul.children('li').each(function (i) {
                        $(this).find(':hidden').val(i);
                    });
                }
            }).find(':not(:input)').disableSelection();
        },

        customizeFeaturesGroupboxNative: function () {
            this.customizeFeaturesGroupbox($('.field.features.native .value'));
        },

        customizeTemplateSelect: function () {
            var container = $('.listfeatures .field.template .value');
            $('<a href="" class="inline-link small left-margin view-template"><b><i>'
                + $.wa.locale['view template'] + '</i></b></a>').insertAfter(container.find('select'));
            this.rememberTemplate();
        },

        rememberTemplate: function () {
            var link = $('.listfeatures .field.template .view-template');
            var select = $('.listfeatures .field.template select');
            link.attr('data-template', select.find(':selected').val());
        },

        sortTemplates: function () {
            var select = $('.listfeatures .field.template select');
            var templates = [];

            var count = select.children('option').length - 2;
            if (count > 1) {
                var template_options = select.children('option').filter(function () {
                    return parseInt($(this).val()) > 0;
                });

                template_options.each(function () {
                    templates.push($(this).val());
                });

                templates.sort();

                $.each(templates, function (i) {
                    var template = this.toString();
                    template_options.eq(i)
                        .val(template)
                        .text($.wa.locale['Template %s'].replace('%s', template));
                });
            }
        },

        initEvents: function () {
            var self = this;

            //show/hide hint
            $('.listfeatures').on('click', '.show-hint', function () {
                var hint = $(this).closest('.hint-block').find('.hidden-hint');
                if (hint.is(':visible')) {
                    hint.slideUp().addClass('hidden');
                } else {
                    hint.slideDown().removeClass('hidden');
                }
                return false;
            });

           //add edit link for checked feature checkboxes of existing set, except for 'Features'
            $('.listfeatures').on('change', '.field.features .value :checkbox', function () {
                if (self.getSelectedSetId() && !$(this).is('[name="features[features]"]')) {
                    self.updateFeatureEditIcons($(this));
                }
            });

            //auto check/uncheck 'Features' checkbox
            $('.listfeatures').on('change', '.field.features.native .value :checkbox', function () {
                var features_checkbox = $('.listfeatures .field.features.data .value :checkbox[name="features[features]"]');
                if ($(this).closest('.field .value').find(':checked').length > 0) {
                    features_checkbox.attr('checked', true);
                } else {
                    features_checkbox.attr('checked', false);
                }
            });

            //check/uncheck all feature checkboxes when 'Features' data checkbox is checked/unchecked
            $('.listfeatures').on('change', '.field.features.data :checkbox[name="features[features]"]', function () {
                var feature_checkboxes = $('.listfeatures .field.features.native .value :checkbox');
                if ($(this).is(':checked')) {
                    feature_checkboxes.attr('checked', true);
                } else {
                    feature_checkboxes.attr('checked', false);
                }
                if (self.getSelectedSetId()) {
                    self.updateFeatureEditIcons(feature_checkboxes);
                }
            });

            //edit feature options
            $('.listfeatures').on('click', '.field.features .value .edit', function () {
                var edit_link = $(this);
                var feature_id = edit_link.attr('data-feature-id');
                var feature_name = edit_link.attr('data-feature-name');
                if (feature_id == 'skus') {
                    var title = $.wa.locale['Edit settings for SKUs'];
                } else if (feature_id == 'tags') {
                    var title = $.wa.locale['Edit settings for tags'];
                } else if (feature_id == 'categories') {
                    var title = $.wa.locale['Edit settings for categories'];
                } else {
                    var title = $.wa.locale['Edit settings for feature %s'].replace('%s', '<span class="gray">' + feature_name + '</span>');
                }
                $('<p class="content"><i class="icon16 loading"></i></p>'
                    + '<input type="hidden" name="settlement" value="' + self.getSelectedSettlement() + '">'
                    + '<input type="hidden" name="set_id" value="' + self.getSelectedSetId() + '">'
                    + '<input type="hidden" name="feature_id" value="' + feature_id + '">').waDialog(
                        {
                            title: title,
                            buttons: '<input type="submit" value="' + $.wa.locale['Save'] + '" class="button green">' +
                               ' <a href="" class="cancel">' + $.wa.locale['cancel'] + '</a>',
                            onLoad: function () {
                                var dialog = $(this);
                                $.post(
                                    '?plugin=listfeatures&module=settings&action=featureOptions',
                                    {settlement: self.getSelectedSettlement(),
                                        set_id: self.getSelectedSetId(),
                                        feature_id: edit_link.attr('data-feature-id'),
                                        feature_name: feature_name},
                                    function (html) {
                                        dialog.find('.content').empty().html(html);
                                    }
                                );
                            },
                            onSubmit: function () {
                                var dialog = $(this);
                                dialog.find('.cancel').after('<i class="icon16 loading"></i>');
                                $.post(
                                    '?plugin=listfeatures&module=settings&action=featureOptionsSave',
                                    $(this).serialize(),
                                    function () {
                                        dialog.trigger('close');
                                    }
                                );
                                return false;
                            },
                            onClose: function () {
                                $(this).remove();
                            }
                        }
                    );
            });

            //update sets for newly selected settlement
            $('.listfeatures').on('change', 'select[name="settlement"]', function () {
                $('.listfeatures .delete-set-link').addClass('hidden');
                self.loadSettlementOptions();
            });

            //show selected set options
            $('.listfeatures').on('click', '.field.sets .set', function () {
                var set_button = $(this);
                if (!set_button.hasClass('selected')) {
                    $('.listfeatures .field.sets .set').removeClass('selected');
                    set_button.addClass('selected');
                    var selected_set_id = self.getSelectedSetId();
                    var last_set_id = $('.listfeatures .set:last').attr('data-set-id');
                    if (selected_set_id == last_set_id && selected_set_id > 1) {
                        $('.listfeatures .delete-set-link').removeClass('hidden');
                    } else {
                        $('.listfeatures .delete-set-link').addClass('hidden');
                    }
                    $('.listfeatures .cancel-new-set-link').addClass('hidden');
                    //remove new set button, if set was not saved
                    if (!last_set_id) {
                        $('.listfeatures .set:last').remove();
                    }
                    self.loadSetOptions();
                }
            });

            //add new set
            $('.listfeatures').on('click', '.add-set-link', function () {
                if (self.getSelectedSetId()) {
                    $('.listfeatures .set').removeClass('selected');
                    $('.listfeatures .set:last')
                        .clone()
                        .text('...')
                        .attr('data-set-id', '')
                        .addClass('selected')
                        .appendTo($('.listfeatures .settlement-sets'));
                    $('.listfeatures .cancel-new-set-link').removeClass('hidden');
                    $('.listfeatures .delete-set-link').addClass('hidden');
                    $('.listfeatures .field.set-options .value :checkbox').attr('checked', false);
                    $('.listfeatures .edit').remove();
                }
            });

            //cancel creation of new set
            $('.listfeatures').on('click', '.cancel-new-set-link', function () {
                $('.listfeatures .set:last').remove();
                $('.listfeatures .set:first').click();
            });

            //delete set
            $('.listfeatures').on('click', '.delete-set-link', function () {
                $().waDialog({
                    title: $.wa.locale['Delete set %s?'].replace('%s', self.getSelectedSetId()),
                    buttons: '<input type="submit" value="' + $.wa.locale['Delete'] + '" class="button green">' +
                        ' <a href="" class="cancel">' + $.wa.locale['cancel'] + '</a>',
                    width: '500px',
                    height: '200px',
                    onSubmit: function () {
                        var dialog = $(this);
                        dialog.find('.cancel').after('<i class="icon16 loading"></i>');
                        $.post(
                            '?plugin=listfeatures&module=settings&action=setDelete',
                            {settlement: self.getSelectedSettlement(),
                                set_id:     self.getSelectedSetId()},
                            function (response) {
                                dialog.remove('.loading');
                                $('.listfeatures .set.selected').remove();
                                $('.listfeatures .set:first').click();
                                dialog.trigger('close');
                            },
                            'json'
                        );
                        return false;
                    },
                    onClose: function () {
                        $(this).remove();
                    }
                });
            });

            //save settings
            $('.listfeatures form.settings').on('submit', function () {
                var form = $(this);
                form.find('.set-id-value').val(self.getSelectedSetId());
                var form_data = form.serialize();
                var buttons = $('.listfeatures .field.buttons .value');
                buttons.append('<i class="icon16 loading"></i>');
                var selected_set_button = $('.listfeatures .field.sets .set.selected');
                $.post(
                    '?plugin=listfeatures&module=settings&action=save',
                    form_data,
                    function (response) {
                        form.find('.loading').remove();
                        $('.listfeatures .cancel-new-set-link').addClass('hidden');
                        if (response.data.new_set_id !== undefined) {
                            var new_set_id = response.data.new_set_id;
                            selected_set_button.text(new_set_id).attr('data-set-id', new_set_id);
                            self.updateFeatureEditIcons($('.listfeatures .field.features .value :checkbox').not('[name="features[features]"]'));
                        }
                        var selected_set_id = self.getSelectedSetId();
                        if (selected_set_id == $('.listfeatures .set:last').attr('data-set-id')
                        && selected_set_id > 1) {
                            $('.listfeatures .delete-set-link').removeClass('hidden');
                        } else {
                            $('.listfeatures .delete-set-link').addClass('hidden');
                        }
                        $('.add-set').removeClass('hidden');

                        self.rememberTemplate();

                        $('<span class="s-mgs-after-button"><i class="icon16 yes"></i></span>')
                            .appendTo(buttons)
                            .animate({opacity: 0}, 2000, function () {
                                $(this).remove();
                            });
                    },
                    'json'
                );
                return false;
            });

            //view selected or add new template
            $('.listfeatures').on('click', '.field.template .view-template', function () {
                var link = $(this);
                var select = link.closest('.field').find('select');
                var template = select.find(':selected').val();
                var template_name = select.find(':selected').text();

                $('<div class="error red hidden"></div>'
                    + '<i class="icon16 loading"></i>'
                    + '<input type="hidden" name="template" value="' + template + '">'
                    + ((!template) ?
                        '<p class="gray">' + $.wa.locale['Edit this default template as you need.'] + '</p>' : '')
                    + '<textarea id="template-content" class="hidden" name="source"></textarea>').waDialog(
                        {
                            title: (template) ? template_name : $.wa.locale['New template'],
                            buttons: template == 'default' ?
                                '<input type="button" value="' + $.wa.locale['Close'] + '" class="button gray cancel">' :
                                (template ? '<a href="" class="red float-right delete-template">' + $.wa.locale['delete template'] + '</a>' : '')
                                    + '<input type="submit" value="' + $.wa.locale['Save'] + '" class="button green" disabled>'
                                    + ' <a href="" class="cancel">' + $.wa.locale['close'] + '</a>',
                            className: 'listfeatures',
                            onLoad: function () {
                                var dialog = $(this);

                                //attach "delete template" event handler
                                dialog.on('click', '.delete-template', function () {
                                    var delete_link = $(this);
                                    $('<i class="icon16 loading right-margin middle"></i>').prependTo(delete_link);
                                    $.post('?plugin=listfeatures&module=settings&action=templateDelete', dialog.find('form').serialize(), function (response) {
                                        dialog.find('.loading').remove();
                                        if (response.status == 'fail') {
                                            var error = dialog.find('.error');
                                            error.removeClass('hidden').html(response.errors.join('<br>'));
                                            setTimeout(function () {
                                                error.animate({height: 0, opacity: 0}, 1000, function () {
                                                    $(this).css({height: '', opacity: ''}).empty().addClass('hidden');
                                                });
                                            }, 3000);
                                        } else {
                                            select.find('[value="' + template + '"]').remove();
                                            select.find('[value="default"]').attr('selected', true);
                                            dialog.trigger('close');
                                        }
                                    }, 'json');
                                    return false;
                                });

                                //load template
                                $.post('?plugin=listfeatures&module=settings&action=templateView', {template: template}, function (html) {
                                    dialog.find('.loading').remove();
                                    dialog.find('#template-content').html(html);
                                    waEditorAceInit({
                                        'id':   'template-content',
                                        'type': 'html'
                                    });
                                    dialog.find(':submit').attr('disabled', false);
                                });
                            },
                            onSubmit: function (d) {
                                $(this).find('.cancel').after('<i class="icon16 loading middle"></i>');
                                $(this).find('#template-content').val(wa_editor.getValue());
                                $.post('?plugin=listfeatures&module=settings&action=templateSave', $(this).serialize(), function (response) {
                                    if (!template) {
                                        var select = $('.listfeatures .field.template select');
                                        select.find('option:selected').attr('selected', false);
                                        var new_option = $('<option></option>');
                                        new_option
                                            .val(response.data.new_template)
                                            .text($.wa.locale['Template %s'].replace('%s', response.data.new_template))
                                            .insertBefore(select.find('option:last'))
                                            .attr('selected', true);
                                        self.rememberTemplate();
                                        self.sortTemplates();
                                    }
                                    d.trigger('close');
                                }, 'json');
                                return false;
                            },
                            onClose: function () {
                                if (!template) {
                                    var select = link.closest('.value').find('select');
                                    select.find(':selected').attr('selected', false);
                                    var saved_template = link.attr('data-template');
                                    select.find('[value="' + saved_template + '"]').attr('selected', true);
                                }
                                $(this).remove();
                            }
                        }
                    );
                return false;
            });

            //auto-open template-adding dialog
            $('.listfeatures').on('change', '.field.template select', function () {
                if ($(this).find(':selected').val().length < 1) {
                    $('.listfeatures .set-options.template .view-template').click();
                }
            });
        }
    };

    $.wa.listfeatures.init();

});