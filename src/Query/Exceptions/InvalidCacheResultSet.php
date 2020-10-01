<?php

namespace WPGeonames\Query\Exceptions;

use ErrorException;

class InvalidCacheResultSet
    extends
    ErrorException
{

    public function __construct(
        $message,
        $row
    ) {

        parent::__construct( sprintf( '%s: %s', $message, \GuzzleHttp\json_encode( $row ) ) );
    }

}