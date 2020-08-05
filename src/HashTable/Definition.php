<?php

namespace WPGeonames\HashTable;

class Definition {
//  public properties
	public $name;
	public $file;
	public $fields;


	/**
	 * wpGeonamesArrayDefinition constructor.
	 *
	 * @param $name
	 * @param $file
	 * @param $fields
	 */
	public function __construct( $name, $file, $fields ) {

		$this->name   = $name;
		$this->file   = $file;
		$this->fields = $fields;
	}
}