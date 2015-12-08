<?php
/**
 * Debug Bar Cron, a WordPress plugin.
 *
 * @package     WordPress\Plugins\Debug Bar Cron
 * @author      Zack Tollman, Helen Hou-Sandi, Juliette Reinders Folmer
 * @link        https://github.com/tollmanz/debug-bar-cron
 * @version     0.1.2
 * @license     http://creativecommons.org/licenses/GPL/2.0/ GNU General Public License, version 2 or higher
 *
 * @wordpress-plugin
 * Plugin Name:	Debug Bar Cron
 * Plugin URI:	http://wordpress.org/extend/plugins/debug-bar-cron/
 * Description:	Debug Bar Cron adds information about WP scheduled events to the Debug Bar.
 * Version:		0.1.2
 * Author:		Zack Tollman, Helen Hou-Sandi
 * Author URI:	http://github.com/tollmanz/
 * Depends:     Debug Bar
 * Text Domain:	zt-debug-bar-cron
 * Domain Path:	/languages/
 */

// Avoid direct calls to this file.
if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( ! function_exists( 'debug_bar_cron_has_parent_plugin' ) ) {
	/**
	 * Show admin notice & de-activate if debug-bar plugin not active.
	 */
	function debug_bar_cron_has_parent_plugin() {
		if ( is_admin() && ( ! class_exists( 'Debug_Bar' ) && current_user_can( 'activate_plugins' ) ) ) {
			add_action( 'admin_notices', create_function( null, 'echo \'<div class="error"><p>\', sprintf( __( \'Activation failed: Debug Bar must be activated to use the <strong>Debug Bar Cron</strong> Plugin. %sVisit your plugins page to activate.\', \'zt-debug-bar-cron\' ), \'<a href="\' . esc_url( admin_url( \'plugins.php#debug-bar\' ) ) . \'">\' ), \'</a></p></div>\';' ) );

			deactivate_plugins( plugin_basename( __FILE__ ) );
			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}
		}
	}
	add_action( 'admin_init', 'debug_bar_cron_has_parent_plugin' );
}


if ( ! function_exists( 'zt_add_debug_bar_cron_panel' ) ) {
	/**
	 * Adds panel, as defined in the included class, to Debug Bar.
	 *
	 * @param array $panels Existing debug bar panels.
	 *
	 * @return array
	 */
	function zt_add_debug_bar_cron_panel( $panels ) {
		if ( ! class_exists( 'ZT_Debug_Bar_Cron' ) ) {
			require_once 'class-debug-bar-cron.php';
			$panels[] = new ZT_Debug_Bar_Cron();
		}
		return $panels;
	}
	add_filter( 'debug_bar_panels', 'zt_add_debug_bar_cron_panel' );
}


/**
 * Drop cron event ajax action.
 *
 * @return void
 */
function wp_ajax_delete_cron_job(){

	if ( !current_user_can( 'manage_options' ) || !isset( $_POST['nonce'] )
		|| !wp_verify_nonce( $_POST['nonce'], 'debug_cron_nonce' ) ) {
			die();
	}

	$ajax = stripslashes_deep( $_POST );
	$ajax['args'] = unserialize( base64_decode( $ajax['args'] ) );
	$ajax['args'] = $ajax['args'] === false ? array() : $ajax['args'];
	if ( false !== ( $ts = wp_next_scheduled( $ajax['hook'] , $ajax['args'] ) ) )
		wp_unschedule_event( $ts, $ajax['hook'], $ajax['args']);

	die();
}

add_action( 'wp_ajax_delete_cron_job', 'wp_ajax_delete_cron_job' );
