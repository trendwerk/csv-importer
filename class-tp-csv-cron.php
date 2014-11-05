<?php
/**
 * Handle and perform cronjobs.
 *
 * @package CSV_Importer
 */

class TP_CSV_Cron {

	function __construct() {
		$queue = TP_CSV_Queue::get();

		if( ! wp_next_scheduled( 'tp_csv_importer' ) && 0 < count( $queue ) && 'waiting' === $queue[0]->status['code'] ) {
			wp_schedule_single_event( time(), 'tp_csv_importer' );
		}

		add_action( 'tp_csv_importer', array( $this, 'perform' ) );
	}

	/**
	 * Perform cronjob
	 */
	function perform() {
		$queue = TP_CSV_Queue::get();

		if( 0 < count( $queue ) ) {
			$file = $queue[0];

			/**
			 * Do import
			 */
			$result = TP_CSV_Import::import( $file->attachment_id );

			/**
			 * Set new status
			 */
			TP_CSV_Queue::set_status( $file->attachment_id, $result->status );
		}
	}

} new TP_CSV_Cron;
