'use strict';

(function($) {
	$.eventStream = {
		interval: false,
		prefix: 'default',
		init: function({
			module,
			action,
			data = '',
			params: {
				texts,
				ms,
				count
			},
			on: {
				Create,
				Error,
				Progress,
				Done,
				Exit,
				Next
			}
		}) {
			if(!!window.EventSource) {
				let source = new EventSource('?plugin=massupdating&module=' + module + '&action=' + action + (data ? ('&' + data) : ''));

				if(typeof Create == 'function')
					Create();

				$('#massupdating-progressbar-' + $.eventStream.prefix + ' i').attr('class', 'icon16 loading');
				$('#massupdating-progressbar-' + $.eventStream.prefix + '-inner').css('width', '0%');

				source.addEventListener('message', function(e) {
					let data = JSON.parse(e.data);

					if(e.lastEventId == 'error') {
						alert(data.message);

						if(typeof Error == 'function')
							Error(e, data);

						source.close();
					}

					$('#massupdating-progressbar-' + $.eventStream.prefix + '-inner').css('width', data.progress + '%');
					$('#massupdating-progressbar-' + $.eventStream.prefix + ' span.status').html(!data.done ? data.message : (e.lastEventId == 'close' ? data.message : (data.message + ' (' + data.done + ' ' + $_('из') + ' ' + data.from + ')')));

					if(typeof Progress == 'function')
						Progress(e, data);

					if(e.lastEventId == 'exit') {
						$('#massupdating-progressbar-' + $.eventStream.prefix + ' i').attr('class', 'icon16 no').find('span.status').html(data.message);
						if(typeof Exit == 'function')
							Exit(e, data);
						source.close();
					}

					if(e.lastEventId == 'close' || e.lastEventId == 'next') {
						$('#massupdating-progressbar-' + $.eventStream.prefix + ' i').attr('class', 'icon16 yes').find('span.status').html(data.message);

						if(typeof Done == 'function')
							Done(e, data);
						if(e.lastEventId == 'next' && typeof Next == 'function')
							Next(e, data);
						if(e.lastEventId == 'next') {
							$.eventStream.prefix = data.action;
							$('#massupdating-progressbar-' + $.eventStream.prefix + ' i').attr('class', 'icon16 loading');
							$('#massupdating-progressbar-' + $.eventStream.prefix + '-inner').css('width', '0%');
						}

						if(e.lastEventId == 'close')
							source.close();
					}
				}, false);
			} else {
				$('#massupdating-progressbar-' + $.eventStream.prefix + ' span.status').html(texts[0]);

				i = 1;
				$.eventStream.interval = setInterval(function() {
					percents = (i / count * 100);
					if(percents > 99) {
						$('#massupdating-progressbar-' + $.eventStream.prefix + ' span.status').html(texts[1]);
						clearInterval($.eventStream.interval);
					} else {
						$('#massupdating-progressbar-' + $.eventStream.prefix + '-inner').css('width', percents + '%');
						i++;
					}
				}, ms);

				$.getJSON('?plugin=massupdating&module=' + module + '&ie=true&action=' + action + (data ? ('&' + data) : ''), function(data) {
					$('#massupdating-progressbar-' + $.eventStream.prefix + ' i').attr('class', 'icon16 yes');
					callback(data);
					$('#massupdating-progressbar').css('width', '100%');
					clearInterval($.eventStream.interval);
				});
			}
		}
	}
})(jQuery);