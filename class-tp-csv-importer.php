<?php
/**
 * Plugin Name: CSV Importer
 * Description: Starter plugin for creating a CSV import tool.
 *
 * @package	CSV_Importer
 */

include_once( 'class-tp-csv-admin.php' );
include_once( 'class-tp-csv-queue.php' );
include_once( 'class-tp-csv-cron.php' );
include_once( 'class-tp-csv-import.php' );

class TP_CSV_Importer {

	function __construct() {
		add_action( 'plugins_loaded', array( $this, 'localization' ) );	
	}

	/**
	 * Load localization
	 */
	function localization() {
		load_muplugin_textdomain( 'tp-csv-importer', dirname( plugin_basename( __FILE__ ) ) . '/assets/lang/' );
	}

} new TP_CSV_Importer;
