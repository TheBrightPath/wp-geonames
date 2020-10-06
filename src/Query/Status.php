<?php

namespace WPGeonames\Query;

use ErrorException;
use WPGeonames\Entities\Location;
use WPGeonames\QueryInterface;

class Status
{

//  public properties

    /** @var
     * \WPGeonames\Query\Query
     */
    public $query;

    /**
     * @var \WPGeonames\QueryInterface
     */
    public $mainQuery;

    /**
     * @var array Current result
     */
    public $result = [];

    /**
     * @var int count($result)
     */
    public $count = 0;

    /**
     * @var int|null Total of found records in current request
     */
    public $total;

    public $startAt    = 0;
    public $maxRecords = Query::MAX_ROWS;

    public $processRecords = 0;

    /** @var array|null */
    public $globalResultSet;

    /**
     * @var \WPGeonames\Entities\Location
     */
    public $classLocations = Location::class;

    /**
     * @var \WPGeonames\Entities\Country
     */
    public $classCountries;


    /**
     * wpGeonamesQueryStatus constructor.
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

        $this->query           = $query;
        $this->globalResultSet =& $globalResultSet;

        // find main query
        while ( ! $query instanceof QueryInterface )
        {

            if ( ! $query instanceof ChildQueryInterface )
            {
                throw new ErrorException( 'Invalid query chain!' );
            }

            $query = $query->parent;
        }

        $this->mainQuery = $query;
    }

}