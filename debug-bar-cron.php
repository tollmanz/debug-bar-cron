<?php
/*
Plugin Name: Debug Bar Cron
Plugin URI: http://wordpress.org/extend/plugins/debug-bar-cron/
Description: Adds information about WP scheduled events to Debug Bar.
Author: tollmanz
Version: 0.1
Author URI: http://twitter.com/zack_dev
*/

/**
 * Adds panel, as defined in the included class, to Debug Bar.
 *
 * @param $panels array
 * @return array
 */
function zt_add_debug_bar_cron_panel( $panels ) {
	if ( ! class_exists( 'ZT_Debug_Bar_Cron' ) ) {
		include ( 'class-debug-bar-cron.php' );
		$panels[] = new ZT_Debug_Bar_Cron();
	}
	return $panels;
}
add_filter( 'debug_bar_panels', 'zt_add_debug_bar_cron_panel' );