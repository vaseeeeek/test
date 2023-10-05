'use strict';

Backbone.emulateHTTP = true;
Backbone.emulateJSON = true;

window.Seoratings = {
  getTemplate: function(selector) {
    return Handlebars.compile($(selector + '-tmpl').html());
  }
};

window.Handlebars.registerHelper('select', function(value, options) {
  let $el = $('<select />').html(options.fn(this));
  $el.find('[value="' + value + '"]').attr({ 'selected': 'selected' });
  return $el.html();
});

window.Handlebars.registerHelper('selectMultiple', function(value, options) {
  let $el = $('<select />').html(options.fn(this));
  if (value && value.map) {
    value.map(function(el) {
      $el.find('[value="' + el + '"]').attr({ 'selected': 'selected' });
    });
  }
  return $el.html();
});

window.Handlebars.registerHelper('ifIsOn', function(value, options) {
  if (value == '1') {
    return options.fn(this);
  }
  return options.inverse(this);
});

window.Handlebars.registerHelper('ifEquals', function(arg1, arg2, options) {
  return (arg1 === arg2) ? options.fn(this) : options.inverse(this);
});

window.Handlebars.registerHelper('ifNotEquals', function(arg1, arg2, options) {
  return (arg1 !== arg2) ? options.fn(this) : options.inverse(this);
});