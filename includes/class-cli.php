<?php
/**
 * DB CheckPoint Cli
 *
 * @since   NEXT
 * @package DB CheckPoint
 */

/**
 * DB CheckPoint Cli.
 *
 * @since NEXT
 */
class DBCP_Cli {

	/**
	 * Parent plugin class
	 *
	 * @var   DB_CheckPoint
	 * @since NEXT
	 */
	protected $plugin = null;

	/**
	 * Constructor
	 *
	 * @since  NEXT
	 *
	 * @param  DB_CheckPoint $plugin Main plugin object.
	 *
	 * @return void
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Saves a checkpoint of the db.
	 */
	public function checkpoint_save( $args ) {

		$snapshot_name = $this->get_snapshot_name( $args );

		$upload_dir = wp_upload_dir();

		$location  = $upload_dir[ 'basedir' ] . '/checkpoint-storage/' . time() . '.' . $snapshot_name . '.sql';
		$args[ 0 ] = $location;

		$db = new DB_Command;
		$db->export( $args, null );

		WP_CLI::success( "Checkpoint Saved!" );
	}

	/**
	 * Restores the most recent checkpoint of the db.
	 */
	public function checkpoint_restore( $args ) {

		$snapshot_name = $this->get_snapshot_name( $args );

		$upload_dir = wp_upload_dir();

		if ( $restore_file = $this->get_most_recent_file( $snapshot_name ) ) {
			$location = $upload_dir[ 'basedir' ] . '/checkpoint-storage/' . $this->get_most_recent_file( $snapshot_name );
		} else {
			WP_CLI::error( 'No checkpoint found associated with ' . $snapshot_name );
		}

		$args[ 0 ] = $location;

		$db = new DB_Command;
		$db->import( $args, null );

		WP_CLI::success( "Checkpoint Restored!" );
	}

	/**
	 * Lists checkpoints of the db.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : The name of the checkpoint to list.
	 *
	 * ## EXAMPLES
	 *
	 *     wp checkpoint list something-risky
	 */
	public function checkpoint_list( $args ) {

		//@TODO change WP_CLI::line to be more informative.

		$snapshot_name = $this->get_snapshot_name( $args );

		$upload_dir = wp_upload_dir();

		$backupsdir = scandir( $upload_dir[ 'basedir' ] . '/checkpoint-storage/', SCANDIR_SORT_DESCENDING );
		foreach ( $backupsdir as $backup ) {
			if ( strpos( $backup, $snapshot_name ) !== false ) {
				WP_CLI::line( $backup );
			}
		}
	}

	public function get_most_recent_file( $backup_name ) {

		//@TODO Use something better than substr (tem will restore temp)

		$upload_dir = wp_upload_dir();
		$backupsdir = scandir( $upload_dir[ 'basedir' ] . '/checkpoint-storage/', SCANDIR_SORT_DESCENDING );
		foreach ( $backupsdir as $backup ) {
			if ( strpos( $backup, $backup_name ) !== false ) {
				return $backup;
			}
		}

		return false;
	}

	public function get_snapshot_name( $args ) {

		error_log(print_r($args, TRUE));

		if ( key_exists( 0, $args ) ) {
			return $args[ 0 ];
		}

		return sanitize_title( get_option( 'blogname', 'shruggy' ) );
	}
}
