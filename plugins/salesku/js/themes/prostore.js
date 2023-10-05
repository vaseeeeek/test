$.saleskuPluginProductElements._Selectors.image = '.image_holder, .image';
$.saleskuPluginProductElements.set('Image',  function(root_element) {
    return root_element.find('.first-img');
}, 1);