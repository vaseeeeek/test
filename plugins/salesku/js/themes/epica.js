$.saleskuPluginProductElements.Image = function(root_element) {
    if(root_element.find('.image').find('img.lazy').length==1) {
        return root_element.find('.image').find('img.lazy');
    } else {
        return root_element.find('.image').find('img:last');
    }
};
$.saleskuPluginProductElements.OriginalImage = function(root_element)  {
    if(root_element.find('.image').find('img.lazy').length==1) {
        return root_element.find('.image').find('img.lazy').data('original');
    } else {
        return root_element.find('.image').find('img:last').data('original');
    }
};