<?php

namespace WPGeonames\Query;

use WPGeonames\Core;

class ApiStatus
    extends
    Status
{

//  public properties

    public $keepPreviouslyCachedRecords = false;

    /**
     * @var \GeoNames\Client
     */
    public $geonamesClient;

    /**
     * @var array|null
     */
    public $parameters;


    /**
     * ApiStatus constructor.
     *
     * @param  \WPGeonames\Query\Query  $query
     * @param  array|null               $globalResultSet
     *
     * @throws \ErrorException
     */
    public function __construct(
        Query $query,
        ?array &$globalResultSet = null
    ) {

        $this->geonamesClient = Core::getGeoNameClient();

        parent::__construct( $query, $globalResultSet );
    }

}