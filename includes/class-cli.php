<?php
/**
 * DB CheckPoint Cli
 *
 * @since NEXT
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
	 * @param  DB_CheckPoint $plugin Main plugin object.
	 * @return void
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks
	 *
	 * @since  NEXT
	 * @return void
	 */
	public function hooks() {
	}
}
