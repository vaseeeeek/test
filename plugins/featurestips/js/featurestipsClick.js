$(document).ready(function(){
    $('div.featurestips_tip').click(function(featurestipsevent) {
        $('div.featurestips_view').css("visibility", "hidden");
        $('div.featurestips_view', this).css("visibility", "visible");
        featurestipsevent.preventDefault();
        featurestipsevent.stopPropagation();
    });
    $('div.featurestips_view').click(function(featurestipsevent)  {
        featurestipsevent.stopPropagation();
    });
    $('body').click(function() {
        $('div.featurestips_view').css("visibility", "hidden");
    });
});