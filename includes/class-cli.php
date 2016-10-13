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
	 *
	 * @author Gary Kovar
	 *
	 * @since  0.1.0
	 */
	public function checkpoint_save( $args ) {

		$snapshot_name = $this->get_snapshot_name( $args );

		$this->maybe_nuke_checkpoints( $snapshot_name );

		$upload_dir = wp_upload_dir();

		$location  = $upload_dir[ 'basedir' ] . '/checkpoint-storage/' . $snapshot_name . '.' . $this->human_timestamp() . '.sql';
		$args[ 0 ] = $location;

		$db = new DB_Command;
		$db->export( $args, null );

		WP_CLI::success( "Checkpoint Saved!" );
	}

	/**
	 * Restores the most recent checkpoint of the db.
	 *
	 * @author Gary Kovar
	 *
	 * @since  0.1.0
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
	 * Figure out what name to use with this file.
	 *
	 * @author Gary Kovar
	 *
	 * @since  0.1.0
	 *
	 * @param $args
	 *
	 * @return string
	 */
	public function get_snapshot_name( $args ) {

		if ( key_exists( 0, $args ) ) {
			return $args[ 0 ];
		}

		return sanitize_title( get_option( 'blogname', 'shruggy' ) );
	}

	/**
	 * Check to see if the checkpoint name matches the site name, if so remove any checkpoints of the same name.
	 *
	 * @author Gary Kovar
	 *
	 * @since 0.1.0
	 *
	 * @param $checkpoint_name
	 */
	public function maybe_nuke_checkpoints( $checkpoint_name ) {
		if ($checkpoint_name == $this->get_snapshot_name(null)) {
			$this->nuke_checkpoints( $checkpoint_name );
		}
	}

	/**
	 * Deletes all previous checkpoints under the same name.
	 *
	 * @author Gary Kovar
	 *
	 * @since 0.1.0
	 *
	 * @param $checkpoint_name
	 */
	public function nuke_checkpoints( $checkpoint_name ) {
		$upload_dir = wp_upload_dir();
		$backupsdir = scandir( $upload_dir[ 'basedir' ] . '/checkpoint-storage/', SCANDIR_SORT_DESCENDING );
		foreach ( $backupsdir as $backup ) {
			if(strpos($backup, $checkpoint_name)===0) {
				unlink($upload_dir['basedir'].'/checkpoint-storage/'.$backup);
			}
		}
	}

	/**
	 * Return a pretty human readable time.
	 *
	 * @author Gary Kovar
	 *
	 * @since  0.1.0
	 *
	 * @return false|string
	 */
	public function human_timestamp() {
		return date( "Ymd-Hi", time() );
	}
}
