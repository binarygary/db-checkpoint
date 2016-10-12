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
	 * Prints a greeting.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : The name of the person to greet.
	 *
	 * [--type=<type>]
	 * : Whether or not to greet the person with success or error.
	 * ---
	 * default: success
	 * options:
	 *   - success
	 *   - error
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp example hello Newman
	 *
	 * @when before_wp_load
	 */
	public function checkpoint_save( $args ) {

		$upload_dir = wp_upload_dir();

		$location  = $upload_dir[ 'basedir' ] . '/checkpoint-storage/' . time() . '.' . $args[ 0 ] . '.sql';
		$args[ 0 ] = $location;

		$db = new DB_Command;
		$db->export( $args, null );

		WP_CLI::success( "Checkpoint Saved!" );
	}

	public function checkpoint_restore( $args ) {

		$upload_dir = wp_upload_dir();

		if ($restore_file = $this->get_most_recent_file( $args[ 0 ] )){
			$location = $upload_dir[ 'basedir' ] . '/checkpoint-storage/' . $this->get_most_recent_file( $args[ 0 ] );
		} else {
			WP_CLI::error( 'No checkpoint found associated with ' . $args[0] );
		}

		$args[ 0 ] = $location;

		$db = new DB_Command;
		$db->import( $args, null );

		WP_CLI::success( "Checkpoint Restored!" );
	}

	public function get_most_recent_file( $backup_name ) {
		$upload_dir = wp_upload_dir();
		$backupsdir = scandir( $upload_dir[ 'basedir' ] . '/checkpoint-storage/', SCANDIR_SORT_DESCENDING );
		foreach ( $backupsdir as $backup ) {
			if ( strpos( $backup, $backup_name ) !== false ) {
				return $backup;
			}
		}
		return false;
	}
}
