$(function() {
  $('#seoratings__link--add-to-rating').click(function(event) {
    event.preventDefault();
    const products = $.product_list.getSelectedProducts(true);
    if (products.count === 0) {
      $('#seoratings-dialog--empty').waDialog({
        height: '100px',
        buttons: '<a class="button red cancel" href="#" class="">' + $_('Закрыть') + '</a>'
      });
    } else {
      $('#seoratings-dialog').waDialog({
        height: '150px',
        buttons: '<input class="button green" type="submit" value="Сохранить">&nbsp;<a class="button red cancel" href="#">' + $_('Отменить') + '</a>',
        onSubmit: function(dialog) {
          const data = $(this).serializeArray().concat(products.serialized);

          $.post('?plugin=seoratings&module=backendRatingProducts&action=addProducts', data, function(response) {
          }, 'json').always(function() {
            dialog.trigger('close');
            $.products.dispatch();
          });

          return false;
        }
      });
    }
  });
});