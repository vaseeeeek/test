var shevskySettingsControls = {
	initEditor: function(options) {
		var wa_editor = ace.edit(options.ace_editor_container);

		wa_editor.setTheme("ace/theme/eclipse");
		var session = wa_editor.getSession();
		session.setValue($('#' + options.id).hide().val());
		
		if (options.type == 'css') {
			session.setMode("ace/mode/css");
		} else if (options.type == 'javascript') {
			session.setMode("ace/mode/javascript");
		} else {
			session.setMode("ace/mode/css");
			session.setMode("ace/mode/javascript");
			session.setMode("ace/mode/smarty");
		}
		session.setUseWrapMode(true);
		wa_editor.setOption("maxLines", 10000);
		wa_editor.setAutoScrollEditorIntoView(true);
		wa_editor.renderer.setShowGutter(false);
		wa_editor.setShowPrintMargin(false);
		wa_editor.setFontSize(14);
		wa_editor.navigateTo(0, 0);
		session.setUseSoftTabs(false);

		session.on('change', function() {
			$('#' + options.id).val(wa_editor.getValue()).trigger('change');
		});
	},
	editor: function(elem) {
		wa_url = '/';
		
		$(elem).after('<div class="ace bordered"><div id="shevskySettingsControls-' + $(elem).attr('id') + '-body-container"></div></div>');

		var editor = this.initEditor({
			'prefix': 'shevskySettingsControls-' + $(elem).attr('id') + '-',
			'id': $(elem).attr('id'),
			'ace_editor_container': 'shevskySettingsControls-' + $(elem).attr('id') + '-body-container',
			'type': $(elem).hasClass('css') ? 'css' : 'html'
		});
	}
};