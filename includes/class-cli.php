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
	public function checkpoint_save($args) {

		error_log($args);

		$db = new DB_Command;
		$db->export();

		WP_CLI::success( "Checkpoint Saved!" );
	}

	public function checkpoint_restore() {

	}
}
