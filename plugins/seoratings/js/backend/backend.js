(function(module, global) {
  module.BackendView = Backbone.View.extend({
    el: $('.seoratings-backend'),
    events: {
      'click .seoratings-backend__item--categories': 'handleRatingSettings',
      'click [data-js="plugin__settings"]': 'handlePluginSettings',
      'click [data-js="rating__new"]': 'handleNewRating',
      'click [data-js="delete__rating"]': 'handleDelete',
      'click [data-js="duplicate__rating"]': 'handleDuplicate',
      'click [data-js="handle__rating--settings"]': 'handleRatingSettings',
      'click [data-js="handle__rating--products"]': 'handleRatingProducts',
      'click [data-js="handle__rating--templates"]': 'handleRatingTemplates',
    },
    initialize: function() {
      this.currentView = null;
      this.saveButton = this.$el.find('[data-js="save__settings"]');
      this.collection = new module.RatingCollection();
      this.collection.fetch().then(response => {
        this.collectionView = new module.RatingCollectionView({ collection: this.collection });
        this.collectionView.render();
      });
      this.loader = new module.LoaderView();
      this.$('.seoratings-backend__box--content').append(this.loader.el);
      this.listenTo(Backbone, 'template:destroy', this.handleRatingTemplates);
      this.listenTo(Backbone, 'loader:show', () => {
        this.loader.show();
      });
      this.listenTo(Backbone, 'loader:hide', () => {
        this.loader.hide();
      });
    },
    handleRatingTemplates: function(event) {
      if (event) {
        event.preventDefault();
      }
      if (this.currentView) {
        this.currentView.remove();
      }
      let view = new module.RatingTemplatesView({ collection: module.seoratings_templates });
      $('#seoratings-content').html(view.render().el);
      this.currentView = view;
      Backbone.trigger('rating:nav:item:deactivate', this.model);
    },
    handleRatingProducts: function(event) {
      event.preventDefault();
      Backbone.trigger('loader:show');
      let ratingId = event.target.getAttribute('href');
      let model = this.findRatingById(ratingId);
      if (model.get('type') === 'set') {
        if (this.currentView) {
          this.currentView.remove();
        }
        let view = new module.RatingProductsSetView({}, { ratingId });
        $('#seoratings-content').html(view.render().el);
        this.currentView = view;
        Backbone.trigger('loader:hide');
        Backbone.trigger('loader:hide');
      } else {
        if (model.get('type') === 'entity') {

        } else {
          let collection = new module.RatingProductsCollection({}, { ratingId });
          collection.fetch().then(() => {
            if (this.currentView) {
              this.currentView.remove();
            }
            let view = new module.RatingProductsCollectionView({ collection }, { ratingId });
            $('#seoratings-content').html(view.render().el);
            this.currentView = view;
            Backbone.trigger('loader:hide');
          });
        }
      }
    },
    handleRatingSettings: function(event) {
      event.preventDefault();
      let model = this.findRatingById(event.target.getAttribute('href'));
      if (this.currentView) {
        this.currentView.remove();
      }
      let view = new module.RatingView({ model, collection: this.collection });
      $('#seoratings-content').html(view.render().el);
      this.currentView = view;
    },
    handleDelete: function(event) {
      event.preventDefault();
      this.currentView.model.destroy().then(() => {});
      Backbone.trigger('rating:nav:item:deactivate', this.model);
    },
    handleDuplicate: function(event) {
      event.preventDefault();
      const id = event.target.getAttribute('href');
      Backbone.trigger('loader:show');
      $.get('?plugin=seoratings&module=backendRating&action=duplicate', { id }).done(() => location.reload());
    },
    handleNewRating: function(event) {
      event.preventDefault();
      let model = new module.RatingModel();
      let view = new module.RatingView({ model, className: 'seoratings-backend__content--is-new-rating', collection: this.collection });
      $('#seoratings-content').html(view.render().el);
      this.currentView = view;
      Backbone.trigger('rating:nav:item:deactivate', this.model);
    },
    handlePluginSettings: function(event) {
      event.preventDefault();
      $.get(event.target.getAttribute('href'))
        .then((response) => {
          return response.data;
        })
        .then((data) => {
          if (this.currentView) {
            this.currentView.remove();
          }
          let model = new module.SettingsModel(data);
          let view = new module.SettingsView({ model });
          $('#seoratings-content').html(view.render().el);
          this.currentView = view;
        });
    },
    findRatingById: function(ratingId) {
      return this.collection.find(function(model) {
        return parseInt(model.get('id')) === parseInt(ratingId);
      });
    }
  });
})(Seoratings, window);

new Seoratings.BackendView();
