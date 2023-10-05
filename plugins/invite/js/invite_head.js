$(function() {
    var inviteVars = inviteGetUrlVars();
    if (inviteVars.invite_code) {
        console.log(inviteVars.invite_code);
        $('input[name="data[invite_code]"]').val(inviteVars.invite_code);
    }

    function inviteGetUrlVars() {
        var vars = {};
        var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi,
            function(m,key,value) {
                vars[key] = value;
            });
        return vars;
    }
});