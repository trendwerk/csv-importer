<?php
/**
 * Handle and perform cronjobs.
 *
 * @package CSV_Importer
 */

class TP_CSV_Cron {

	function __construct() {
		$queue = TP_CSV_Queue::get();

		if( ! wp_next_scheduled( 'tp_csv_importer' ) && 0 < count( $queue ) ) {
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

			foreach( $queue as $file ) {

				if( 'processing' === $file->status['code'] )
					break;

				if( 'waiting' !== $file->status['code'] )
					continue;

				/**
				 * Do import
				 */
				$importer = new TP_CSV_Import( $file->attachment_id, $file->taxonomy, $file->term );
				$result = $importer->start();

				/**
				 * Set new status
				 */
				TP_CSV_Queue::set_status( $file->attachment_id, $result->status );

				break;
			}
		}
	}

} new TP_CSV_Cron;
