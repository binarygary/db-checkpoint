<?php

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	class DB_CheckPoint extends WP_CLI_COMMAND {

		public function __construct() {
			$this->load_commands();
		}

		public function load_commands() {
			WP_CLI::add_command( 'dbsnap', array ($this, 'checkpoint_save' ), $this->get_checkpoint_save_args() );
			WP_CLI::add_command( 'dbsnapback', array( $this, 'checkpoint_restore' ), $this->get_checkpoint_restore_args() );
		}


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
				'when'      => 'before_wp_load',
			);
		}


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
				'when'      => 'before_wp_load',
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

	function db_checkpoint() {
		$dbcheck = new DB_CheckPoint;
	}

	db_checkpoint();

}


