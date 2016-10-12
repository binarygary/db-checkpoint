<?php
/**
 * Plugin Name: DB Snapshot
 * Plugin URI:  https://www.binarygary.com/
 * Description: Extends WP-CLI to include a db snapshot for development purposes.
 * Version:     0.1.0
 * Author:      Gary Kovar
 * Author URI:  https://www.binarygary.com/
 * Donate link: https://www.binarygary.com/
 * License:     GPLv2
 * Text Domain: db-snapshot
 * Domain Path: /languages
 *
 * @link    https://www.binarygary.com/
 *
 * @package DB Snapshot
 * @version 0.1.0
 */

/**
 * Copyright (c) 2016 Gary Kovar (email : plugins@binarygary.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Built using generator-plugin-wp
 */


/**
 * Autoloads files with classes when needed
 *
 * @since  NEXT
 *
 * @param  string $class_name Name of the class being requested.
 *
 * @return void
 */
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
	 * URL of plugin directory
	 *
	 * @var string
	 * @since  NEXT
	 */
	protected $url = '';

	/**
	 * Path of plugin directory
	 *
	 * @var string
	 * @since  NEXT
	 */
	protected $path = '';

	/**
	 * Plugin basename
	 *
	 * @var string
	 * @since  NEXT
	 */
	protected $basename = '';

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
		$this->basename = plugin_basename( __FILE__ );
		$this->url      = plugin_dir_url( __FILE__ );
		$this->path     = plugin_dir_path( __FILE__ );
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
	 * Add hooks and filters
	 *
	 * @since  NEXT
	 * @return void
	 */
	public function hooks() {
		// Priority needs to be:
		// < 10 for CPT_Core,
		// < 5 for Taxonomy_Core,
		// 0 Widgets because widgets_init runs at init priority 1.
		add_action( 'init', array( $this, 'init' ), 0 );
	}

	/**
	 * Activate the plugin
	 *
	 * @since  NEXT
	 * @return void
	 */
	public function _activate() {
		// Make sure any rewrite functionality has been loaded.
		flush_rewrite_rules();
	}

	/**
	 * Deactivate the plugin
	 * Uninstall routines should be in uninstall.php
	 *
	 * @since  NEXT
	 * @return void
	 */
	public function _deactivate() {
	}

	/**
	 * Init hooks
	 *
	 * @since  NEXT
	 * @return void
	 */
	public function init() {
		if ( $this->check_requirements() ) {
			load_plugin_textdomain( 'db-checkpoint', false, dirname( $this->basename ) . '/languages/' );
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
		if ( ! $this->meets_requirements() ) {

			// Add a dashboard notice.
			add_action( 'all_admin_notices', array( $this, 'requirements_not_met_notice' ) );

			// Deactivate our plugin.
			add_action( 'admin_init', array( $this, 'deactivate_me' ) );

			return false;
		}

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
	 * Deactivates this plugin, hook this function on admin_init.
	 *
	 * @since  NEXT
	 * @return void
	 */
	public function deactivate_me() {

		// We do a check for deactivate_plugins before calling it, to protect
		// any developers from accidentally calling it too early and breaking things.
		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( $this->basename );
		}
	}

	/**
	 * Check that all plugin requirements are met
	 *
	 * @since  NEXT
	 * @return boolean True if requirements are met.
	 */
	public function meets_requirements() {
		// Do checks for required classes / functions
		// function_exists('') & class_exists('').
		// We have met all requirements.
		return true;
	}

	/**
	 * Adds a notice to the dashboard if the plugin requirements are not met
	 *
	 * @since  NEXT
	 * @return void
	 */
	public function requirements_not_met_notice() {
		// Output our error.
		echo '<div id="message" class="error">';
		echo '<p>' . sprintf( __( 'DB CheckPoint is missing requirements and has been <a href="%s">deactivated</a>. Please make sure all requirements are available.', 'db-checkpoint' ), admin_url( 'plugins.php' ) ) . '</p>';
		echo '</div>';
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
			case 'basename':
			case 'url':
			case 'path':
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

	/**
	 * This plugin's url
	 *
	 * @since  NEXT
	 *
	 * @param  string $path (optional) appended path.
	 *
	 * @return string       URL and path
	 */
	public static function url( $path = '' ) {
		static $url;
		$url = $url ? $url : trailingslashit( plugin_dir_url( __FILE__ ) );

		return $url . $path;
	}

	public function load_commands() {
		WP_CLI::add_command( 'snapshot set', array( $this->cli, 'checkpoint_save' ) );
		WP_CLI::add_command( 'snapshot get', array( $this->cli, 'checkpoint_restore' ) );
		WP_CLI::add_command( 'snapshot list', array( $this->cli, 'checkpoint_list' ) );
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

// Kick it off.
add_action( 'plugins_loaded', array( db_checkpoint(), 'hooks' ) );

register_activation_hook( __FILE__, array( db_checkpoint(), '_activate' ) );
register_deactivation_hook( __FILE__, array( db_checkpoint(), '_deactivate' ) );
