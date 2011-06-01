// JavaScript Document
(function() {
	tinymce.create('tinymce.plugins.shortcodes', {
		init : function(ed, url) {
			ed.addButton('shortcodes', {
				title : 'Shortcodes',
				image : 'http://local.lemontree.de/wp-content/plugins/swfobj/media-button-flash.gif', //url+'../images/mylink.png',
				onclick : function() {
					ed.selection.setContent('[mylink]' + ed.selection.getContent() + '[/mylink]');
 
				}
			});
		},
		createControl : function(n, cm) {
			return null;
		}
	});
	tinymce.PluginManager.add('shortcodes', tinymce.plugins.shortcodes);
})();