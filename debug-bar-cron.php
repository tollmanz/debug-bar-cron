<?php
/*
Plugin Name: Debug Bar Cron
Plugin URI: http://github.com/tollmanz/
Description: Adds information about WP scheduled events to Debug Bar.
Author: Zack Tollman, Helen Hou-Sandi, Oleg Butuzov
Version: 0.1.3
Author URI: http://github.com/tollmanz
Depends: Debug Bar
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



/**
 * Drop Cron Event ajax action
 * @return void
 */
function wp_ajax_delete_cron_job(){
	if (!current_user_can('manage_options') || !wp_verify_nonce( $_POST['nonce'], 'debug_cron_nonce' ) )
		die();
		
	$ajax = stripslashes_deep($_POST); 
	$ajax['args'] = unserialize($ajax['args']);
	if ($ajax['args'] == false)
		 unset($ajax['args']);

	if (!isset($ajax['args'])){
		$ts = wp_next_scheduled( $ajax['hook'] );
		wp_unschedule_event( $ts, $ajax['hook']);
	} else {
		$ts = wp_next_scheduled( $ajax['hook'] , $ajax['args']);
		wp_unschedule_event( $ts, $ajax['hook'], $ajax['args']);
	}
	
	die();
}
add_action('wp_ajax_delete_cron_job', 'wp_ajax_delete_cron_job');