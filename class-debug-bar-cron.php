<?php

class ZT_Debug_Bar_Cron extends Debug_Bar_Panel {

	private $_crons;

	private $_core_crons;

	private $_user_crons;

	private $_total_crons = 0;

	function init() {
		$this->title( __( 'Cron', 'debug-bar' ) );
		add_action( 'wp_print_styles', array( $this, 'print_styles' ) );
		add_action( 'admin_print_styles', array( $this, 'print_styles' ) );
	}

	function print_styles() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.dev' : '';
		wp_enqueue_style( 'zt-debug-bar-cron', plugins_url( "css/debug-bar-cron$suffix.css", __FILE__ ), array(), '20120325' );
	}

	function prerender() {
		$this->set_visible( true );
	}

	function render() {
		$this->get_crons();

		$doing_cron = get_transient( 'doing_cron' ) ? 'Yes' : 'No';

		// Get the time of the next event
		$cron_times = array_keys( $this->_crons );

		$unix_time = $cron_times[0];
		$next_cron_human = date( 'Y-m-d H:i:s', $unix_time );
		$time_until_next_cron = human_time_diff( $unix_time );

		echo '<div id="debug-bar-request">';
		echo '<h2><span>' . __( 'Total Events', 'zt-debug-bar-cron' ) . ':</span>' . $this->_total_crons . '</h2>';
		echo '<h2><span>' . __( 'Doing Cron', 'zt-debug-bar-cron' ) . ':</span>' . $doing_cron . '</h2>';
		echo '<h2><span>' . __( 'Next Event', 'zt-debug-bar-cron' ) . ':</span>' . $next_cron_human . ' / ' . $unix_time . ' / ' . $time_until_next_cron . '</h2>';
		echo '<h2><span>' . __( 'Current Time', 'zt-debug-bar-cron' ) . ':</span>' . date( 'H:i:s' ) . '</h2>';
		echo '<div class="clear"></div>';

		echo '<h3>' . __( 'Custom Events', 'zt-debug-bar-cron' ) . '</h3>';

		if ( ! is_null( $this->_user_crons ) ) {
			$this->display_events( $this->_user_crons );
		} else {
			echo '<p>' . __( 'No Custom Events scheduled.', 'zt-debug-bar-cron' ) . '</p>';
		}

		echo '<h3>' . __( 'Core Events', 'zt-debug-bar-cron' ) . '</h3>';

		if ( ! is_null( $this->_core_crons ) ) {
			$this->display_events( $this->_core_crons );
		} else {
			echo '<p>' . __( 'No Core Events scheduled.', 'zt-debug-bar-cron' ) . '</p>';
		}

		echo '</div>';
	}

	function get_crons() {
		if ( ! is_null( $this->_crons ) )
			return $this->_crons;

		if ( ! $crons = _get_cron_array() )
			return $this->_crons;

		$this->_crons = $crons;

		// Lists all crons that are defined in WP Core
		$core_cron_hooks = array(
			'wp_scheduled_delete',
			'upgrader_scheduled_cleanup',
			'importer_scheduled_cleanup',
			'publish_future_post',
			'akismet_schedule_cron_recheck',
			'akismet_scheduled_delete',
			'do_pings',
			'wp_version_check',
			'wp_update_plugins',
			'wp_update_themes'
		);

		foreach ( $this->_crons as $time => $time_cron_array ) {
			foreach ( $time_cron_array as $hook => $data ) {
				$this->_total_crons++;
				if ( in_array( $hook, $core_cron_hooks ) )
					$this->_core_crons[ $time ][ $hook ] = $data;
				else
					$this->_user_crons[ $time ][ $hook ] = $data;
			}
		}

		return $this->_crons;
	}

	function display_events( $events ) {
		if ( is_null( $events ) || empty( $events ) )
			return '';

		echo '<table class="zt-debug-bar-cron-event-table">';
		echo '<thead>';
		echo '<th>' . __( 'Next Execution', 'zt-debug-bar-cron' ) . '</th>';
		echo '<th>' . __( 'Hook', 'zt-debug-bar-cron' ) . '</th>';
		echo '<th>' . __( 'Interval Hook', 'zt-debug-bar-cron' ) . '</th>';
		echo '<th>' . __( 'Interval Value', 'zt-debug-bar-cron' ) . '</th>';
		echo '<th>' . __( 'Args', 'zt-debug-bar-cron' ) . '</th>';
		echo '</thead>';

		foreach ( $events as $time => $time_cron_array ) {
			foreach ( $time_cron_array as $hook => $data ) {
				echo '<tr>';
				echo '<td>' . date( 'Y-m-d H:i:s', $time ) . '<br />' . $time . '</td>';
				echo '<td>' . wp_strip_all_tags( $hook ) . '</td>';

				foreach ( $data as $hash => $info ) {
					echo '<td>' . wp_strip_all_tags( $info['schedule'] ) . '</td>';

					echo '<td>';
					if ( isset( $info['interval'] ) ) {
						echo wp_strip_all_tags( $info['interval'] ) . 's';
						echo '<br />' . $info['interval'] / 60 . 'm';
						echo '<br />' . $info['interval'] / ( 60  * 60 ). 'h';
					}
					echo '</td>';

					echo '<td>';
					if ( ! empty( $info['args'] ) ) {
						foreach ( $info['args'] as $key => $value )
							echo wp_strip_all_tags( $key ) . ' => ' . wp_strip_all_tags( $value );
					}
					echo '</td>';
				}

				echo '</tr>';
			}
		}

		echo '</table>';
	}

	function seconds_to_human() {}


}