<?php
/**
 * Plugin Name: Strong Testimonials - Migrate from Easy Testimonials
 * Plugin URI: https://strongtestimonials.com/
 * Description: Submodule that helps migrate testimonials from Easy Testimonials to Strong Testimonials
 * Author: WPChill
 * Author URI: https://www.wpchill.com/
 * Version: 1.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPMTST_ET_MIGRATOR_VERSION', '1.0.1' );
define( 'WPMTST_ET_MIGRATOR_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPMTST_ET_MIGRATOR_URL', plugin_dir_url( __FILE__ ) );
define( 'WPMTST_ET_MIGRATOR_FILE', __FILE__ );


add_action( 'plugins_loaded', 'wpmtst_migrate_away_easytestimonials_set_locale', 15 );
add_action( 'plugins_loaded', 'run_wpmtst_migrate_away_easytestimonials', 15 );

/**
 * Set localization for the plugin
 *
 * @return void
 * @since 1.0.0
 */
function wpmtst_migrate_away_easytestimonials_set_locale() {

	load_plugin_textdomain( 'et-st-migrator', false, dirname( plugin_basename( WPMTST_ET_MIGRATOR_FILE ) ) . '/languages/' );
}

/**
 * Load the main plugin class.
 *
 * @return void
 * @since 1.0.0
 */
function run_wpmtst_migrate_away_easytestimonials() {
	require_once WPMTST_ET_MIGRATOR_PATH . 'includes/class-st-et-migrator.php';
	$load = new ST_ET_Migrator();
}
