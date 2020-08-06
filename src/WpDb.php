<?php

namespace WPGeonames;

use mysqli;
use function mysqli_errno;

if ( ! defined( 'ARRAY_K' ) ) {
	define( 'ARRAY_K', 'ARRAY_K' );
}

class WpDb
	extends \wpdb {

//  public properties
	public $last_error_no = 0;

// protected properties
	protected $use_mysqli = false;
	protected $has_connected = false;


	/**
	 * WpDb constructor.
	 *
	 * @param \wpdb|null $db
	 *
	 * @noinspection PhpMissingParentConstructorInspection
     * @noinspection MagicMethodsValidityInspection
     */
	public function __construct( \wpdb $db = null ) {

		global $wpdb;

		$db = $db ?? $wpdb;

		$db->flush();

		$x = get_object_vars( $db );

		/*
		 $skip = array_merge(
			[
				'tables',
				'old_tables',
				'global_tables',
				'ms_global_tables',
				'incompatible_modes',
			],
			$this->old_tables
		);
		*/

		foreach ( $x as $property => &$value ) {

			//if ( in_array( $property, $skip ) ) {
			//continue;
			//}

			if ( property_exists( $this, $property ) ) {
				$this->$property =& $value;
			} else {
				$this->$property = $value;
			}

		}

		foreach ( [ 'use_mysqli', 'has_connected' ] as $property ) {

			$this->$property = $wpdb->__get( $property );
			$this->__set($property, $this->$property);

		}


		if ( $wpdb instanceof \wpdb ) {
			$wpdb = $this;
		}

	}


    public function flush():void {

		$this->last_error_no = 0;

		parent::flush();
	}


	public function query( $query ) {

		$return_val = parent::query( $query );

		if ( ! empty( $this->dbh ) ) {

			if ( $this->use_mysqli ) {
				if ( $this->dbh instanceof mysqli ) {
					$this->last_error_no = mysqli_errno( $this->dbh );
				} else {
					// $dbh is defined, but isn't a real connection.
					// Something has gone horribly wrong, let's try a reconnect.
					$this->last_error_no = 2006;
				}
			} else {
				if ( is_resource( $this->dbh ) ) {
					/** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
					$this->last_error_no = \mysql_errno( $this->dbh );
				} else {
					$this->last_error_no = 2006;
				}
			}
		}

		return $return_val;

	}


	public static function &formatOutput( &$result, $output, $keyName = '', $prefix = '' ) {

		if ( $result === null ) {
			return $result;
		}

		$class = null;

		if (
			! in_array( $output, [ OBJECT, OBJECT_K, ARRAY_K, ARRAY_A, ARRAY_N ], true )
			&& class_exists( $output )
		) {

			$class  = $output;
			$output = $keyName ? OBJECT_K : OBJECT;

		}

		switch ( strtoupper( $output ) ) {
			// Back compat for OBJECT being previously case-insensitive.

			case OBJECT:
				// Return an integer-keyed array of row objects.
				break;

			case OBJECT_K:
			case ARRAY_K:
			case ARRAY_A:
			case ARRAY_N:
				$new_array = [];

				// Return an array of row objects with keys from column 1.
				// (Duplicates are discarded.)
				if ( $result ) {

					array_walk( $result,
						static function ( $row )
						use ( &$new_array, $output, $keyName, $prefix ) {

							$object_vars = get_object_vars( $row );
							$key         = $prefix . ( $keyName
									? $object_vars[ $keyName ]
									: reset( $object_vars ) );

							switch ( $output ) {
								/** @noinspection PhpMissingBreakStatementInspection */
								case ARRAY_K:
									$row = $object_vars;
								// continue with next

								case OBJECT_K:
									if ( ! isset( $new_array[ $key ] ) ) {
										$new_array[ $key ] = $row;
									}
									break;

								case ARRAY_A:
									// Return an integer-keyed array of...
									// ...column name-keyed row arrays.
									$new_array[] = $object_vars;
									break;

								case ARRAY_N:
									// Return an integer-keyed array of...
									// ...integer-keyed row arrays.
									$new_array[] = array_values( $object_vars );
									break;
							}
						} );

				}

				$result = $new_array;
				break;

			default:
			    throw new \ErrorException("Unknown output format or class '$output'");
		}

		if ( $class !== null ) {

			array_walk( $result, static function ( &$item ) use ( $class ) {

				$item = new $class( $item );
			} );

		}

		return $result;

	}
}