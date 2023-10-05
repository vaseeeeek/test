/**
 * Created by snark on 2/7/16.
 */

(function ($) {
    $.Invite = {
        localization: null,
        plugin_id: null,
        wa_url: null,
        shop_url: null,
        add: function(e) {
            var self = this;
            var form = $(e).parents('form').serialize();
            //console.log(form);
            $.post(self.shop_url + 'invite/add', form, function(d){
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
                        }
                    }
                }
            }, 'json');
            return false;
        }
    }
})(jQuery);

