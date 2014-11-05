<?php
/**
 * Queue for CSV files.
 *
 * @package	CSV_Importer
 */

class TP_CSV_Queue {

	static $queue_option = 'tp-csv-importer-queue';

	/**
	 * Add file to queue
	 *
	 * @param string $file Key from $_FILES
	 *
	 * @abstract
	 */
	static function add( $file ) {
		if( ! isset( $file ) || ! isset( $_FILES[ $file ] ) )
			return;

		$attachment_id = self::upload( $file );

		if( isset( $attachment_id ) && is_int( $attachment_id ) ) {
			$queue = self::get();

			$queue[] = (object) array(
				'attachment_id' => $attachment_id,
				'status'        => array(
					'code'      => 'waiting',
				),
			);

			self::save( $queue );

			return true;
		}

		return false;
	}

	/**
	 * Uploads file
	 *
	 * @param string Key to $_FILES
	 * @return int Attachment ID
	 *
	 * @abstract
	 */
	static function upload( $file ) {
		if( ! isset( $file ) )
			return;

		$file = media_handle_upload( $file, 0, array(), array(
			'test_form' => false,
			'mimes'     => array(
				'csv'   => 'text/csv',
			),
		) );

		return $file;
	}

	/**
	 * Remove file from queue
	 *
	 * @param int $attachment_id
	 * @return void
	 *
	 * @abstract
	 */
	static function remove( $attachment_id ) {
		$queue = self::get();

		$attachment_id = absint( $attachment_id );

		if( 0 < count( $queue ) ) {
			foreach( $queue as $index => $file ) {
				if( $file->status['code'] === 'processing' )
					continue;

				if( $attachment_id === $file->attachment_id )
					unset( $queue[ $index ] );
			}
		}

		self::save( $queue );
	}

	/**
	 * Set status
	 *
	 * @param int $attachment_id
	 * @param array $status
	 */
	static function set_status( $attachment_id, $status ) {
		$queue = self::get();

		if( 0 < count( $queue ) ) {
			foreach( $queue as &$file ) {
				if( $attachment_id === $file->attachment_id )
					$file->status = $status;
			}
		}

		self::save( $queue );
	}

	/**
	 * Get queue
	 *
	 * @return array The queue
	 * 
	 * @abstract
	 */
	static function get() {
		return array_filter( (array) get_option( self::$queue_option ) );
	}

	/**
	 * Save queue
	 *
	 * @param array $queue
	 *
	 * @abstract
	 */
	static function save( $queue ) {
		if( ! is_array( $queue ) )
			return;

		update_option( self::$queue_option, array_values( $queue ) );
	}

}
