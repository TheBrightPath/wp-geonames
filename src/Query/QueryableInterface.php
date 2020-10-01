<?php

namespace WPGeonames\Query;

use WPGeonames\Entities\Location;

interface QueryableInterface
{

    public function query( $output = Location::class ): Status;

    public function getStatus(): Status;

}