<?php
/**
 * Debug Bar Cron - Debug Bar Panel.
 *
 * @package     WordPress\Plugins\Debug Bar Cron
 * @author      Zack Tollman, Helen Hou-Sandi, Juliette Reinders Folmer
 * @link        https://github.com/tollmanz/debug-bar-cron
 * @version     0.1.2
 * @license     http://creativecommons.org/licenses/GPL/2.0/ GNU General Public License, version 2 or higher
 */

// Avoid direct calls to this file.
if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * The class in this file extends the functionality provided by the parent plugin "Debug Bar".
 */
if ( ! class_exists( 'ZT_Debug_Bar_Cron' ) && class_exists( 'Debug_Bar_Panel' ) ) {

	/**
	 * Add a new Debug Bar Panel.
	 */
	class ZT_Debug_Bar_Cron extends Debug_Bar_Panel {

		const DBCRON_STYLES_VERSION = '1.0';

		const DBCRON_NAME = 'debug-bar-cron';

		/**
		 * Holds all of the cron events.
		 *
		 * @var array
		 */
		private $_crons;

		/**
		 * Holds only the cron events initiated by WP core.
		 *
		 * @var array
		 */
		private $_core_crons;

		/**
		 * Holds the cron events created by plugins or themes.
		 *
		 * @var array
		 */
		private $_user_crons;

		/**
		 * Total number of cron events.
		 *
		 * @var int
		 */
		private $_total_crons = 0;

		/**
		 * Total number of cron events created by plugins and themes.
		 *
		 * @var int
		 */
		private $_total_user_crons = 0;

		/**
		 * Total number of WP core cron events.
		 *
		 * @var int
		 */
		private $_total_core_crons = 0;

		/**
		 * Whether cron is being executed or not.
		 *
		 * @var string
		 */
		private $_doing_cron = 'No';


		/**
		 * Give the panel a title and set the enqueues.
		 *
		 * @return void
		 */
		public function init() {
			load_plugin_textdomain( 'zt-debug-bar-cron', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
			$this->title( __( 'Cron', 'zt-debug-bar-cron' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
		}


		/**
		 * Enqueue styles.
		 *
		 * @return void
		 */
		public function enqueue_scripts_styles() {
			$suffix = ( ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min' );

			wp_enqueue_style(
				self::DBCRON_NAME,
				plugins_url( 'css/' . self::DBCRON_NAME . $suffix . '.css', __FILE__ ),
				array( 'debug-bar' ),
				self::DBCRON_STYLES_VERSION
			);
		}


		/**
		 * Show the menu item in Debug Bar.
		 *
		 * @return void
		 */
		public function prerender() {
			$this->set_visible( true );
		}


		/**
		 * Show the contents of the page.
		 *
		 * @return void
		 */
		public function render() {
			$this->get_crons();

			$this->_doing_cron = get_transient( 'doing_cron' ) ? __( 'Yes', 'zt-debug-bar-cron' ) : __( 'No', 'zt-debug-bar-cron' );

			// Get the time of the next event.
			$cron_times          = ( is_array( $this->_crons ) ? array_keys( $this->_crons ) : array() );
			$unix_time_next_cron = (int) $cron_times[0];
			$time_next_cron      = date( 'Y-m-d H:i:s', $unix_time_next_cron );

			$human_time_next_cron = human_time_diff( $unix_time_next_cron );

			// Add a class if past current time and doing cron is not running.
			$times_class = $this->is_time_in_past( $unix_time_next_cron ) ? ' past' : '';

			$this->load_debug_bar_pretty_output();

			echo  // WPCS: XSS ok.
			'
			<div class="debug-bar-cron">
				<h2><span>', esc_html__( 'Total Events', 'zt-debug-bar-cron' ), ':</span>', $this->_total_crons, '</h2>
				<h2><span>', esc_html__( 'Core Events', 'zt-debug-bar-cron' ), ':</span>', $this->_total_core_crons, '</h2>
				<h2><span>', esc_html__( 'Custom Events', 'zt-debug-bar-cron' ), ':</span>', $this->_total_user_crons, '</h2>
				<h2><span>', esc_html__( 'Doing Cron', 'zt-debug-bar-cron' ), ':</span>', esc_html( $this->_doing_cron ), '</h2>
				<h2 class="times', esc_attr( $times_class ), '"><span>', esc_html__( 'Next Event', 'zt-debug-bar-cron' ), ':</span>
					', esc_html( $time_next_cron ), '<br />
					', $unix_time_next_cron, '<br />
					', esc_html( $this->display_past_time( $human_time_next_cron, $unix_time_next_cron ) ), '
				</h2>
				<h2><span>', esc_html__( 'Current Time', 'zt-debug-bar-cron' ), ':</span>', esc_html( date( 'H:i:s' ) ), '</h2>

				<div class="clear"></div>

				<h3>', esc_html__( 'Schedules', 'zt-debug-bar-cron' ), '</h3>';

			$this->display_schedules();

			echo '
				<h3>', esc_html__( 'Custom Events', 'zt-debug-bar-cron' ), '</h3>';

			$this->display_events( $this->_user_crons, __( 'No Custom Events scheduled.', 'zt-debug-bar-cron' ) );

			echo '
				<h3>', esc_html__( 'Core Events', 'zt-debug-bar-cron' ), '</h3>';

			$this->display_events( $this->_core_crons, __( 'No Core Events scheduled.', 'zt-debug-bar-cron' ) );

			echo '
			</div>';

			$this->reset_debug_bar_pretty_output();
		}


		/**
		 * Gets all of the cron jobs.
		 *
		 * This function sorts the cron jobs into core crons, and custom crons. It also tallies
		 * a total count for the crons as this number is otherwise tough to get.
		 *
		 * @return array|null Array of crons.
		 */
		private function get_crons() {
			if ( is_array( $this->_crons ) ) {
				return $this->_crons;
			}

			$crons = _get_cron_array();
			if ( ! is_array( $crons ) || array() === $crons  ) {
				return $this->_crons;
			}

			$this->_crons = $crons;

			// Lists all crons that are defined in WP Core.
			// @internal To find all, search WP trunk for `wp_schedule_(single_)?event`.
			$core_cron_hooks = array(
				'do_pings',
				'importer_scheduled_cleanup',     // WP 3.1+.
				'publish_future_post',
				'update_network_counts',          // WP 3.1+.
				'upgrader_scheduled_cleanup',     // WP 3.3+.
				'wp_maybe_auto_update',           // WP 3.7+.
				'wp_scheduled_auto_draft_delete', // WP 3.4+.
				'wp_scheduled_delete',            // WP 2.9+.
				'wp_split_shared_term_batch',     // WP 4.3+.
				'wp_update_plugins',
				'wp_update_themes',
				'wp_version_check',
			);

			// Sort and count crons.
			foreach ( $this->_crons as $time => $time_cron_array ) {
				foreach ( $time_cron_array as $hook => $data ) {
					$this->_total_crons += count( $data );

					if ( in_array( $hook, $core_cron_hooks, true ) ) {
						$this->_core_crons[ $time ][ $hook ] = $data;
						$this->_total_core_crons            += count( $data );
					} else {
						$this->_user_crons[ $time ][ $hook ] = $data;
						$this->_total_user_crons            += count( $data );
					}
				}
			}

			return $this->_crons;
		}


		/**
		 * Displays the events in an easy to read table.
		 *
		 * @param array  $events        Array of events.
		 * @param string $no_events_msg Message to display if there are no events.
		 */
		private function display_events( $events, $no_events_msg ) {
			// Exit early if no events found.
			if ( ! is_array( $events ) || empty( $events ) ) {
				echo '
				<p>', esc_html( $no_events_msg ), '</p>';
				return;
			}

			echo '
				<table class="zt-debug-bar-cron-event-table">
					<thead><tr>
						<th class="col1">', esc_html__( 'Next Execution', 'zt-debug-bar-cron' ), '</th>
						<th class="col2">', esc_html__( 'Hook', 'zt-debug-bar-cron' ), '</th>
						<th class="col3">', esc_html__( 'Interval Hook', 'zt-debug-bar-cron' ), '</th>
						<th class="col4">', esc_html__( 'Interval Value', 'zt-debug-bar-cron' ), '</th>
						<th class="col5">', esc_html__( 'Args', 'zt-debug-bar-cron' ), '</th>
					</tr></thead>
					<tbody>';

			foreach ( $events as $time => $time_cron_array ) {
				foreach ( $time_cron_array as $hook => $data ) {
					foreach ( $data as $hash => $info ) {
						echo '
						<tr>
							<td';

						// Add a class if past current time.
						if ( $this->is_time_in_past( $time ) ) {
							echo ' class="past"';
						}

						echo '>
								', esc_html( date( 'Y-m-d H:i:s', $time ) ), '<br />
								', intval( $time ), '<br />
								', esc_html( $this->display_past_time( human_time_diff( $time ), $time ) ), '
							</td>
							<td>', esc_html( $hook ), '</td>';


						// Report the schedule.
						echo '
							<td>';
						if ( $info['schedule'] ) {
							echo esc_html( $info['schedule'] );
						} else {
							echo esc_html__( 'Single Event', 'zt-debug-bar-cron' );
						}
						echo '</td>';

						// Report the interval.
						echo '
							<td>';
						if ( isset( $info['interval'] ) ) {
							$interval = (int) $info['interval'];
							/* TRANSLATORS: %s is number of seconds. */
							printf( esc_html__( '%ss', 'zt-debug-bar-cron' ) . '<br />', $interval ); // WPCS: XSS ok.
							/* TRANSLATORS: %s is number of minutes. */
							printf( esc_html__( '%sm', 'zt-debug-bar-cron' ) . '<br />', ( $interval / 60 ) ); // WPCS: XSS ok.
							/* TRANSLATORS: %s is number of hours. */
							printf( esc_html__( '%sh', 'zt-debug-bar-cron' ), ( $interval / ( 60 * 60 ) ) ); // WPCS: XSS ok.
						} else {
							echo esc_html__( 'Single Event', 'zt-debug-bar-cron' );
						}
						echo '</td>';

						// Report the args.
						echo '
							<td>';
						$this->display_cron_arguments( $info['args'] );
						echo '</td>
						</tr>';
					}
				}
			}

			echo '
					</tbody>
				</table>';
		}


		/**
		 * Displays the cron arguments in a readable format.
		 *
		 * @param mixed $args Cron argument(s).
		 *
		 * @return void
		 */
		private function display_cron_arguments( $args ) {
			// Arguments defaults to an empty array if no arguments are given.
			if ( is_array( $args ) && array() === $args ) {
				echo esc_html__( 'No Args', 'zt-debug-bar-cron' );
				return;
			}

			// Ok, we have an argument, let's pretty print it.
			$this->print_pretty_output( $args );
		}


		/**
		 * Displays all of the schedules defined.
		 *
		 * @return void
		 */
		private function display_schedules() {
			echo '
				<table class="zt-debug-bar-cron-event-table">
					<thead><tr>
						<th class="col1">', esc_html__( 'Interval Hook', 'zt-debug-bar-cron' ), '</th>
						<th class="col2">', esc_html__( 'Interval (S)', 'zt-debug-bar-cron' ), '</th>
						<th class="col3">', esc_html__( 'Interval (M)', 'zt-debug-bar-cron' ), '</th>
						<th class="col4">', esc_html__( 'Interval (H)', 'zt-debug-bar-cron' ), '</th>
						<th class="col5">', esc_html__( 'Display Name', 'zt-debug-bar-cron' ), '</th>
					</tr></thead>
					<tbody>';


			$schedules = wp_get_schedules();
			foreach ( $schedules as $interval_hook => $data ) {
				$interval = (int) $data['interval'];
				echo  // WPCS: XSS ok.
				'
						<tr>
							<td>', esc_html( $interval_hook ), '</td>
							<td>', $interval, '</td>
							<td>', ( $interval / 60 ), '</td>
							<td>', ( $interval / ( 60 * 60 ) ), '</td>
							<td>', esc_html( $data['display'] ) . '</td>
						</tr>';
			}

			echo '
					</tbody>
				</table>';
		}


		/**
		 * Verify if a given timestamp is in the past or the future.
		 *
		 * @param int $time Timestamp.
		 *
		 * @return bool True if the time has passed, false otherwise.
		 */
		private function is_time_in_past( $time ) {
			return ( time() > $time && 'No' === $this->_doing_cron );
		}


		/**
		 * Compares time with current time and adds ' ago' if current time is greater than event time.
		 *
		 * @param string $human_time Human readable time difference.
		 * @param int    $time       Unix time of event.
		 *
		 * @return string
		 */
		private function display_past_time( $human_time, $time ) {
			if ( time() > $time ) {
				/* TRANSLATORS: %s is a human readable time difference. */
				return sprintf( __( '%s ago', 'zt-debug-bar-cron' ), $human_time );
			} else {
				return $human_time;
			}
		}


		/**
		 * Load the pretty output class & set the recursion limit.
		 */
		private function load_debug_bar_pretty_output() {
			if ( ! class_exists( 'Debug_Bar_Pretty_Output' ) ) {
				require_once plugin_dir_path( __FILE__ ) . 'inc/debug-bar-pretty-output/class-debug-bar-pretty-output.php';
			}

			// Limit recursion depth if possible - method available since DBPO v1.4.
			if ( method_exists( 'Debug_Bar_Pretty_Output', 'limit_recursion' ) ) {
				Debug_Bar_Pretty_Output::limit_recursion( 2 );
			}
		}


		/**
		 * Print any type of variable with colour coding and a variable type indication.
		 *
		 * @param mixed $variable The variable to print.
		 */
		private function print_pretty_output( $variable ) {
			if ( defined( 'Debug_Bar_Pretty_Output::VERSION' ) ) {
				echo Debug_Bar_Pretty_Output::get_output( $variable, '', true ); // WPCS: XSS ok.
			} else {
				// An old version of the pretty output class was loaded.
				// Real possibility as there are several DB plugins using the pretty print class.
				Debug_Bar_Pretty_Output::output( $variable, '', true );
			}
		}


		/**
		 * Unset recursion depth limit for the pretty output class.
		 *
		 * @internal Method available since DBPO v1.4.
		 */
		private function reset_debug_bar_pretty_output() {
			if ( method_exists( 'Debug_Bar_Pretty_Output', 'unset_recursion_limit' ) ) {
				Debug_Bar_Pretty_Output::unset_recursion_limit();
			}
		}
	} // End of class ZT_Debug_Bar_Cron.

} // End of if class_exists wrapper.
