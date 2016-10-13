<?php
function db_checkpoint_autoload_classes( $class_name ) {
	if ( 0 !== strpos( $class_name, 'DBCP_' ) ) {
		return;
	}

	$filename = strtolower( str_replace(
		'_', '-',
		substr( $class_name, strlen( 'DBCP_' ) )
	) );

	DB_CheckPoint::include_file( 'includes/class-' . $filename );
}

spl_autoload_register( 'db_checkpoint_autoload_classes' );

/**
 * Main initiation class
 *
 * @since  NEXT
 */
final class DB_CheckPoint {

	/**
	 * Current version
	 *
	 * @var  string
	 * @since  NEXT
	 */
	const VERSION = '0.1.0';

	/**
	 * Singleton instance of plugin
	 *
	 * @var DB_CheckPoint
	 * @since  NEXT
	 */
	protected static $single_instance = null;

	/**
	 * Instance of DBCP_Cli
	 *
	 * @since NEXT
	 * @var DBCP_Cli
	 */
	protected $cli;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since  NEXT
	 * @return DB_CheckPoint A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Sets up our plugin
	 *
	 * @since  NEXT
	 */
	protected function __construct() {
		$this->init();
	}

	/**
	 * Attach other plugin classes to the base plugin class.
	 *
	 * @since  NEXT
	 * @return void
	 */
	public function plugin_classes() {
		// Attach other plugin classes to the base plugin class.
		$this->cli = new DBCP_Cli( $this );
	} // END OF PLUGIN CLASSES FUNCTION

	/**
	 * Init hooks
	 *
	 * @since  NEXT
	 * @return void
	 */
	public function init() {
		if ( $this->check_requirements() ) {
			$this->plugin_classes();
			$this->load_commands();
		}
	}

	/**
	 * Check if the plugin meets requirements and
	 * disable it if they are not present.
	 *
	 * @since  NEXT
	 * @return boolean result of meets_requirements
	 */
	public function check_requirements() {

		$upload_dir = wp_upload_dir();
		if ( ! file_exists( $upload_dir[ 'basedir' ] . '/checkpoint-storage' ) ) {
			mkdir( $upload_dir[ 'basedir' ] . '/checkpoint-storage' );
		}

		if ( !defined( 'WP_CLI' )) {
			return false;
		}

		return true;
	}

	/**
	 * Magic getter for our object.
	 *
	 * @since  NEXT
	 *
	 * @param string $field Field to get.
	 *
	 * @throws Exception Throws an exception if the field is invalid.
	 * @return mixed
	 */
	public function __get( $field ) {
		switch ( $field ) {
			case 'version':
				return self::VERSION;
			case 'cli':
				return $this->$field;
			default:
				throw new Exception( 'Invalid ' . __CLASS__ . ' property: ' . $field );
		}
	}

	/**
	 * Include a file from the includes directory
	 *
	 * @since  NEXT
	 *
	 * @param  string $filename Name of the file to be included.
	 *
	 * @return bool   Result of include call.
	 */
	public static function include_file( $filename ) {
		$file = self::dir( $filename . '.php' );
		if ( file_exists( $file ) ) {
			return include_once( $file );
		}

		return false;
	}

	/**
	 * This plugin's directory
	 *
	 * @since  NEXT
	 *
	 * @param  string $path (optional) appended path.
	 *
	 * @return string       Directory and path
	 */
	public static function dir( $path = '' ) {
		static $dir;
		$dir = $dir ? $dir : trailingslashit( dirname( __FILE__ ) );

		return $dir . $path;
	}

	public function load_commands() {
		WP_CLI::add_command( 'dbsnap', array( $this->cli, 'checkpoint_save' ) , $this->get_checkpoint_save_args() );
		WP_CLI::add_command( 'dbsnapback', array( $this->cli, 'checkpoint_restore' ), $this->get_checkpoint_restore_args() );
	}

	public function get_checkpoint_save_args() {
		return array(
			'shortdesc' => 'Restores the checkpoint image of the database.',
			'synopsis' => array(
				array(
					'type'     => 'positional',
					'name'     => 'name',
					'optional' => true,
					'multiple' => false,
				),
			),
			'when' => 'before_wp_load',
		);
	}


	public function get_checkpoint_restore_args() {
		return array(
			'shortdesc' => 'Creates a simple checkpoint image of the database.',
			'synopsis' => array(
				array(
					'type'     => 'positional',
					'name'     => 'name',
					'optional' => true,
					'multiple' => false,
				),
			),
			'when' => 'before_wp_load',
		);
	}

}

/**
 * Grab the DB_CheckPoint object and return it.
 * Wrapper for DB_CheckPoint::get_instance()
 *
 * @since  NEXT
 * @return DB_CheckPoint  Singleton instance of plugin class.
 */
function db_checkpoint() {
	return DB_CheckPoint::get_instance();
}

db_checkpoint();