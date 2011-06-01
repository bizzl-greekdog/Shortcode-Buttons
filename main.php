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
	}
}

Shortcode_Button::init();
?>