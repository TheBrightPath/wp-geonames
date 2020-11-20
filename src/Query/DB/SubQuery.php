<?php

namespace WPGeonames\Query\DB;

use DateTime;
use ErrorException;
use WPGeonames\Core;
use WPGeonames\Entities\Country;
use WPGeonames\Entities\Location;
use WPGeonames\Query\ApiQuery;
use WPGeonames\Query\ApiStatus;
use WPGeonames\Query\ChildQueryTrait;
use WPGeonames\Query\DbQueryInterface;
use WPGeonames\Query\Exceptions\InvalidCacheResultSet;
use WPGeonames\Query\Executor;
use WPGeonames\Query\ParentQueryInterface;
use WPGeonames\Query\ParentQueryTrait;
use WPGeonames\Query\Query;
use WPGeonames\Query\QueryableTrait;
use WPGeonames\Query\QueryTrait;
use WPGeonames\Query\Status;
use WPGeonames\Query\SubQueryTrait;

/**
 * Class ChildQueryInterface
 *
 * @package      WPGeonames
 *
 * @property int|null                $queryId
 * @property \DateTimeImmutable|null $queryCreated
 * @property \DateTimeImmutable|null $queryUpdated
 * @property int|null                $resultCount
 * @property int|null                $resultTotal
 *
 * @noinspection TraitsPropertiesConflictsInspection
 */
class SubQuery
    implements
    DbQueryInterface,
    Query,
    ParentQueryInterface
{

    use SubQueryTrait
    {
        __construct as private __SubQueryTrait_construct;
    }

    use ChildQueryTrait
    {
        ChildQueryTrait::__construct as private _ChildQueryTrait__construct;
        SubQueryTrait::getSearchType insteadof ChildQueryTrait;
        SubQueryTrait::setSearchType insteadof ChildQueryTrait;
    }

    use DbQueryTrait
    {
        DbQueryTrait::__construct as private DbQueryTrait__construct;
    }

    use QueryTrait
    {
        ChildQueryTrait::__construct insteadof QueryTrait;
        DbQueryTrait::loadValues insteadof QueryTrait;
        DbQueryTrait::__set insteadof QueryTrait;
    }

    use QueryableTrait;
    use ParentQueryTrait;

//  public properties

    /**
     * Will be set during first instantiation unless already set
     *
     * @var \WPGeonames\Query\ChildQueryInterface
     */
    public static $_subQueryType;


    /**
     * ChildQueryInterface constructor.
     *
     * @param  \WPGeonames\Query\DB\MainQuery  $parent
     * @param  array|object|int                $params
     *
     * @throws \ErrorException
     * @noinspection PhpMissingParamTypeInspection
     */
    public function __construct(
        $parent,
        $params = []
    ) {

        if ( ! $parent instanceof MainQuery )
        {
            throw new ErrorException(
                sprintf( 'Parameter 1 of %s needs to be an instance of %s', static::class, MainQuery::class )
            );
        }

        if ( static::$_subQueryType === null )
        {
            static::$_subQueryType = Executor::$_subQueryType ?? ApiQuery::class;
        }

        // save the parent
        $this->_ChildQueryTrait__construct( $parent );

        // save the params
        $this->_setNullOnEmptyPropertyOnSet = false;
        $this->_ignoreEmptyPropertyOnSet    = false;
        $this->loadValues( $params );

        // make sure the search type has been provided
        /** @noinspection PhpParamsInspection */
        $this->__SubQueryTrait_construct();

        // make sure the table name is set for static::loadRecords
        if ( static::$_tblName === null )
        {
            static::$_tblName = Core::tblLocationsSubQueries;
        }

        // if the parent's queryId has been provided, load the record
        if ( null === $this->queryId = $parent->getQueryId() )
        {
            throw new ErrorException( 'no queryId available in parent class.' );
        }

        $this->reload();

        $this->_status = new Status( $this );

        add_filter(
            "geonames/cache/lookup/type={$this->searchType}",
            [
                $this,
                'cacheLookup',
            ]
        );

        add_filter(
            "geonames/cache/result/type={$this->searchType}",
            [
                $this,
                'cacheStoreRecords',
            ],
            10,
            2
        );

    }


    /**
     * @param  int|null    $offset
     * @param  int|null    $limit
     * @param  array|null  $exclude
     *
     * @return array|object|null
     * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
     */
    public function getCachedResult(
        ?int $offset = null,
        ?int $limit = null,
        ?array &$exclude = null
    ) {

        $offset     = $offset ?? $this->_status->startAt;
        $limit      = $limit ?? $this->_status->maxRecords ?? - 1;
        $sqlLimit   = '';
        $sqlExclude = '';

        if ( $offset >= 0 && $limit < 0 )
        {
            $limit = 18446744073709551615;
        }

        if ( $limit >= 0 )
        {
            $sqlLimit = "LIMIT $limit\n";

            if ( $offset > 0 )
            {
                $sqlLimit .= "OFFSET $offset\n";
            }
        }

        if ( $exclude !== null && ! empty( $exclude ) )
        {
            $sqlExclude = sprintf(
                'AND r.geoname_id NOT IN (SELECT geoname_id FROM `wp_geonames_locations_results` WHERE query_id = %d AND search_type IN ("%s"))',
                $this->queryId,
                implode( '","', array_keys( $exclude ) )
            );
        }

        $sql = Core::$wpdb->prepareAndReplaceTablePrefix(
            <<<SQL
SELECT
      r.`order`
    , r.`score`
    , r.`geoname_id` as `_id`
    , l.*
    , l.`geoname_id` as `idLocation`
FROM
        `wp_geonames_locations_results` r
    LEFT JOIN
        `wp_geonames_locations_cache` l on l.geoname_id = r.geoname_id
WHERE
    1
    AND r.query_id = %d
    AND r.search_type = %s
    $sqlExclude
ORDER BY
      r.order
$sqlLimit
;
SQL
            ,
            $this->queryId,
            $this->getSearchTypeName()
        );

        return Core::$wpdb->get_results( $sql );

    }


    /**
     * @return bool|int
     * @throws \ErrorException
     */
    protected function cacheDeleteResultSet()
    {

        if ( ( $this->queryId ?? 0 ) === 0 )
        {
            return 0;
        }

        if ( ( $deleted = Core::$wpdb->deleteAndReplaceTablePrefix(
                Core::tblLocationsResults,
                [
                    'query_id'    => $this->queryId,
                    'search_type' => $this->getSearchTypeName(),
                ],
                [
                    '%d',
                    '%s',
                ]
            ) ) === false )
        {
            throw new ErrorException(
                sprintf(
                    'Cache result set could not be deleted for %d.%s: %d %s',
                    $this->queryId,
                    $this->getSearchTypeName() ?? (string) $this->getSearchType(),
                    Core::$wpdb->last_error_no,
                    Core::$wpdb->last_error
                )
            );
        }

        $this->_status->result = [];

        $this->update();

        return $deleted;
    }


    /**
     * @param  \WPGeonames\Query\Status  $status
     *
     * @return \WPGeonames\Query\Status
     * @throws \ErrorException
     * @throws \WPGeonames\Query\Exceptions\InvalidCacheResultSet
     */
    public function cacheLookup(
        Status $status
    ): Status {

        // ignore if ...
        if (
            // ... it's not our own status
            $status !== $this->_status

            // ... we haven't cached that search before
            || $this->resultTotal === null

            // ... we already got more than required
            || $status->processRecords - $status->startAt >= $status->maxRecords

            // ... the first record is larger than what the result has been reported to be
            || $status->startAt >= $this->resultTotal

        )
        {
            if ( $status->startAt >= ( $this->resultTotal ?? PHP_INT_MAX ) )
            {
                $status->processRecords += $this->resultTotal;
            }

            return $status;
        }

        if (

            $status->startAt < $status->processRecords
        )
        {
            $diff  = $status->processRecords - $status->startAt;
            $start = $status->startAt + $diff;
            $limit = max( 0, $status->maxRecords - $diff );
        }
        else
        {
            $start = $status->startAt;
            $limit = max( 0, $status->maxRecords );
        }

        if ( $limit === 0 )
        {
            return $status;
        }

        $result = $this->getCachedResult( $start, $limit, $status->globalResultSet );
        $valid  = $this->validateCacheResult( $result );

        if ( $valid === null )
        {
            // no records found

            $status->processRecords += $start;

            return $status;
        }

        if ( $valid === false )
        {
            // invalid result

            // delete result entries
            $this->cacheDeleteResultSet();

            return $status;

        }

        /** @noinspection AdditionOperationOnArraysInspection */
        $status->result         += $result;
        $status->processRecords += $start + count( $result );
        $status->count          = count( $status->result );

        return $status;
    }


    /**
     * @param  \WPGeonames\Query\Status  $status
     * @param  \WPGeonames\Query\Status  $myOwnStatus
     *
     * @return \WPGeonames\Query\ApiStatus|\WPGeonames\Query\Status
     * @throws \ErrorException
     */
    public function cacheStoreRecords(
        Status $status,
        Status $myOwnStatus
    ) {

        // skip if we're not dealing with our own status
        if ( $myOwnStatus !== $this->_status )
        {
            return $status;
        }

        // skip non-api results
        if ( ! $status instanceof ApiStatus )
        {
            return $status;
        }

        $search_type = $myOwnStatus->query->getSearchTypeName();

        if ( empty( $search_type ) )
        {
            throw new ErrorException( 'Search type not supported' );
        }

        if ( ! $status->keepPreviouslyCachedRecords )
        {
            $this->cacheDeleteResultSet();
        }

        if ( ! empty( $status->result ) )
        {
            // pre-load countries so they are loaded with one single sql query
            $status->classCountries::load();

            // store locations to the database
            Location::saveAll( $status->result, 0 );
        }

        // store result set to the database
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $query_id = $myOwnStatus->query->queryId;
        $i        = count( $myOwnStatus->result );

        array_walk(
            $status->result,
            static function (
                Location $location
            )
            use
            (
                &
                $query_id,
                &
                $search_type,
                &
                $i
            )
            {

                if ( false === Core::$wpdb->insertAndReplaceTablePrefix(
                        core::tblLocationsResults,
                        [
                            'query_id'    => $query_id,
                            'search_type' => $search_type,
                            'geoname_id'  => $location->getGeonameId(),
                            'order'       => ++ $i,
                            'score'       => $location->getScore(),
                        ],
                        [
                            // query_id
                            '%d',
                            // search_type
                            '%s',
                            // geoname_id
                            '%d',
                            // order
                            '%d',
                            // score
                            '%f',
                        ]
                    ) )
                {
                    throw new ErrorException( Core::$wpdb->last_error );
                }
            }
        );

        $this->update();

        return $status;

    }


    /**
     * @param  bool  $updateQueryCount
     *
     * @return false|int
     * @throws \ErrorException
     */
    protected function insert( bool $updateQueryCount = false )
    {

        if ( $this->queryCreated !== null )
        {
            return $this->update();
        }

        $sqlSearchType   = $this->getSearchTypeName();
        $sqlQueryQueried = $updateQueryCount
            ? null
            : '0000-00-00 00:00:00';

        $return = Core::$wpdb->insertAndReplaceTablePrefix(
            Core::tblLocationsSubQueries,
            [
                'query_id'      => $this->queryId,
                'search_type'   => $sqlSearchType,
                'query_queried' => $sqlQueryQueried,
                'query_count'   => (int) $updateQueryCount,
                'result_count'  => 0,
            ],
            [
                // query_id
                '%d',
                // search_type
                '%s',
                // query_queried
                '%s',
                // query_count
                '%d',
                // result_count
                '%d',
            ]
        );

        if ( $return === false )
        {
            throw new ErrorException( Core::$wpdb->last_error, Core::$wpdb->last_error_no );
        }

        return $this->reload();

    }


    /**
     * @param  string  $output
     *
     * @return mixed|\WPGeonames\Query\Status
     * @throws \ErrorException
     */
    public function query( $output = Location::class ): Status
    {

        $this->save( true );

        $searchType             = $this->getSearchType();
        $searchTypeName         = $this->getSearchTypeName();
        $status                 = $this->_status;
        $status->classLocations = $output;
        $status->classCountries = $status->classCountries
            ?? $output::$_countryClass
            ?? Location::$_countryClass
            ?? Country::class;

        // search for cached results
        // filter need to return the number of records
        $status = apply_filters( "geonames/cache/lookup/type=$searchType", $status );
        $status = apply_filters( "geonames/cache/lookup/name=$searchTypeName", $status );
        $status = apply_filters( "geonames/cache/lookup", $status );

        // don't touch if it's no longer our own status
        if ( $status !== $this->_status )
        {
            $status = apply_filters( "geonames/cache/result", $status, $this->_status );
            $status = apply_filters( "geonames/cache/result/type=$searchType", $status, $this->_status );
            $status = apply_filters( "geonames/cache/result/name=$searchTypeName", $status, $this->_status );

            return $status;
        }

        if (
        !   // Check if we need to fetch any records from the geonames server
        (
            // we haven't fetched any records before
            ( $this->resultTotal === null )

            || (
                // there are still records to be fetched
                ( $this->resultCount < $this->resultTotal )

                // we still need more records
                && ( $status->count < $status->maxRecords )

                // the first requested record is smaller than what is available
                && ( $status->startAt < $this->resultTotal )

                // the last requested record is smaller than what we have stored
                //&& $status->processRecords - $status->startAt < $this->resultTotal
            )
        ) )
        {
            $status->result = $status->classLocations::load( $status->result, null, $status->classCountries );

            $status->total = $this->getResultTotal();

            return $status;
        }

        /**
         * @var \WPGeonames\Query\ApiQuery  $apiQuery
         * @var \WPGeonames\Query\ApiStatus $apiStatus
         * @var \WPGeonames\Query\ApiStatus $apiStatus
         */
        $apiStatus = $this->createSubQuery( $searchType )
                          ->getStatus()
        ;

        $apiStatus->globalResultSet =& $status->globalResultSet;

        $apiStatus->keepPreviouslyCachedRecords = (
            $this->resultTotal === null
            || $status->processRecords === 0
            || ( $diff = ( $this->getQueryUpdated() ?? $this->getQueryCreated() )->diff( new DateTime() ) ) === false
            || $diff->days === false
            || $diff->days > 7
        );
        unset( $diff );

        $apiStatus->startAt = $apiStatus->keepPreviouslyCachedRecords
            ? $status->processRecords
            : 0;

        $apiStatus->maxRecords     = $status->maxRecords;
        $apiStatus->classLocations = $status->classLocations;
        $apiStatus->classCountries = $status->classCountries;

        $apiStatus = $apiStatus->query->query();

        // check if query has been disabled
        if ( $apiStatus->parameters === null )
        {
            $this->setResultTotal( 0 )
                 ->save()
            ;

            return $status;
        }

        // otherwise we should have received the total of the search
        if ( $apiStatus->total === null )
        {
            throw new ErrorException( 'Geonames API did not return a totalResultCount' );
        }

        $this->setResultTotal( $apiStatus->total );

        // store the new records in cache
        $apiStatus = apply_filters( "geonames/cache/result", $apiStatus, $this->_status );
        $apiStatus = apply_filters( "geonames/cache/result/type=$searchType", $apiStatus, $this->_status );
        $apiStatus = apply_filters( "geonames/cache/result/name=$searchTypeName", $apiStatus, $this->_status );

        $this->save();

        // since we don't have the entire de-duplication information available, get the result again from the cache!
        $status->result         = [];
        $status->processRecords = 0;
        $status->count          = 0;

        $status = apply_filters( "geonames/cache/lookup/type=$searchType", $status );
        $status = apply_filters( "geonames/cache/lookup/name=$searchTypeName", $status );
        $status = apply_filters( "geonames/cache/lookup", $status );

        $status->result = $status->classLocations::load( $status->result, null, $status->classCountries );

        $status->duplicates = array_diff_key( $apiStatus->result ?? [], $status->result ?? [] );
        $status->total      = $this->getResultTotal();

        return $status;

    }


    /**
     * @throws \ErrorException
     */
    protected function reload(): bool
    {

        $sqlWhere = Core::$wpdb->prepare(
            'query_id = %d AND search_type = %s',
            $this->queryId,
            $this->getSearchTypeName()
        );

        $params = static::loadRecords( $sqlWhere );

        return ! empty( $params ) && $this->_reload( $params );
    }


    /**
     * @param  bool  $updateQueryCount
     *
     * @return bool|int
     * @throws \ErrorException
     */
    public function save( bool $updateQueryCount = false )
    {

        if ( $this->queryId === null || ( $searchType = $this->getSearchTypeName() ) === null )
        {
            throw new ErrorException(
                sprintf(
                    'Cannot save %s without queryId (%d) or searchType (%s)',
                    static::class,
                    $this->queryId,
                    $searchType ?? $this->getSearchTypeName()
                )
            );
        }

        if ( $this->getQueryCreated() === null )
        {
            return $this->insert( $updateQueryCount );
        }

        if ( ! $updateQueryCount && ! $this->_isDirty )
        {
            return false;
        }

        return $this->update( $updateQueryCount );
    }


    /**
     * @param  bool  $updateQueryCount
     *
     * @return bool|int
     * @throws \ErrorException
     */
    protected function update( bool $updateQueryCount = false )
    {

        $searchType = $this->getSearchTypeName();

        /** @noinspection ProperNullCoalescingOperatorUsageInspection */
        $sqlResultTotal = $this->resultTotal ?? 'NULL';

        $sqlUpdateQueryDate = $updateQueryCount
            ? 'CURRENT_TIMESTAMP'
            : 'NULL';

        $sqlAddQueryCount = $updateQueryCount
            ? 1
            : 0;

        $sql = Core::$wpdb->prepareAndReplaceTablePrefix(
            <<<SQL
UPDATE
	`wp_geonames_locations_queries_sub` qOld

INNER JOIN 
    (
    SELECT
          {$this->queryId}            AS `id`
        , %s                          AS `type`
        , $sqlResultTotal             AS `result_total`
        , $sqlUpdateQueryDate         AS `new_query_date`
        , $sqlAddQueryCount           AS `add_query_count`
    ) as qNew
    	ON  qNew.id = qOld.query_id
        AND qNew.type = qOld.search_type
LEFT JOIN
	(SELECT
     	  query_id
	    , search_type
     	, COUNT(*) as `count`
     	-- , MAX(`order`) as `order`
     FROM
     	`wp_geonames_locations_results`
     GROUP BY
     	  query_id
	    , search_type
    )
     as r
     ON  r.query_id = qOld.query_id
     AND r.search_type = qOld.search_type
SET
      qOld.`result_count`    = COALESCE(r.`count`, 0) 
    , qOld.`query_updated`   = CASE 
        WHEN qOld.`result_count` != COALESCE(r.`count`, 0) THEN CURRENT_TIMESTAMP
        WHEN COALESCE(qOld.`result_total`,-1) != COALESCE(qNew.`result_total`, -1) THEN CURRENT_TIMESTAMP
        ELSE qOld.`query_updated` END
    , qOld.`query_queried`   = COALESCE(qNew.`new_query_date`, qOld.`query_queried`)         
    , qOld.`query_count`     = qOld.`query_count` + qNew.`add_query_count`    
    , qOld.`result_total`    = COALESCE(qNew.`result_total`, qOld.`result_total`)            
;
SQL,
            $searchType
        );

        if ( Core::$wpdb->query( $sql ) === false )
        {
            return false;
        }

        return $this->reload();

    }


    /**
     * @param  array|null                     $result
     * @param  \WPGeonames\Query\Status|null  $status
     *
     * @return bool|null
     * @throws \WPGeonames\Query\Exceptions\InvalidCacheResultSet
     */
    protected function validateCacheResult(
        ?array &$result,
        ?Status $status = null
    ): ?bool {

        if ( empty( $result ) )
        {
            return null;
        }

        $row = (object) reset( $result );

        if ( ( $status ?? $status = $this->_status )->startAt > $row->order - 1 )
        {
            throw new InvalidCacheResultSet( 'start row does not match order: ', $row );
        }

        $lastOrder = $status->startAt;

        try
        {
            array_walk(
                $result,
                static function ( &$row ) use
                (
                    &
                    $status,
                    &
                    $lastOrder
                )
                {

                    $geoname_id = null;
                    $order      = null;

                    if ( is_array( $row ) )
                    {
                        $row = (object) $row;
                    }

                    if ( ! is_object( $row )
                        || empty( $geoname_id = $row->geoname_id )
                        || empty( $order = $row->order )
                    )
                    {
                        throw new InvalidCacheResultSet( 'invalid row', $row );
                    }

                    if ( $geoname_id === null || ! is_numeric( $geoname_id ) || $geoname_id <= 0 )
                    {
                        throw new InvalidCacheResultSet( 'invalid genome_id', $row );
                    }

                    if ( $order === null || ! is_numeric( $order ) || (int) $order <= $lastOrder )
                    {
                        throw new InvalidCacheResultSet( 'invalid order', $row );
                    }

                    $order = (int) $order;
                    $step  = $order - $lastOrder;

                    if ( $step > 1 )
                    {
                        $status->duplicates = array_replace(
                            $status->duplicates,
                            array_fill( $lastOrder + 1, $step - 1, null )
                        );
                    }

                    $lastOrder = $order;
                }

            );
        }
        catch ( InvalidCacheResultSet $e )
        {
            return false;
        }

        return true;
    }

}