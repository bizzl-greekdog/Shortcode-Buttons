<?php

/*
  Plugin Name: Shortcode Button
  Plugin URI:
  Description: Adds a shortcode button to the rich editor.
  Version: 1.0.0
  Author: Benjamin Kleiner <bizzl@users.sourceforge.net>
  Author URI:
  License: LGPL3
 */

if (!function_exists('join_path')) {

	function join_path() {
		$fuck = func_get_args();
		return implode(DIRECTORY_SEPARATOR, $fuck);
	}

}

class Shortcode_Button {

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
	}

	public static function init_button() {

		if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
			return;
		}

		if (get_user_option('rich_editing') == 'true') {
			add_filter('mce_external_plugins', array(__CLASS__, 'add_plugin'));
			add_filter('mce_buttons', array(__CLASS__, 'register_button'));
			add_action('admin_print_scripts', array(__CLASS__, 'init_scripts'));
		}
	}
	
	public static function init_scripts() {
		global $shortcode_tags;
		wp_enqueue_script('jquery-ui-dialog');
		echo '<script id="lol">' . print_r(array_keys($shortcode_tags), true) . '</script>';
	}

	public static function add_plugin($plugin_array) {
		add_shortcode();
		$plugin_array['shortcodes'] = join_path(plugin_dir_url(__FILE__), 'js', 'shortcodes.js');
		return $plugin_array;
	}

	public static function register_button($buttons) {
		array_push($buttons, "|", "shortcodes");
		return $buttons;
	}

}

Shortcode_Button::init();
?>