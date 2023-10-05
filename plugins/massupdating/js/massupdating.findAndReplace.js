'use strict';

(function($) {
	$.massupdating.findAndReplace = {
		do: function(data, action, callback) {
			$.massupdating.toggleDialogButtons('disabled');
			$.eventStream.prefix = 'find';
			$.eventStream.init({
				module: 'far',
				action: action,
				data: $.param(data),
				params: {
					ms: 100,
					count: 100,
					texts: [$_('Поиск...'), $_('Завершение поиска...')]
				},
				on: {
					Create: function() {
						$('#massupdating-progressbar-find span.status').html($_('Подготовка к поиску...'));
						$('#massupdating-progressbar-find').show();
						$('#massupdating-progressbar-replace').hide();
					},
					Error: function() {
						$.massupdating.toggleDialogButtons('active');
						$('.massupdating-progressbar, #massupdating-search-results').hide();
					},
					Done: function(e, data) {
						if(action == 'find' || (action == 'findAndReplace' && e.lastEventId == 'next')) {
							$('#massupdating-progressbar-not-found').hide();
							$('#massupdating-search-results').show().find('h3 span b').html(data.found_count);
							$('#massupdating-search-results span.results').html(data.found_count > 100 ? $_('Первые 100 результатов поиска') : $_('Результаты поиска'));
							$('table.massupdating-search-results tr.match').remove();
							let i = 1;

							let getRegExp = function(str, save, flags) {
								if(save)
									str = '(' + str + ')';

								return new RegExp(str, flags);
							};
							let regExp = getRegExp(data.regexp, false, (data.ignore_case ? 'i' : '') + (data.replaces == 'all' ? 'g' : ''));
							
							for(let id of Object.keys(data.found)) {
								let match = data.found[id];
								match['by_one'] = [];

								let result;
								while(result = regExp.exec(match.str)) {
									let position = result.index;
									let found = match.str.substr(0, position);
									let replaced = match.str.substr(position);
									replaced = replaced.replace(getRegExp(data.regexp, true, data.ignore_case ? 'i' : ''), '<b class="found">$1</b>');
									found += replaced;

									let start = 0;
									if(position - data.closest_symbols > 0)
										start = position - data.closest_symbols;
									let length = ('<b class="found">' + match.find[0] + '</b>').length + data.closest_symbols * 2;
									found = (start ? '...' : '') + found.substr(start, length) + (start + length < found.length ? '...' : '');
									match['by_one'].push(found);

									if(data.replaces !== 'all')
										break;
								}

								match['by_one'].forEach(function(value, key) {
									$('table.massupdating-search-results').append($('<tr class="match ' + (i % 2 ? 'even' : 'odd') + '"/>').attr('id', 'massupdating-search-result-' + id + '-' + key).append(key == 0 ? $('<td align="center" rowspan="' + match['by_one'].length + '"/>').html(id) : '').append($('<td/>').html(value)));
								});
								i++;
							}
						}

						if(e.lastEventId == 'next') {
							$('#massupdating-progressbar-replace span.status').html($_('Подготовка к замене...'));
							$('#massupdating-progressbar-replace').show();
						}

						if(e.lastEventId == 'close')
							$.massupdating.toggleDialogButtons('active');

						if(action == 'findAndReplace' && e.lastEventId == 'close')
							if($('#massupdating-reload').is(':checked')) {
								$('#s-product-list-skus-container').hide();
								$.products.dispatch();
							}
					},
					Exit: function(e, data) {
						$.massupdating.toggleDialogButtons('active');
						$('#massupdating-progressbar-not-found').show();
						$('#massupdating-search-results').hide();
					}
				}
			});
		}
	}
})(jQuery);