(function($) {
    'use sctrict'
    $.pluginsBack = {
        pluginName:'clicklite',
        codeMirrorArr:'',
        init: function() {
            this.initCodeMirror();
            this.initSwitcher();
            this.initSave();
            this.initReset();
            this.initTab();
        },
        initCodeMirror: function() {
            var that = this;
            that.CodeMirrorArr = {};
            $('.codemirror-area').each(function () {
                var th = $(this);
                var editor = CodeMirror.fromTextArea(this, {
                    mode: th.data('mode'),
                    tabMode: 'indent',
                    height: 'dynamic',
                    lineWrapping: true,
                    onChange: function(cm) {
                        th.val(cm.getValue());
                    }
                });
                that.CodeMirrorArr[th.data('object')] = editor;
            });

            $('.plugins__editTextarea').click(function(event) {
                event.preventDefault();
                var parent = $(this).parent();

                if(parent.find('.CodeMirror').length) {
                    parent.find('.CodeMirror').slideToggle();
                }

                return false;
            });
        },
        initSwitcher: function() {
            $('.switcher').iButton({
                labelOn: "", labelOff: "", className: 'mini'
            }).change(function() {
                var onLabelSelector = '#' + this.id + '-on-label',
                    offLabelSelector = '#' + this.id + '-off-label';
                var additinalField = $(this).closest('.ibutton-checkbox').next('.onopen');
                if (!this.checked) {
                    if (additinalField.length) {
                        additinalField.hide();
                    }
                    $(onLabelSelector).addClass('unselected');
                    $(offLabelSelector).removeClass('unselected');
                } else {
                    if (additinalField.length) {
                        additinalField.css('display', 'inline-block');
                    }
                    $(onLabelSelector).removeClass('unselected');
                    $(offLabelSelector).addClass('unselected');
                }
            });
        },
        initSave: function() {
            var that = this;

            $('.plugins__save').click(function() {
                var btn = $(this);
                var form = btn.closest('form');
                var errormsg = form.find('.errormsg');
                errormsg.text('');
                btn.next("i.icon16").remove();
                btn.attr('disabled', 'disabled').after('<i style="vertical-align:middle" class="icon16 loading"></i>');
                $.ajax({
                    url: "?plugin="+that.pluginName+"&module=settings&action=save",
                    data: form.serializeArray(),
                    dataType: "json",
                    type: "post",
                    success: function(response) {
                        btn.removeAttr('disabled').next().remove();
                        if (typeof response.errors != 'undefined') {
                            if (typeof response.errors.messages != 'undefined') {
                                $.each(response.errors.messages, function(i, v) {
                                    errormsg.append(v + "<br />");
                                });
                            }
                        } else if (response.status == 'ok' && response.data) {
                            btn.after('<i style="vertical-align:middle" class="icon16 yes"></i>');
                            viewLink();
                        } else {
                            btn.after('<i style="vertical-align:middle" class="icon16 no"></i>');
                        }
                    },
                    error: function() {
                        errormsg.text($_('Что-то не так.'));
                        btn.removeAttr('disabled').next().remove();
                        btn.after('<i style="vertical-align:middle" class="icon16 no"></i>');
                    }
                });
                return false;
            });

            function viewLink() {
                var pluginLink = $("#wa-app #mainmenu .tabs").find('li a[href="?plugin='+that.pluginName+'"]').closest('li');
                if($(".plugins input[name='shop_plugins[status]']").prop('checked')) {
                    if(!pluginLink.length)
                        $("#wa-app #mainmenu .tabs li:last")
                            .before('<li class="no-tab"><a href="?plugin='+that.pluginName+'">Купить (lite)</a></li>');
                } else {
                    pluginLink.remove();
                }
            }
        },
        initReset: function() {
            var that = this;

            $('.plugins__reset').click(function(event) {
                event.preventDefault();
                var th = $(this);
                var templ = th.data('templ');
                var reset = confirm("Вы действительно хотите сбросить?");

                if(reset) {
                    $.ajax({
                        url: "?plugin="+that.pluginName+"&module=settings&action=reset",
                        data: { 'templ':templ },
                        dataType: "json",
                        type: "post",
                        success: function(response) {
                            if(response.data.status) {
                                th.next().val(response.data.templ_original);
                                that.CodeMirrorArr[templ].setValue(response.data.templ_original);
                            } else {
                                alert(response.data.error);
                            }
                        }
                    });
                }

                return false;
            });
        },
        initTab: function() {
            var that = this;
            var pb = $('.plugins');
            pb.find('.tab-content .block').hide().first().show();

            pb.find('.tabs>li>a').click(function (e) {
                e.preventDefault();
                var parent = $(this).closest('.tabs');
                parent.find('li').removeClass('selected');
                $(this).parent().addClass('selected');

                parent.next().find('.block').hide().eq($(this).parent().index()).show();

                $('.CodeMirror').show();
                $('.CodeMirror').each(function(i, el){
                    el.CodeMirror.refresh();
                });
                $('.CodeMirror').not('.CodeMirrorBlock .CodeMirror').hide();
            });
        }
    }

    $.pluginsBack.init();
})(jQuery);