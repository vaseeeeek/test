(function($){

	$.shop.bdgPlugin = {
		options: [],
		plugin_id: 'bdg',
		form: $('#plugins-settings-form'),
		design_data: '',
		init: function(options){
			/* custom init */
			this.initBadges();
			
			/* general init */
			this.initDescription();
			this.initDesign();
			
			var self = this;
			$.plugins.success = function(){
				self.successSubmitBtn();
			};
		},
		/* custom methods */
		initBadges: function(){
			this.colorpickerClick();
			this.nameChange();
			this.badgeChangeInit();
			this.addBadgeClick();
			this.deleteBadgeClick();
			this.opener();
		},
		nameChange: function(){
			var self = this;
			this.form.on('change','.name-input',function(){
				var that = $(this),
					fg = that.closest('.field-group'),
					ta = $('textarea',fg),
					code = ta.val(),
					div = $('<div />').html(code),
					s = $('.-b-',div).size(),
					v = that.val();
				
				if ( code != '' && v != '' && s > 0 ){
					$('.-b-',div).text(v);
					ta.val(div.html());
					$('.bdg-image',fg).html(div);
					self.setColor($(this),$('.color_bg',fg).val());
				}
			});
		},
		badgeChangeInit: function(){
			var self = this;
			this.form.on('change','input, textarea',function(){
				self.editSubmitBtn();
			});
		},
		colorpickerClick: function(){
			var self = this;
			this.form.on('click','.colorreplacer',function(){
				var that = $(this),
					p = that.prev('input');
				$('#colorpicker').remove();
				that.after('<div id="colorpicker"></div>');
				var f = $.farbtastic('#colorpicker');
				f.linkTo(p);
				f.linkTo(function(){
					p.val(this.color);
					p.css('background-color','#fff');
					p.css('color','#000');
					that.css('background-color',this.color);
					self.setColor(that,this.color);
					$('.shop-bdg-name-span i',that.closest('.field-group')).css('background-color',this.color);
					self.editSubmitBtn();
				});
			})
		},
		setColor: function(that,color){
			var fg =  that.closest('.field-group'),
				b = $('.-b-',fg);
			if ( b.size() ){
				var t = b.text().trim(),
					tm = '<span>'+t+'</span><i></i>';
				b.attr('c',$.color.parse(color).toString(1));
				if ( $('span',b).size() == 0 )
					b.html(tm);
				$('textarea',fg).val($('<div>').append(b.clone().text(t)).html());
				var s = $('span',b),
					i = s.next('i'),
					sc = $.color.parse(color).scale('rgb',0.9).toString(),
					sci = $.color.parse(color).scale('rgb',0.7).toString();

				s.css('background-image','linear-gradient(to bottom, '+color+' 0%, '+sc+' 100%)');
				s.css('background-color',color);
				i.css('border-color',sci);
			}
		},
		addBadgeClick: function(){
			var self = this;
			this.form.on('click','.add-badge',function(){
				var e = $(this).closest('div'),
					p = e.prev('.field-group'),
					cn = 1;
				if ( p.size() )
					cn = parseInt(p.data('cn'))+1;
				
				var c = $('#new-badge').html().replace(/#cn#/g,cn);
				e.before(c);
				e.prev().data('cn',cn);
				self.setColor(e.prev(),'#123456');
				return false;
			})
		},
		deleteBadgeClick: function(){
			this.form.on('click','.bdg-delete',function(){
				$(this).closest('.field-group').remove();
				return false;
			})
		},
		opener: function(){
			var self = this;
			this.form.on('click','.bdg-up, .bdg-down',function(){
				var that = $(this),
					g = that.closest('.field-group'),
					d = g.find('.field'),
					f = d.first(),
					m = g.find('.bdg-image');
				that.hide();
				if ( that.is('.bdg-up') ){
					d.not(':first').hide();
					m.hide();
					f.find('.name').hide();
					f.find('input[type="text"]').hide();
					f.find('.shop-bdg-name-span').show();
					f.find('.value').animate({ 'margin-left':'0px' },300,function(){
						g.animate({ 'margin-bottom':'0px','margin-top':'5px' },100,function(){
							g.find('.bdg-down').show()
						});
					});
				} else {
					f.find('.value').animate({ 'margin-left':'180px' },300,function(){
						g.animate({ 'margin-bottom':'30px','margin-top':'30px' },100,function(){
							d.not(':first').show();
							m.show();
							self.setColor(that,$('.colorreplacer',g).css('background-color'));
							g.find('.bdg-up').show();
							f.find('.name').show();
							f.find('input[type="text"]').show();
							f.find('.shop-bdg-name-span').hide();
						});
					});
				}
			})
		},
		
		/* general methods */
		initDescription: function(){
			var b = $('#desc-block'),
				show = '<i class="icon10 darr"></i>Показать описание плагина',
				hide = '<i class="icon10 darr"></i>Скрыть описание плагина',
				desc = $('<div />').addClass('desc').html(b.html()).hide(),
				btn = $('<a />').addClass('inline-link desc-btn').html(show);
			
			btn.click(function(){
				var self = $(this);
				desc.toggle();
				if ( desc.is(':visible') ){
					self.html(hide);
					$('i',self).removeClass('darr').addClass('uarr');
				}else{
					self.html(show);
					$('i',self).removeClass('uarr').addClass('darr');
				}
				return false;
			})
			b.html('').append(desc).append(btn);
		},
		initDesign: function(){
			var self = this,
				b = $('.block-frontend-design');
			$('a',b).click(function(){
				var title = $(this).text(),
					name = $(this).data('name'),
					mode = $(this).data('mode'),
					theme = $('#select-frontend-design-theme').val(),
					div = $('<div />').hide();
				self.design_data = {name:name,theme:theme};
				b.append( div.attr('id','dialog-plugin-design') );
				div.waDialog({
					title: (theme == '_') ? 'Редактирование "'+title+'" для всех тем' : 'Редактирование "'+title+'" для темы "'+theme+'"',
					buttons: '<input type="submit" value="Сохранить" class="button green" /> <em>Ctrl+S</em> или <a href="#" class="cancel">отмена</a>',
					onSubmit: function (d) {
						self.saveChanges();
						return false;
					},
					onLoad:function(){
						$.post('?plugin='+self.plugin_id+'&module=getFileContent',{ name:name,theme:theme },function(response){
							$('#dialog-plugin-design .dialog-content-indent').append('<div id="plugin-block-editor"></div>');
							
							var editor = ace.edit('plugin-block-editor');
							ace.config.set("basePath", wa_url + 'wa-content/js/ace/');
							editor.setTheme("ace/theme/eclipse");
							var session = editor.getSession();
							session.setMode("ace/mode/"+mode);
							session.setUseWrapMode(true);
							editor.setOption("maxLines", 10000);
							editor.setAutoScrollEditorIntoView(true);
							editor.renderer.setShowGutter(false);
							editor.setShowPrintMargin(false);
							
							if (navigator.appVersion.indexOf('Mac') != -1)
								editor.setFontSize(13);
							else if (navigator.appVersion.indexOf('Linux') != -1)
								editor.setFontSize(16);
							else
								editor.setFontSize(14);
							
							$('.ace_editor').css('fontFamily', '');
							
							editor.insert(response.data);
							self.design_data[name] = response.data;
							//$('#plugin-design-textarea').html(response.data);
							
							editor.focus();
							editor.navigateTo(0, 0);
							
							editor.commands.addCommands([{
								name: 'plugindesignSave',
								bindKey: {win: 'Ctrl-S',  mac: 'Ctrl-S'},
								exec: self.saveChanges
							}]);
							
							session.on('change', function() {
								self.design_data[name] = editor.getValue();
								var btn = $('#dialog-plugin-design :submit');
								
								if ( btn.hasClass('green') )
									btn.removeClass('green').addClass('yellow');
							});
						},'json')
					},
					onClose: function(){
						$('div.dialog').remove();
					}
				});
				return false;
			})
		},
		saveChanges: function(){
			var self = this,
				wr = $('#dialog-plugin-design'),
				btn = $(':submit',wr);
				
			btn.after('<i class="icon16 loading"></i>');
			$.post('?plugin='+$('#plugin-submit-btn').data('plugin-id')+'&module=saveFile',self.design_data,function(){
				$('.loading',wr).remove();
				if ( btn.hasClass('yellow') )
					btn.removeClass('yellow').addClass('green');
			});
		},
		successSubmitBtn: function(){
			var btn = $('#plugin-submit-btn');
			if ( btn.hasClass('yellow') )
				btn.removeClass('yellow').addClass('green');
		},
		editSubmitBtn: function(){
			var btn = $('#plugin-submit-btn');
			if ( btn.hasClass('green') )
				btn.removeClass('green').addClass('yellow');
		}
	}

})(jQuery)