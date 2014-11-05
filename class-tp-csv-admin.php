<?php
/**
 * Management for CSV import.
 *
 * @package	CSV_Importer
 */

class TP_CSV_Admin {

	var $capability = 'publish_posts';
	var $page_queue = 'tp-csv-importer-queue';
	var $page_add_file = 'tp-csv-importer-add';

	function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'actions' ) );
		add_action( 'admin_notices', array( $this, 'notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Admin menu
	 */
	function menu() {
		add_menu_page( __( 'Import', 'tp-csv-importer' ), __( 'Import', 'tp-csv-importer' ), $this->capability, $this->page_queue, array( $this, 'queue' ), 'dashicons-download' );
		add_submenu_page( $this->page_queue, __( 'Queue', 'tp-csv-importer' ), __( 'Queue', 'tp-csv-importer' ), $this->capability, $this->page_queue, array( $this, 'queue' ) );

		add_submenu_page( $this->page_queue, __( 'Add file', 'tp-csv-importer' ), __( 'Add file', 'tp-csv-importer' ), $this->capability, $this->page_add_file, array( $this, 'add' ) );
	}

	/**
	 * Queue admin page
	 */
	function queue() {
		$queue = TP_CSV_Queue::get();
		?>

		<div class="wrap tp-csv-importer">

			<h2>
				<?php _e( 'Import queue', 'tp-csv-importer' ); ?>
			</h2>

			<table class="wp-list-table widefat">

				<thead>

					<tr>

						<th>
							<?php _e( 'File', 'tp-csv-importer' ); ?>
						</th>

						<th>
							<?php _e( 'Status', 'tp-csv-importer' ); ?>
						</th>

						<th class="actions"></th>

					</tr>

				</thead>

				<tbody>

					<?php 
						if( 0 === count( $queue ) ) { 
							?>

							<tr>

								<td colspan="2">

									<?php printf( __( 'The queue is currently empty. <a href="%1$s">Add new file</a>', 'tp-csv-importer' ), admin_url( 'admin.php?page=' . $this->page_add_file ) ); ?>

								</td>

							</tr>

							<?php 
						} else { 

							foreach( $queue as $file ) {

								$attachment = get_post( $file->attachment_id );
								?>

								<tr>

									<td>

										<a href="<?php echo wp_get_attachment_url( $file->attachment_id ); ?>" target="_blank">
											<?php echo $attachment->post_title; ?>
										</a>

									</td>

									<td>

										<?php
											$status = $file->status;

											if( 'waiting' === $status['code'] )
												_e( 'Waiting', 'tp-csv-importer' );
											else if( 'processing' === $status['code'] )
												printf( __( 'Processing (%1$s)', 'tp-csv-importer' ), $status['progress'] . '%' );
											else if( 'failed' === $status['code'] )
												printf( __( 'Failed: %1$s', 'tp-csv-importer' ), $status['message'] );
											else if( 'success' === $status['code'] )
												printf( __( 'Done', 'tp' ) );

										?>

									</td>

									<td>
										<?php if( 'processing' !== $status['code'] && 'success' !== $status['code'] ) { ?>
											<a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=' . $this->page_queue . '&tp-csv-importer-remove-file=' . $file->attachment_id ), 'tp-csv-importer-remove' ); ?>" class="dashicons dashicons-trash tp-csv-importer-remove-file"></a>
										<?php } ?>
									</td>

								</tr>

								<?php 

								/**
								 * Remove file after seen 'success'
								 */
								if( 'success' === $status['code'] )
									TP_CSV_Queue::remove( $file->attachment_id );
							}
						} 
					?>

				</tbody>

			</table>

		</div>

		<?php
	}

	/**
	 * Add file admin page
	 */
	function add() {
		?>

		<div class="wrap tp-csv-importer">

			<h2>
				<?php _e( 'Add file to queue', 'tp-csv-importer' ); ?>
			</h2>

			<form method="POST" enctype="multipart/form-data">

				<input type="file" name="tp-csv-importer-file" />

				<?php wp_nonce_field( 'tp-csv-importer-add' ); ?>

				<?php submit_button( __( 'Add', 'tp-csv-importer' ) ); ?>

			</form>

		</div>

		<?php
	}

	/**
	 * Actions: Add / remove file
	 */
	function actions() {
		/**
		 * Add file
		 */
		if( isset( $_FILES['tp-csv-importer-file'] ) && check_admin_referer( 'tp-csv-importer-add' ) ) {
			$added = TP_CSV_Queue::add( 'tp-csv-importer-file' );

			if( $added ) {
				wp_redirect( admin_url( 'admin.php?page=' . $this->page_queue . '&message=tp-csv-importer-added' ) );
			} else {
				wp_redirect( admin_url( 'admin.php?page=' . $this->page_add_file . '&message=tp-csv-importer-not-added' ) );
			}

			die();
		}

		/**
		 * Remove file
		 */
		if( isset( $_GET['tp-csv-importer-remove-file'] ) && check_admin_referer( 'tp-csv-importer-remove' ) ) {
			TP_CSV_Queue::remove( $_GET['tp-csv-importer-remove-file'] );

			wp_redirect( admin_url( 'admin.php?page=' . $this->page_queue . '&message=tp-csv-importer-removed' ) );
			die();
		}
	}

	/**
	 * Show notices
	 */
	function notices() {
		if( isset( $_GET['message'] ) && ( 'tp-csv-importer-added' === $_GET['message'] || 'tp-csv-importer-removed' === $_GET['message'] ) ) {
			?>

			<div class="updated">
				<p>
					<?php 
						if( 'tp-csv-importer-added' === $_GET['message'] )
							_e( 'The file has been added to the queue.', 'tp-csv-importer' );
						else if( 'tp-csv-importer-removed' === $_GET['message'] )
							_e( 'The file has been removed the queue.', 'tp-csv-importer' );
					?>
				</p>
			</div>

			<?php
		} elseif( isset( $_GET['message'] ) && 'tp-csv-importer-not-added' === $_GET['message'] ) {
			?>

			<div class="error">
				<p>
					<?php _e( 'ERROR: The file was not added to the queue.', 'tp' ); ?>
				</p>
			</div>

			<?php
		}
	}

	/**
	 * Enqueue scripts
	 */
	function enqueue() {
		wp_enqueue_script( 'tp-csv-importer', plugins_url( 'assets/coffee/admin.js', __FILE__ ), array( 'jquery' ) );
		wp_localize_script( 'tp-csv-importer', 'TP_CSV_Importer_Labels', array(
			'remove_notice' => __( 'Are you sure you want to remove this file from the queue?', 'tp' ),
		) );

		wp_enqueue_style( 'tp-csv-importer', plugins_url( 'assets/sass/admin.css', __FILE__ ) );
	}

} new TP_CSV_Admin;
