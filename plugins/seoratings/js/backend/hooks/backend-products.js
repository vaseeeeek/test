$(function() {
  $.products.seoratingsAction = function() {
    this.load('?plugin=seoratings', function() {
      document.title = 'SEO Ratings';
      var sidebar = $('#s-sidebar');
      sidebar.find('li.selected').removeClass('selected');
      sidebar.find('#seo-ratings').addClass('selected');
    });
  };

  $.products.seoratingsActionEdit = function(category_id) {
    this.load('?plugin=seoratings&action=edit&category_id=' + category_id, function() {
      document.title = 'SEO Ratings';
      var sidebar = $('#s-sidebar');
      sidebar.find('li.selected').removeClass('selected');
      sidebar.find('#seo-ratings').addClass('selected');
    });
  };
});
