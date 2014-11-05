<?php
/**
 * Import CSV to WordPress
 *
 * @package CSV_Importer
 */

class TP_CSV_Import {

	/**
	 * Import a file
	 *
	 * @param int $attachment_id
	 * 
	 * @return string Status code
	 *
	 * @abstract
	 */
	static function import( $attachment_id ) {
		self::set_progress( $attachment_id, 0 );

		/**
		 * Read file
		 */
		$file_path = get_attached_file( $attachment_id );

		if( $handle = @fopen( $file_path, 'r' ) ) {
			$head = fgetcsv( $handle, 0, ';' );

			while( $entry = fgetcsv( $handle, 0, ';' ) ) {
				$entry = array_combine( $head, $entry );

				self::add_entry( $entry );
			}
		} else {
			return (object) array(
				'status'      => array(
					'code'    => 'failed',
					'message' => __( 'Couldn\'t read file.', 'tp' ),
				),
			);
		}

		/**
		 * Report back to TP_CSV_Cron
		 */
		return (object) array(
			'status'   => array(
				'code' => 'success',
			),
		);
	}

	/**
	 * Add new entry
	 *
	 * @param array $reference
	 *
	 * @abstract
	 */
	static function add_entry( $reference ) {
		if( ! isset( $reference ) || ! is_array( $reference ) )
			return;

		$reference = (object) $reference;

		$new = array(
			'post_author'  => 1,
			'post_content' => $reference->content,
			'post_status'  => 'publish',
			'post_title'   => $reference->title,
			'post_type'    => 'post',
		);

		//Add or update the entry
		if( $_entry = self::get_post( $reference->id ) ) {
			$post_id = wp_update_post( wp_parse_args( array(
				'ID' => $_entry->ID,
			), $new ) );
		} else {
			$post_id = wp_insert_post( $new );
		}

		//Save reference ID
		update_post_meta( $post_id, '_reference_id', $reference->id );
	}

	/**
	 * Set progress
	 *
	 * @param int $attachment_id
	 * @param int $progress
	 *
	 * @abstract
	 */
	static function set_progress( $attachment_id, $progress ) {
		TP_CSV_Queue::set_status( $attachment_id, array(
			'code'     => 'processing',
			'progress' => $progress,
		) );
	}

	/**
	 * Get a post by reference ID
	 *
	 * @param int $reference_id
	 * 
	 * @return object|bool(false)
	 *
	 * @abstract
	 */
	static function get_post( $reference_id ) {
		if( ! $reference_id )
			return false;

		$posts = get_posts( array(
			'post_type'     => 'any',
			'post_status'   => 'any',
			'numberposts'   => 1,
			'meta_query'    => array(
				array(
					'key'   => '_reference_id',
					'value' => $reference_id
				),
			),
		) );

		if( isset( $posts[0] ) )
			return $posts[0];

		return false;
	}

}