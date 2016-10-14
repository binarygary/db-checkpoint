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

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	if ( ! class_exists( 'DB_CheckPoint' ) ) {
		class DB_CheckPoint extends WP_CLI_Command {

			public function check_requirements() {
				$upload_dir = wp_upload_dir();
				if ( ! file_exists( $upload_dir[ 'basedir' ] . '/checkpoint-storage' ) ) {
					mkdir( $upload_dir[ 'basedir' ] . '/checkpoint-storage' );
				}

				return true;
			}

			/**
			 * Returns the array of configuration setup info for dbsnap command.
			 *
			 * @author Gary Kovar
			 *
			 * @since  0.1.0
			 *
			 * @return array
			 *
			 */
			public function get_checkpoint_save_args() {
				return array(
					'shortdesc' => 'Restores the checkpoint image of the database.',
					'synopsis'  => array(
						array(
							'type'     => 'positional',
							'name'     => 'name',
							'optional' => true,
							'multiple' => false,
						),
					),
					'when'      => 'after_wp_load',
				);
			}

			/**
			 * Returns the array of configuration setup info for dbsnapback command.
			 *
			 * @author Gary Kovar
			 *
			 * @since  0.1.0
			 *
			 * @return array
			 *
			 */
			public function get_checkpoint_restore_args() {
				return array(
					'shortdesc' => 'Creates a simple checkpoint image of the database.',
					'synopsis'  => array(
						array(
							'type'     => 'positional',
							'name'     => 'name',
							'optional' => true,
							'multiple' => false,
						),
					),
					'when'      => 'after_wp_load',
				);
			}

			/**
			 * Saves a checkpoint of the db.
			 *
			 * @author Gary Kovar
			 *
			 * @since  0.1.0
			 */
			public function checkpoint_save( $args ) {

				if ( ! $this->check_requirements() ) {
					exit;
				}

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

				if ( ! $this->check_requirements() ) {
					exit;
				}

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
			 * Get the name of the most recent backup file.
			 *
			 * @author Gary Kovar
			 *
			 * @since  0.1.0
			 *
			 * @param $backup_name
			 *
			 * @return bool
			 */
			public function get_most_recent_file( $backup_name ) {

				$upload_dir = wp_upload_dir();
				$backupsdir = scandir( $upload_dir[ 'basedir' ] . '/checkpoint-storage/', SCANDIR_SORT_DESCENDING );
				foreach ( $backupsdir as $backup ) {
					if ( strpos( $backup, $backup_name ) === 0 ) {
						return $backup;
					}
				}

				return false;
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
			 * @since  0.1.0
			 *
			 * @param $checkpoint_name
			 */
			public function maybe_nuke_checkpoints( $checkpoint_name ) {
				$this->nuke_checkpoints( $checkpoint_name );
			}

			/**
			 * Deletes all previous checkpoints under the same name.
			 *
			 * @author Gary Kovar
			 *
			 * @since  0.1.0
			 *
			 * @param $checkpoint_name
			 */
			public function nuke_checkpoints( $checkpoint_name ) {
				$upload_dir = wp_upload_dir();
				$backupsdir = scandir( $upload_dir[ 'basedir' ] . '/checkpoint-storage/', SCANDIR_SORT_DESCENDING );
				foreach ( $backupsdir as $backup ) {
					if ( strpos( $backup, $checkpoint_name ) === 0 ) {
						unlink( $upload_dir[ 'basedir' ] . '/checkpoint-storage/' . $backup );
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

		/**
		 * Kick off!
		 *
		 * @return DB_CheckPoint
		 */
		function db_checkpoint() {
			return new DB_CheckPoint;
		}

		$checkpoint = db_checkpoint();

		/**
		 * Add dbsnap as a WP CLI command.
		 */
		WP_CLI::add_command( 'dbsnap', array(
			$checkpoint,
			'checkpoint_save',
		), $checkpoint->get_checkpoint_save_args() );

		/**
		 * Add dbsnapback as a WP CLI command.
		 */
		WP_CLI::add_command( 'dbsnapback', array(
			$checkpoint,
			'checkpoint_restore',
		), $checkpoint->get_checkpoint_restore_args() );
	}
}


if ( ! defined( 'WP_CLI' ) ) {
	if ( ! class_exists( 'DB_CheckPoint_Plugin' ) ) {
		class DB_CheckPoint_Plugin {

			/**
			 * Hook to add functions to WP
			 *
			 * @author Gary Kovar
			 *
			 * @since  0.2.0
			 */
			public function hooks() {
				add_action( 'admin_bar_menu', array( $this, 'toolbar_dbsnap' ), 999 );
			}

			/**
			 * Add link to wp-admin toolbar.
			 *
			 * @author Gary Kovar
			 *
			 * @since  0.2.0
			 */
			public function toolbar_dbsnap( $wp_admin_bar ) {
				$args = array(
					'id'    => 'my_page',
					'title' => 'My Page',
					'href'  => 'http://mysite.com/my-page/',
					'meta'  => array( 'class' => 'my-toolbar-page' ),
				);
				$wp_admin_bar->add_node( $args );
			}
		}

		/**
		 * Kick Off!
		 *
		 * @return DB_CheckPoint_Plugin
		 */
		function db_checkpoint_plugin() {
			return new DB_CheckPoint_Plugin();
		}

		add_action( 'plugins_loaded', array( db_checkpoint_plugin(), 'hooks' ) );
	}
}
