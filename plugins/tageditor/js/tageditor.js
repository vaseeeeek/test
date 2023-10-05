$(function() {
  var tageditor = {
    instance: false,

    init: function() {
      if (!tageditor.instance) {
        tageditor.events();
        tageditor.instance = true;
      }
    },

    showTagDialog: function(id, name, url, tag) {
      var $dialog_template = $('.tageditor-tag-dialog-template').clone();
      $dialog_template.find('.dialog-content').waDialog({
        buttons: $dialog_template.find('.dialog-buttons').html(),
        className: 'tageditor',
        width: $(window).width() - 100,
        height: $(window).height() - 150,
        onLoad: function() {
          var dialog = $(this);
          var tag = {
            id: id,
            name: name,
            url: url
          };

          $.post(
            '?plugin=tageditor&action=tagOptions',
            { tag: tag },
            function(response) {
              dialog.find('.loading').replaceWith(response);

              var $delete = dialog.find('.tageditor-tag-dialog-delete');
              var $delete_confirm = dialog.find('.tageditor-tag-dialog-delete-confirm');

              $delete_confirm.css({ 'margin-right': '-' + ($delete_confirm.width() + 20) + 'px' });
              $delete.css({ 'opacity': 1 });

              dialog.find('[name="data[description]"], [name="data[description_extra]"]').waEditor({
                lang: dialog.find('#wa_editor_lang').val(),
                editorOnLoadFocus: false,   //redactor 1
                focus: false,   //redactor 2
                imageUpload: '?plugin=tageditor&action=uploadImage',
                uploadImageFields: {
                  _csrf: dialog.find('input[name="_csrf"]').val()
                }
              });
              dialog.find('.tag-name-edit').focus();
            }
          );
        },
        onSubmit: function(d) {
          var dialog = $(d);
          var error = $('.tageditor-error');
          error.empty();
          var new_name = dialog.find('.tag-name-edit').eq(0).val().trim();
          if (new_name.length < 1) {
            error.text($.wa.locale['A tag cannot be empty.']);
          } else {
            $('<i class="icon16 loading margin-left middle"></i>').insertAfter(dialog.find('.cancel'));
            $('[name="data[description]"], [name="data[description_extra]"]').waEditor('sync');
            $.post('?plugin=tageditor&action=tagEdit', $(this).serialize(), function(response) {
              if (response.status == 'fail') {
                dialog.find('.loading').remove();
                error.html(response.errors.join(' '));
              } else {
                if (tag) {
                  tag.find('.name').text(new_name);
                  tag.attr('data-name', new_name);
                  tag.attr('data-url', response.data.url);
                }

                $('#s-tag-cloud .tags a').filter(function() {
                  //update tag in shop's cloud in sidebar
                  return $(this).text() == name;
                }).attr('href', '#/products/tag=' + response.data.url + '/').text(new_name);

                d.trigger('close');
              }
            }, 'json');
          }
          return false;
        },
        onClose: function() {
          tageditor.dialogClose($(this));
        }
      });
    },

    deleteTag: function(tag_id, tag_name, callback) {
      if (callback) {
        callback.call();
      }

      var search = $('#tageditor-search');

      $.post('?plugin=tageditor&action=tagDelete', { id: tag_id }, function(response) {
        //remove tag from shop's cloud in sidebar
        $('#s-tag-cloud .tags a').filter(function() {
          return $(this).text() == tag_name;
        }).remove();

        //reset search if last visible tag has just been deleted
        if (!$('.tageditor .tag:visible').length) {
          search.val('').trigger('change');
        }

        //if there are no tags, show message and remove search field
        if (!$('.tageditor .tag').length && !$('#tageditor-notice-too-many-tags').is(':visible')) {
          $('#tageditor-notice-no-tags').removeClass('hidden');
          search.remove();
        }

        //show all tags if a tag above limit has just been deleted
        //and disable autocomplete for search field
        if (response.data.tags !== undefined) {
          $('.tageditor .tags-container').html(response.data.tags);
          if (search.data('autocomplete')) {
            search.autocomplete('destroy');
            search.removeData('autocomplete');
          }
        }
      });
    },

    deleteTagsAnimated: function(tag_selector, icon, icon_class, callback) {
      var after_delete_function = function(callback) {
        if (!$('.tageditor .tag').length) {
          $('#tageditor-search').remove();
          $('#tageditor-notice-too-many-tags').remove();
        }

        $('#tageditor-notice-no-tags').removeClass('hidden');
        icon.attr('class', 'icon16 yes');
        setTimeout(function() {
          icon.animate({ opacity: 0 }, 500, function() {
            icon.attr('class', icon_class);
            icon.css('opacity', 1);
            if (callback) {
              callback.call();
            }
          });
        }, 500);
      };

      var $tags = $(tag_selector);
      if ($tags.length) {
        var deletion_duration = 3000;
        var delete_tag_timeout = deletion_duration / $tags.length;
        $tags.each(function(i) {
          var tag = $(this);
          setTimeout(function() {
            tag.animate({ opacity: 0 }, 700, function() {
              tag.remove();
              if (!$(tag_selector).length) {
                after_delete_function();
              }
            });
          }, i * delete_tag_timeout);
        });
      } else {
        callback = callback || null;
        after_delete_function(callback);
      }
    },

    dialogClose: function(dialog) {
      $('#tageditor-search').focus();
      dialog.remove();
    },

    events: function() {
      //edit tag
      $(document).on('click', '.tageditor .tag .name', function() {
        var tag = $(this).closest('.tag');
        tageditor.showTagDialog(tag.data('id'), tag.attr('data-name'), tag.attr('data-url'), tag);
      });

      //delete one tag
      $(document).on('click', '.tageditor .tag .delete', function() {
        var $tag = $(this).closest('.tag');
        var tag_name = $tag.find('.name').text();
        var tag_url = $tag.data('url');

        $('<p><a target="_blank" class="no-underline" href="?action=products#/products/tag=' + tag_url + '/">'
          + $.wa.locale['View products with this tag'].replace('%s', tag_name)
          + ' <i class="icon16 new-window"></i></a></p>')
          .waDialog({
            title: $.wa.locale['This tag will be removed from all products! Continue?'],
            buttons: '<input type="submit" value="' + $.wa.locale['Delete'] + '" class="button green">'
              + ' <a href="" class="cancel">' + $.wa.locale['cancel'] + '</a>',
            width: '500px',
            height: '200px',
            className: 'tageditor',
            onSubmit: function(dialog) {
              $(dialog).find('.dialog-buttons .cancel').after($('<i class="icon16 loading middle margin-left"></i>'));
              tageditor.deleteTag($tag.data('id'), tag_name, function() {
                $tag.remove();
                dialog.trigger('close');
              });

              return false;
            },
            onClose: function() {
              tageditor.dialogClose($(this));
            }
          });
        return false;
      });

      //delete tag from dialog
      $(document).on('click', '.tageditor-tag-dialog-delete-button', function() {
        var $button = $(this);
        var $delete = $button.parent();
        var $delete_confirm = $delete.parent().find('.tageditor-tag-dialog-delete-confirm');

        $delete.animate({ 'opacity': 0 }, 500, function() {
          $(this).css({ 'display': 'none' });
        });

        $delete_confirm.animate({
          'margin-right': 0,
          'opacity': 1
        }, 500);

        return false;
      });

      //delete tag from dialog: confirm
      $(document).on('click', '.tageditor-tag-dialog-delete-button-confirm', function() {
        var dialog = $(this).closest('.dialog');
        var tag_id = dialog.find('[name="id"]').val();
        var tag_name = dialog.find('[name="name"]').val();

        tageditor.deleteTag(tag_id, tag_name, function() {
          $('.tageditor .tag').filter(function() {
            return $(this).data('id') == tag_id;
          }).remove();
          tageditor.dialogClose(dialog);
        });

        return false;
      });

      //cancel tag deletion in dialog
      $(document).on('click', '.tageditor-tag-dialog-delete-button-cancel', function() {
        var $button = $(this);
        var $delete_confirm = $button.parent();
        var $delete = $delete_confirm.parent().find('.tageditor-tag-dialog-delete');

        $delete.css({ 'display': 'inline-block' }).animate({ 'opacity': 1 }, 500);
        $delete_confirm.animate({
          'margin-right': '-' + ($delete_confirm.width() + 20) + 'px',
          'opacity': 0
        }, 500);

        return false;
      });

      //update URLs for all tags
      $(document).on('click', '.tageditor .tageditor-update-urls-link', function() {
        var loading = $('<i class="icon16 loading" style="margin-left: 0.5em; vertical-align: middle;"></i>');
        var $dialog_template = $('.tageditor-tag-urls-update-dialog-template').clone();
        var $content = $dialog_template.find('.dialog-content');
        var $buttons = $dialog_template.find('.dialog-buttons');

        $content.waDialog({
          buttons: $buttons.html(),
          height: '200px',
          onSubmit: function(dialog) {
            loading.insertAfter(dialog.find('.dialog-buttons a.cancel'));
            $.post('?plugin=tageditor&action=updateUrls', $(this).serialize(), function() {
              dialog.trigger('close');
            }, 'json');
            return false;
          },
          onClose: function() {
            tageditor.dialogClose($(this));
          }
        });

        return false;
      });

      //delete all empty tags
      $(document).on('click', '.tageditor .tageditor-delete-all-empty-link', function() {
        var link = $(this);
        var icon = link.find('.icon16');

        if (icon.hasClass('loading')) {
          return false;
        }

        var icon_class = icon.attr('class');
        icon.attr('class', 'icon16 loading');

        $.get('?plugin=tageditor&action=tagDeleteEmpty', function() {
          tageditor.deleteTagsAnimated('.tageditor .tag.empty', icon, icon_class);
        });
        return false;
      });

      //delete all tags
      $(document).on('click', '.tageditor .tageditor-delete-all-link', function() {
        var link = $(this);
        var icon = link.find('.icon16');

        if (icon.hasClass('loading')) {
          return false;
        }

        $('')
          .waDialog({
            title: $.wa.locale['Delete all product tags from your online store?'],
            buttons: '<input type="submit" value="' + $.wa.locale['Delete'] + '" class="button green">'
              + ' <a href="" class="cancel">' + $.wa.locale['cancel'] + '</a>'
              + ' <span class="red tageditor-error margin-left"></span>',
            width: '500px',
            height: '200px',
            className: 'tageditor tageditor-red-border',
            onSubmit: function(d) {
              var dialog = $(d);
              var error = $('.tageditor-error');
              error.empty();

              var icon_class = icon.attr('class');
              icon.attr('class', 'icon16 loading');

              $('<i class="icon16 loading margin-left middle"></i>').insertAfter(dialog.find('.cancel'));
              $.post('?plugin=tageditor&action=tagDelete', { id: 'all' }, function(response) {
                if (response.status == 'fail') {
                  dialog.find('.loading').remove();
                  error.html(response.errors.join(' '));
                } else {
                  tageditor.deleteTagsAnimated('.tageditor .tag', icon, icon_class);
                  //remove all tags from shop's cloud in sidebar
                  $('#s-tag-cloud .tags a').remove();
                  d.trigger('close');
                }
              }, 'json');
              return false;
            },
            onClose: function() {
              tageditor.dialogClose($(this));
            }
          });
        return false;
      });

      //search tags
      $(document).on('change keyup click', '#tageditor-search', function() {
        var search = $(this);
        var request = search.val();
        if (request.length < 1) {
          $('.tageditor .tag').show();
        } else {
          $('.tageditor .tag').each(function() {
            var tag = $(this);
            if (tag.find('.name').text().toLowerCase().indexOf(request.toLowerCase()) > -1) {
              tag.show();
            } else {
              tag.hide();
            }
          });
        }
      });

      //reload tag list
      $(document).on('click', '.dialog .error .tageditor-reload', function() {
        $('<i class="icon16 loading margin-left middle"></i>').insertAfter($(this));
        location.reload();
      });

      //suggest tag URL
      $(document).on('click', '.tageditor .suggest-url', function() {
        var link = $(this);
        var form_field = link.closest('.field');

        if (form_field.find('.loading').length) {
          return false;
        }

        var loading = $('<i class="icon16 loading" style="margin-left: 0.5em;"></i>');
        var name_field = link.closest('form').find('[name="name"]');

        loading.insertAfter(link);
        $.post('?plugin=tageditor&action=suggestUrl', { tag: name_field.val() }, function(url) {
          loading.remove();
          form_field.find('[name="data[url]"]').val(url).focus().select();
        });

        return false;
      });

      //show field alias list
      $(document).on('change', '.tageditor .tageditor-user-other-field-show', function() {
        var checkbox = $(this);
        var use_field_alias_list = checkbox.closest('.value').find('.tageditor-user-field-alias-list');
        var field_control = checkbox.closest('.value').find('[name^="data["]');
        if (checkbox.is(':checked')) {
          use_field_alias_list.removeClass('hidden');
          field_control.slideUp();
        } else {
          use_field_alias_list.val('').addClass('hidden');
          field_control.slideDown();
        }
      });

      //show tag index update dialog
      $(document).on('click', '.tageditor-update-index-link', function() {
        var link = $(this);
        var icon = link.find('.icon16');

        if (icon.hasClass('loading')) {
          return false;
        }

        var icon_class = icon.attr('class');
        var pull = [];

        var dialog_template = $('.tageditor-tag-index-update-dialog-template').clone();
        var content = dialog_template.find('.dialog-content').html();
        var buttons = dialog_template.find('.dialog-buttons').html();
        $(content).waDialog({
          buttons: buttons,
          height: '250px',
          esc: false,
          onSubmit: function() {
            var dialog = $(this);
            dialog.find('.tageditor-tag-index-update-dialog-hint').removeClass('hidden');
            dialog.find('.tageditor-tag-index-update-dialog-loading').removeClass('hidden');
            icon.attr('class', 'icon16 loading');
            dialog.find('.dialog-buttons').addClass('hidden');

            var url = '?plugin=tageditor&action=tagIndexUpdate';
            var processId = undefined;

            var cleanup = function() {
              $.post(url, { processId: processId, cleanup: 1 }, function(r) {
                //pause a little before closing the dialog
                setTimeout(function() {
                  dialog.trigger('close');

                  //set "yes" icon after a while after closing the dialog, not right away
                  setTimeout(function() {
                    icon.attr('class', 'icon16 yes');

                    //let the "yes" icon remain visible for a while before it starts to fade away
                    setTimeout(function() {
                      icon.animate({ opacity: 0 }, 500, function() {
                        icon.attr('class', icon_class);
                        icon.css('opacity', 1);
                      });
                    }, 1000);
                  }, 500);
                }, 1000);
              }, 'json');
            };

            var step = function(delay) {
              delay = delay || 2000;
              var timer_id = setTimeout(function() {
                $.post(
                  url,
                  { processId: processId },
                  function(r) {
                    if (!r) {
                      step(3000);
                    } else {
                      if (r && r.ready) {
                        dialog.find('.progressbar .progressbar-inner').css({
                          width: '100%'
                        });
                        dialog.find('.progressbar-description').text('100%');
                        cleanup();
                      } else {
                        if (r && r.error) {
                          dialog.find('.errormsg').text(r.error);
                        } else {
                          if (r && r.progress) {
                            var progress = parseFloat(r.progress.replace(/,/, '.'));
                            if (progress) {
                              dialog.find('.progressbar .progressbar-inner').animate({
                                'width': progress + '%'
                              });
                            }
                            dialog.find('.progressbar-description').text(r.progress);
                            dialog.find('.progressbar-hint').text(r.hint);
                          }
                          if (r && r.warning) {
                            dialog.find('.progressbar-description').append('<i class="icon16 exclamation"></i><p>' + r.warning + '</p>');
                          }

                          step();
                        }
                      }
                    }
                  },
                  'json'
                ).error(function() {
                  step(3000);
                });
              }, delay);
              pull.push(timer_id);
            };

            $.post(url, {}, function(r) {
              if (r && r.processId) {
                processId = r.processId;
                step(1000);
                step();
              } else {
                if (r && r.error) {
                  dialog.find('.errormsg').text(r.error);
                } else {
                  dialog.find('.errormsg').text('Server error');
                }
              }
            }, 'json').error(function() {
              dialog.find('errormsg').text('Server error');
            });

            return false;
          },
          onClose: function() {
            icon.attr('class', icon_class);
            var timer_id = pull.pop();
            while (timer_id) {
              clearTimeout(timer_id);
              timer_id = pull.pop();
            }
            tageditor.dialogClose($(this));
          }
        });
        return false;
      });
    }
  };

  $.products.tageditorAction = function() {
    this.load('?plugin=tageditor', function() {
      tageditor.init();

      document.title = $.wa.locale['Tag editor'];

      var sidebar = $('#s-sidebar');
      sidebar.find('li.selected').removeClass('selected');
      sidebar.find('#tageditor').addClass('selected');

      $('#tageditor-search').focus();

      var $search = $('#tageditor-search');
      if ($search.hasClass('tageditor-search-autocomplete')) {
        $search.autocomplete({
          source: '?plugin=tageditor&action=autocomplete',
          minLength: 3,
          delay: 300,
          select: function(event, ui) {
            tageditor.showTagDialog(ui.item.id, ui.item.value, ui.item.url);
            $search.val('');
            return false;
          }
        });
      }
    });
  };
});