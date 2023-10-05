(function($){
    $.shopSaleskuPluginBackendSettings = {
        default_action: '?plugin=salesku&action=settings&storefront=general',
        action:'',
        init: function() {
            this.load(this.default_action);
        },
        loadAction: function (obj) {
            var link = '?plugin=salesku&action=settings&storefront='+$(obj).val();
            this.load(link);
            return false;
        },
        load: function (url,  call_function) {
            $("#salesku_plugin-content").html('<div class="block triple-padded"><i class="icon16 loading"></i>Loading...</div>');
            return  $.get(url, function (result) {
                $("#salesku_plugin-content").html(result);
                if (typeof call_function === 'function') {
                    call_function.call(this);
                }
            });
        },
        createThemesTemplates: function (obj) {
            $.get(obj.attr('href'), function(response) {
               if(response.status == 'ok') {
                   $('.salesku_plugin-templates-notice').remove();
                   var fd = $('#plugins-settings-form');
                   $.shopSaleskuPluginBackendSettings.save(fd);
                   setTimeout(function () {
                       $('select[name=salesku_storefront]').trigger('change');
                   },900);

               } else {
                   alert(response.errors);
               }
            });
        },
        save: function (f) {
            var action = f.attr('action');
            var msg = '';

            $.ajax({
                url: action,
                data: f.serialize(),
                dataType: "json",
                type: "post",
                success: function(response) {

                    if(!response.data.error) {
                        msg = '<i style="vertical-align:middle" class="icon16 yes"></i> Сохранено';
                    } else {
                        msg = '<i style="vertical-align:middle" class="icon16 cross"></i> Ошибка';
                    }

                },
                error: function(jqXHR, errorText, errorThrown) {
                    if(console){
                        console.log(jqXHR, errorText, errorThrown);
                    }
                },
                complete: function() {
                    var status =  $('#plugins-settings-status');
                    status.html(msg);
                    status.show();
                    setTimeout(function(){
                        status.hide(1500);
                        status.empty();
                    }, 3000);
                }
            });
        }
    };
    $(document).ready(function () {
        $('select[name=salesku_storefront]').change(function () {
            $.shopSaleskuPluginBackendSettings.loadAction($(this));
            return false;
        });
    
        
    });
})(jQuery);