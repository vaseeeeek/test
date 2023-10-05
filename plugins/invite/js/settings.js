/**
 * Created by snark on 2/7/16.
 */

(function ($) {
    $.Invite = {
        localization: null,
        plugin_id: null,
        init: function () {
            var self = this;
            self.initTabs();
        },
        initTabs: function () {
            var self = this, tabs = $("#invite-tabs").children(), cnt = $("#invite-tabs-content").children();
            cnt.hide().first().show();
            tabs.click(function (e) {
                if (!$(this).hasClass('selected')) {
                    $(this).addClass("selected").siblings().removeClass("selected");
                    var tab = $("a", this).attr("href");
                    $(tab).fadeToggle().siblings().hide();
                }
                return false;
            });
        },
        add: function(e) {
            var form = $(e).parents('form').serialize();
            //console.log(form);
            $.post('?plugin=invite&action=add', form, function(d){
                $('#invite-add').find('input').each(function() {
                    $(this).removeClass('invite-validation-error');
                });
                $('.invite-validation-status').each(function() {
                    $(this).html('');
                });
                if (d.status == 'ok') {
                    $('#invite-add-button').removeClass('red').removeClass('gray').addClass('green');
                    $('#invite-invitations-table').html(d.data.invitations_table);
                }
                else {
                    for (var key in d.errors[0]) {
                        if (d.errors[0][key]['email']) {
                            $('#invite-validate-email-status').html(d.errors[0][key]['email']);
                            $('#invite-add-button').removeClass('gray').removeClass('green').addClass('red');
                            $('#invite_shop_invite_add_email').addClass('invite-validation-error');
                            //console.log(d.errors[0][key]['email']);
                        }

                    }
                }
            });
            return false;
        },
        confirm: function(code, e) {
            $.post('?plugin=invite&action=confirm', {code: code}, function(d){
                if (d.status == 'ok') {
                    var i = $(e).find('i');
                    if (i.hasClass('yes')) {
                        i.removeClass('yes').addClass('cross');
                    }
                    else {
                        i.removeClass('cross').addClass('yes');
                    }
                }
            });
        }
    }
})(jQuery);

