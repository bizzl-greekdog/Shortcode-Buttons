<?php
/*
Plugin Name:	Shortcode Buttons
Plugin URI:		https://github.com/bizzl-greekdog/Shortcode-Buttons
Description:	Allows the super admin to create shortcodes and corresponding buttons in the rich editor.
Version:		1.0.0
Author:			Benjamin Kleiner
Author URI:		https://github.com/bizzl-greekdog
License:		LGPL3
*/
/*
    Copyright (c) 2011 Benjamin Kleiner <bizzl@users.sourceforge.net>
 
    This file is part of Shortcode Buttons.

    Shortcode Buttons is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Shortcode Buttons is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with Shortcode Buttons. If not, see <http://www.gnu.org/licenses/>.
*/

if (!function_exists('join_path')) {

	// This is an implementation of pythons sys.path.join()
	// As the name suggest, join_path takes all arguments and joins
	// them using the directory separator.
	function join_path() {
		$fuck = func_get_args();
		$flat = (object)array('flat' => array());
		array_walk_recursive($fuck, create_function('&$v, $k, &$t', '$t->flat[] = $v;'), $flat);
		$f = implode(DIRECTORY_SEPARATOR, $flat->flat);
		return preg_replace('/(?<!:)\\' . DIRECTORY_SEPARATOR . '+/', DIRECTORY_SEPARATOR, $f);
	}

}

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tag.php');

class Shortcode_Buttons {

	protected static $domain = 'shortcode-button';
	protected static $base = '';

	protected static function init_base() {
		self::$base = basename(dirname(__FILE__));
	}

	protected static function init_l10n() {
		$j = join_path(self::$base, 'locale');
		load_plugin_textdomain(self::$domain, false, $j);
	}

	public static function init() {
		self::init_base();
		self::init_l10n();
		add_action('init', array(__CLASS__, 'init_button'));
		add_action('init', array(__CLASS__, 'init_shortcodes'));
		add_action('network_admin_menu', array(__CLASS__, 'network_admin_menu'));
	}

	public static function network_admin_menu() {
		add_submenu_page(
				'settings.php',
				__('Manage Shortcodes', self::$domain),
				__('Shortcodes', self::$domain),
				'manage_options', basename(__FILE__),
				array(__CLASS__, 'manage_shortcodes')
		);
		wp_enqueue_script("jquery");
		wp_enqueue_style(__CLASS__ . '_main', join_path(plugin_dir_url(__FILE__), 'css', 'main.css'));
	}

	public static function manage_shortcodes() {
		$shortcodes = self::get_shortcodes();
		
		$defaults = array(
			'title' => '',
			'name' => '',
			'mode' => 0,
			'icon' => '',
			'replacement' => ''
		);
		
		$modes = array(
			-1 => __('Prepend', self::$domain),
			 0 => __('Surround', self::$domain),
			 1 => __('Append', self::$domain),
			 2 => __('Replace', self::$domain)
		);
		
		echo h(__('Manage Shortcodes', self::$domain), 2);
		
		if (isset($_POST['delete']) || isset($_POST['add'])) {
			if (isset($_POST['delete']))
				foreach ($_POST['deleteMe'] as $name) {
					unset($shortcodes[$name]);
					unlink(self::script_for_name($name));
				}
				
			$_POST['replacement'] = stripslashes($_POST['replacement']);
			if (isset($_POST['add'])) {
				try {
					if (empty($_POST['name']))
						throw new Exception(__('Shortcode name is empty', self::$domain), 1232, null);
					$shortcodes[$_POST['name']] = array(
						'title' => $_POST['title'],
						'icon' => $_POST['icon'],
						'mode' => $_POST['mode'],
						'replacement' => $_POST['replacement']
					);
					self::write_script($_POST['name'], $shortcodes[$_POST['name']]);
				} catch (Exception $e) {
					
					echo div(
						bold(sprintf('FAILURE [%s] %s', $e->getCode(), $e->getMessage())), br(),
						sprintf('in %s, line %s', $e->getFile(), $e->getLine()), br(),
						code($e->getTraceAsString())
					)->addClass('error');
					echo br();
					$defaults = $_POST;
				}
			}
			self::set_shortcodes($shortcodes);
		}
		
		$table = tag('tbody')->attr('id', 'the-list');
		
		foreach ($shortcodes as $name => $options) {
			$table->append(
				tag('tr')->addClass('alternate', 'author-self', 'status-inherit')->append(
					tag('th')->addClass('check-column')->attr('scope', 'row')->append(checkbox('deleteMe[]', "deleteMe-{$name}", false, false, $name)),
					tag('td')->addClass('column-name')->append($name),
					tag('td')->addClass('column-icon')->append(img($options['icon'])),
					tag('td')->addClass('column-title')->append($options['title']),
					tag('td')->addClass('column-replacement')->append(code(htmlentities($options['replacement']))),
					tag('td')->addClass('column-mode')->append($modes[$options['mode']]),
					tag('td')->addClass('column-edit')->append(
						tag('button')->attr(array(
							'type' => 'button',
							'data-options' => json_encode(array_merge(array('name' => $name), $options))
						))->addClass('button-secondary', 'edit')->append(__('Edit', self::$domain))
					)
				)
			);
		}
		
		$t = __('Replace', self::$domain);
		echo script(array(
			'code' => <<<EOF
jQuery(function($) {
	$("button.edit").click(function() {
		var options = $.parseJSON($(this).attr("data-options"));
		$("#name").val(options.name);
		$("#icon").val(options.icon);
		$("#title").val(options.title);
		$("#replacement").val(options.replacement);
		
		$("#add-button").text("{$t}");

		$("#mode").val(options.mode);
		var o = $("#shortcode-editor").offset();
		window.scrollTo(o.top. o.left);
	});
});
EOF
		));
		echo div(
			$form = tag('form')->attr(array('action' => '', 'method' => 'post'))->append(
				tag('table')->addClass('wp-list-table', 'widefat', 'fixed', 'shortcode-list')->append(
					tag('thead')->append(
						tag('tr')->append(
							tag('th')->addClass('manage-column', 'column-cb', 'check-column')->attr('scope', 'col')->append(checkbox('cb', 'cb')->addClass('check-all')),
							tag('th')->addClass('manage-column', 'column-name')->attr('scope', 'col')->append(__('Shortcode', self::$domain)),
							tag('th')->addClass('manage-column', 'column-icon')->attr('scope', 'col')->append(__('Icon', self::$domain)),
							tag('th')->addClass('manage-column', 'column-title')->attr('scope', 'col')->append(__('Title', self::$domain)),
							tag('th')->addClass('manage-column', 'column-replacement')->attr('scope', 'col')->append(__('Replacement Text', self::$domain)),
							tag('th')->addClass('manage-column', 'column-mode')->attr('scope', 'col')->append(__('Mode', self::$domain)),
							tag('th')->addClass('manage-column', 'column-edit')->attr('scope', 'col')->append(__('Edit', self::$domain))
						)
					),
					$table
				),
				div(
					tag('button')->attr(array('name' => 'delete', 'type' => 'submit'))->addClass('button-secondary', 'delete')->append(__('Delete Selected', self::$domain))
				)->addClass('tablenav', 'bottom'),
				tag('table')->attr('id', 'shortcode-editor')->append(
					tag('tr')->append(
						tag('td')->append(
							tag('label')->attr('for', 'name')->append(__('Shortcode', self::$domain))
						),
						tag('td')->addClass('name-editor-cell')->append(
							tag('input')->attr(array('type' => 'text', 'name' => 'name', 'id' => 'name', 'value' => $defaults['name']))
						)
					),
					tag('tr')->append(
						tag('td')->append(
							tag('label')->attr('for', 'icon')->append(__('Icon', self::$domain))
						),
						tag('td')->append(
							tag('input')->attr(array('type' => 'text', 'name' => 'icon', 'id' => 'icon', 'value' => $defaults['icon']))
						)
					),
					tag('tr')->append(
						tag('td')->append(
							tag('label')->attr('for', 'title')->append(__('Button Title', self::$domain))
						),
						tag('td')->append(
							tag('input')->attr(array('type' => 'text', 'name' => 'title', 'id' => 'title', 'value' => $defaults['title']))
						)
					),
					tag('tr')->append(
						tag('td')->append(
							tag('label')->attr('for', 'replacement')->append(__('Replacement Text', self::$domain))
						),
						tag('td')->append(
							tag('textarea', true)->attr(array('type' => 'text', 'name' => 'replacement', 'id' => 'replacement'))->append($defaults['replacement'])
						)
					),
					tag('tr')->append(
						tag('td')->append(
							tag('label')->attr('for', 'mode')->append(__('Mode', self::$domain))
						),
						tag('td')->append(
							tag('select')->attr(array(
								'name' => 'mode',
								'id' => 'mode'
							))->append(options($modes, $defaults['mode']))
						)
//						tag('td')->append(
//							tag('input')->attr(array('type' => 'text', 'name' => 'mode', 'id' => 'mode', 'value' => ''))
//						)
					)
				),
				div(
					tag('button')->attr(array('name' => 'add', 'type' => 'submit', 'id' => 'add-button'))->addClass('button-secondary', 'add')->append(__('Add New', self::$domain))
				)->addClass('tablenav', 'bottom')
			)
		)->addClass('wrap');
	}
	
	private static function get_shortcodes() {
		$f = unserialize(get_site_option('ms-shortcodes', '', false));
		if (!$f)
			$f = array();
		return $f;
	}
	
	private static function set_shortcodes($shortcodes) {
		update_site_option('ms-shortcodes', serialize($shortcodes));
	}
	
	private static function script_for_name($name, $url = false) {
		return join_path($url ? plugin_dir_url(__FILE__) : plugin_dir_path(__FILE__), 'js', 'buttons', "button-${name}.js");
	}
	
	private static function write_script($name, $options) {
		$path = self::script_for_name($name);
		$newContent = 'ed.selection.getContent()';
		if ($options['mode'] == -1)
			$newContent = "'[{$name}]' + " . $newContent;
		elseif ($options['mode'] == 0)
			$newContent = "'[{$name}]' + " . $newContent . " + '[/{$name}]'";
		elseif ($options['mode'] == 1)
			$newContent .= " + '[{$name}]'";
		else
			$newContent = "'[{$name}]'";
		$content = <<<EOF
(function() {
	tinymce.create('tinymce.plugins.shortcode_{$name}', {
		init : function(ed, url) {
			ed.addButton('shortcode_{$name}', {
				title : '{$options['title']}',
				image : '{$options['icon']}',
				onclick : function() {
					ed.selection.setContent({$newContent});
 
				}
			});
		},
		createControl : function(n, cm) {
			return null;
		}
	});
	tinymce.PluginManager.add('shortcode_{$name}', tinymce.plugins.shortcode_{$name});
})();
EOF;
		$f = fopen($path, "w+");
		fwrite($f, $content);
		fclose($f);
	}

	public static function init_button() {
		if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
			return;
		}

		if (get_user_option('rich_editing') == 'true') {
			add_filter('mce_external_plugins', array(__CLASS__, 'add_plugin'));
			add_filter('mce_buttons', array(__CLASS__, 'register_button'));
		}
	}
	
	public static function init_shortcodes() {
		$shortcodes = self::get_shortcodes();
		foreach(array_keys($shortcodes) as $shortcode)
			add_shortcode($shortcode, array(__CLASS__, 'do_shortcode'));
	}
	
	public static function do_shortcode($atts, $content, $name = false) {
		if (!$name)
			return $content;
		$shortcodes = self::get_shortcodes();
		$atts['content'] = do_shortcode($content);
		$atts['raw_content'] = $content;
		$code = $shortcodes[$name]['replacement'];
		foreach($atts as $key => $value)
			$code = str_replace('${' . $key . '}', $value, $code);
		return $code;
	}

	public static function add_plugin($plugin_array) {
		$shortcodes = self::get_shortcodes();
		foreach(array_keys($shortcodes) as $shortcode)
			$plugin_array['shortcode_' . $shortcode] = self::script_for_name($shortcode, true);
		return $plugin_array;
	}

	public static function register_button($buttons) {
		array_push($buttons, "|");
		$shortcodes = self::get_shortcodes();
		foreach(array_keys($shortcodes) as $shortcode)
			array_push($buttons, "shortcode_{$shortcode}");
		return $buttons;
	}

}

Shortcode_Buttons::init();
?>