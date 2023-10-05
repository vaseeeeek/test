
$.product.editTabSyrattachmentsLoad = function(path, post) {
    var self = this;
    path = path || this.path;
    var url = '?plugin=syrattach&module=attachments&id=' + path.id;
    var r = Math.random();
    this.ajax.random = r;
    var $tab = $('#s-product-edit-forms .s-product-form.' + path.tab);
    
    if(path.id == 'new') {
        $.shop.trace('product.attachmentsTabHandler: create', [path.tab + ' — create', path]);
        this.saveData(this.path.mode, path.tab, function () { });
        return;
    }
    
    if ($tab.length) {
        $tab.remove();
    }
    $('#s-product-edit-forms > form').append(tmpl('template-productprofile-tab', {
        id: path.tab
    }));
    $tab = $('#s-product-edit-forms .s-product-form.' + path.tab);
    this.ajax.target = $tab;
    this.ajax.link = $('#s-product-edit-menu li.' + path.tab);
    $.shop.trace('$.product.editTabSyrattachmentsLoad', [path, url, path.params]);
    if (path.params && post) {
        var type = typeof (path.params);
        switch (type) {
            case 'String':
                url += path.params;
                break;
            case 'Array':
                url += path.params.serialize();
                break;
            default:
                $.shop.error('unexpected type ' + type, path.params);
        }
    }
    $.ajax({
        url: url,
        type: post ? 'POST' : 'GET',
        data: post ? (post || {}) : (path.params || {}),
        success: function(data, textStatus) {
            $.shop.trace('$.product.loadTab status=' + textStatus);
            if (self.ajax.random != r) {
                // too late: user clicked something else.
                return;
            }
            $tab.empty().append(data);
            self.ajax.target = null;
            self.ajax.link = null;
            var hash = '#/product/' + path.id + '/edit/';
            if (path.tab) {
                hash += path.tab + '/';
            }
            if (path.tail) {
                hash += path.tail + '/';
            }
            if (path.params) {
                if (!$.isEmptyObject(path.params)) {
                    var ar = [];
                    for (var k in path.params) {
                        if (path.params.hasOwnProperty(k)) {
                            ar.push(k + '=' + path.params[k]);
                        }
                    }
                    hash += ar.join('&');
                }
            }
            window.location.hash = hash;
            self.dispatch(path);
        }
    });
};


