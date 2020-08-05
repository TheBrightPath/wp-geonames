<?php

namespace WPGeonames\HashTable;

class Field {
	public $table;
	public $field;

	/**
	 * wpGeonamesFieldDefinition constructor.
	 *
	 * @param $table
	 * @param $field
	 */
	public function __construct( $table, $field ) {
		$this->table = $table;
		$this->field = $field;
	}

}