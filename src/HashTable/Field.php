<?php

namespace WPGeonames\HashTable;

use WPGeonames\WpDb;

class Field
{
//  public properties
    public $table;
    public $field;


    /**
     * wpGeonamesFieldDefinition constructor.
     *
     * @param $table
     * @param $field
     */
    public function __construct(
        $table,
        $field
    ) {

        $this->table = WpDb::replaceTablePrefix( $table);
        $this->field = $field;
    }

}