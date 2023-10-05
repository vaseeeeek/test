$(document).ready(function(){
    $('div.featurestips_tip').hover(function() {
        $('div.featurestips_view', this).css("visibility", "visible");
    },
    function() {
        $('div.featurestips_view', this).css("visibility", "hidden");
    });
});