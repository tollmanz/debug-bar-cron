=== Plugin Name ===
Contributors: tollmanz, helenyhou
Donate Link:
Tags: debug bar, cron
Requires at least: 3.3.1
Tested up to: 3.3.1
Stable tag: 0.1

Debug Bar Cron adds a new panel to Debug Bar that displays information about WP scheduled events.

== Description ==

Debug Bar Cron adds information about WP scheduled events to a new panel in the Debug Bar. This plugin is an extension for
Debug Bar and thus is dependent upon Debug Bar being installed for it to work properly.

Once installed, you will have access to the following information:
* Number of scheduled events
* If cron is currently running
* Time of next event
* Current time
* List of custom scheduled events
* List of core scheduled events
* List of schedules

== Installation ==

1. Install Debug Bar if not already installed (http://wordpress.org/extend/plugins/debug-bar/)
2. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Place `<?php do_action('plugin_name_hook'); ?>` in your templates

== Frequently Asked Questions ==

= Is debugging cron easier with this plugin? =

Yes

== Screenshots ==

1. The Debug Bar Cron panel

== Changelog ==

= 0.1 =
* Initial release

== Upgrade Notice ==

= 0.1 =
Initial Release

