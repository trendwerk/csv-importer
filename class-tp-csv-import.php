<?php
/**
 * Import CSV to WordPress
 *
 * @package CSV_Importer
 */

class TP_CSV_Import {

	var $attachment_id;

	/**
	 * Create importer
	 * 
	 * @param int $attachment_id
	 */
	function __construct( $attachment_id ) {
		$this->attachment_id = $attachment_id;
	}

	/**
	 * Start import
	 * 
	 * @return string Status code
	 */
	function start() {
		$this->set_progress( 0 );

		/**
		 * Read file
		 */
		$file_path = get_attached_file( $this->attachment_id );
		$total_rows = 0;

		if( $handle = @fopen( $file_path, 'r' ) ) {
			/**
			 * Count rows for progress
			 */
			$head = fgetcsv( $handle, 0, ';' );

			while( $entry = fgetcsv( $handle, 0, ';' ) )
				$total_rows++;

			/**
			 * Import entries
			 */
			if( 0 < $total_rows ) {
				rewind( $handle );

				$head = fgetcsv( $handle, 0, ';' );

				$current_row = 0;

				while( $entry = fgetcsv( $handle, 0, ';' ) ) {
					$entry = array_combine( $head, $entry );

					$current_row++;
					$this->set_progress( floor( ( $current_row / $total_rows ) * 100 ) );

					$this->add_entry( $entry );
				}
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
	 */
	function add_entry( $reference ) {
		if( ! isset( $reference ) || ! is_array( $reference ) )
			return;

		$reference = (object) $reference;

		if( ! isset( $reference->id ) )
			return;

		$new = array(
			'post_author'  => 1,
			'post_content' => $reference->content,
			'post_status'  => 'publish',
			'post_title'   => trim( $reference->title ),
			'post_type'    => 'post',
		);

		//Add or update the entry
		if( $_entry = $this->get_post( $reference->id ) ) {
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
	 * @param int $progress
	 */
	function set_progress( $progress ) {
		TP_CSV_Queue::set_status( $this->attachment_id, array(
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
	 */
	function get_post( $reference_id ) {
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