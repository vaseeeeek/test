(function(){
    var BrandsSearch = (function () {
        var $block = null;
        var $brandsList = null;
        var $searchField = null;
        var $clearSearch = null;
        var $lettersBlock = null;
        var letterActiveClass = 'brands-letters__item--active';

        var _private = {
            searchString: '',
            letter: '',
            initEventListeners: function() {
                $searchField.on('input', function() {
                    var value = this.value.toLowerCase();
                    if (value.length > 0) {
                        _private.searchString = value;
                        $clearSearch.show();
                    } else {
                        _private.searchString = '';
                        $clearSearch.hide();
                    }

                    _private.filterItems();
                });

                $clearSearch.on('click', function () {
                    $searchField.val('').trigger('input');
                    $(this).hide();
                });

                $lettersBlock.on('click', '.brands-letters__item', function () {
                    var item = $(this);

                    if (item.hasClass(letterActiveClass)) {
                        _private.letter = '';
                        item.removeClass(letterActiveClass);
                    } else {
                        $lettersBlock.find('.brands-letters__item').removeClass(letterActiveClass);
                        item.addClass(letterActiveClass);
                        _private.letter = $(this).data('letter');
                    }

                    _private.toggleResetLetterButton();
                    _private.filterItems();
                });

                $lettersBlock.on('click', '.brands-letters__reset', function () {
                    _private.letter = '';
                    $lettersBlock.find('.brands-letters__item').removeClass(letterActiveClass);
                    _private.toggleResetLetterButton();
                    _private.filterItems();
                });
            },
            filterItems: function() {
                _private.getItems().each(function() {
                    var $itemBlock = $(this);
                    var itemName = $itemBlock.data('brand');
                    var itemLetter = $itemBlock.data('letter');

                    if (itemName.indexOf(_private.searchString) === 0 && (itemLetter === _private.letter || _private.letter === '')) {
                        $itemBlock.show();
                    } else {
                        $itemBlock.hide();
                    }
                });

                _private.toggleNotFoundBlock();
            },
            toggleNotFoundBlock: function() {
                var hasShownItems = _private.getItems().filter(':visible').length > 0;

                if (hasShownItems) {
                    $brandsList.find('.shop-brand-brands__not-found').remove();
                } else if ($brandsList.find('.shop-brand-brands__not-found').length === 0) {
                    $brandsList.append('<div class="shop-brand-brands__not-found">Не найдено</div>');
                }
            },
            getItems: function() {
                return $brandsList.find('.shop-brand-brands__brand-wrap');
            },
            toggleResetLetterButton: function() {
                var hasActiveLetter = $lettersBlock.find('.'+letterActiveClass).length;

                if (hasActiveLetter) {
                    _private.addResetLetterButton();
                } else {
                    _private.removeResetLetterButton();
                }
            },
            addResetLetterButton: function () {
                if ($block.find('.brands-letters__reset').length === 0) {
                    $block.find('.brands-letters__list').last().append(
                        '<li class="brands-letters__reset">показать все</li>');
                }
            },
            removeResetLetterButton: function () {
                $block.find('.brands-letters__reset').remove();
            }
        };

        return {
            init: function () {
                $block = $('.brands-search');
                if ($block.length > 0) {
                    $brandsList = $('.shop-brand-brands__wrapper');
                    $searchField = $block.find('.brands-search-input');
                    $clearSearch = $block.find('.brands-search-clear');
                    $lettersBlock = $block.find('.brands-letters');
                    _private.initEventListeners();
                }
            }
        }
    })();

    $(document).ready(function () {
        BrandsSearch.init();
    });
})();