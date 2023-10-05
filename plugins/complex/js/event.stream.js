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
				Exit
			}
		}) {
			if(!!window.EventSource) {
				let source = new EventSource('?plugin=complex&module=' + module + '&action=' + action + (data ? ('&' + data) : ''));

				if(typeof Create == 'function')
					Create();

				$('#progressbar-' + $.eventStream.prefix + ' i').attr('class', 'icon16 loading');
				$('#progressbar-' + $.eventStream.prefix + '-inner').css('width', '0%');

				source.addEventListener('message', function(e) {
					let data = JSON.parse(e.data);

					if(e.lastEventId == 'error') {
						alert(data.message);

						if(typeof Error == 'function')
							Error(e, data);

						source.close();
					}

					$('#progressbar-' + $.eventStream.prefix + '-inner').css('width', data.progress + '%');
					$('#progressbar-' + $.eventStream.prefix + ' span.status').html(!data.done ? data.message : (e.lastEventId == 'close' ? data.message : (data.message + ' (' + data.done + ' ' + 'из' + ' ' + data.from + ')')));

					if(typeof Progress == 'function')
						Progress(e, data);

					if(e.lastEventId == 'exit') {
						$('#progressbar-' + $.eventStream.prefix + ' i').attr('class', 'icon16 no').find('span.status').html(data.message);
						if(typeof Exit == 'function')
							Exit(e, data);
						source.close();
					}

					if(e.lastEventId == 'close' || e.lastEventId == 'next') {
						$('#progressbar-' + $.eventStream.prefix + ' i').attr('class', 'icon16 yes').find('span.status').html(data.message);

						if(typeof Done == 'function')
							Done(e, data);

						if(e.lastEventId == 'close')
							source.close();
					}
				}, false);
			} else {
				$('#progressbar-' + $.eventStream.prefix + ' span.status').html(texts[0]);

				i = 1;
				$.eventStream.interval = setInterval(function() {
					percents = (i / count * 100);
					if(percents > 99) {
						$('#progressbar-' + $.eventStream.prefix + ' span.status').html(texts[1]);
						clearInterval($.eventStream.interval);
					} else {
						$('#progressbar-' + $.eventStream.prefix + '-inner').css('width', percents + '%');
						i++;
					}
				}, ms);

				$.getJSON('?plugin=complex&module=' + module + '&ie=true&action=' + action + (data ? ('&' + data) : ''), function(data) {
					$('#progressbar-' + $.eventStream.prefix + ' i').attr('class', 'icon16 yes');
					callback(data);
					$('#progressbar').css('width', '100%');
					clearInterval($.eventStream.interval);
				});
			}
		}
	}
})(jQuery);