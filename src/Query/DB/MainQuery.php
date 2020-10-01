<?php

namespace WPGeonames\Query\DB;

use ErrorException;
use WPGeonames\Core;
use WPGeonames\Entities\Location;
use WPGeonames\Query\ChildQueryTrait;
use WPGeonames\Query\DbQueryInterface;
use WPGeonames\Query\Executor;
use WPGeonames\Query\Status;

/**
 * Class MainQuery
 *
 * @package WPGeonames\Query\DB
 *
 */
class MainQuery
    extends
    Executor
    implements
    DbQueryInterface
{

    use ChildQueryTrait
    {
        ChildQueryTrait::__construct as private _ChildQueryTrait__construct;
    }

    use DbQueryTrait
    {
        DbQueryTrait::__construct as private _DbQueryTrait__construct;
    }

//  public properties

    /** @var \WPGeonames\Query\ChildQueryInterface */
    public static $_subQueryType = SubQuery::class;


    /**
     * MainQuery constructor.
     *
     * @param  \WPGeonames\QueryInterface  $parent
     * @param  array|null                  $default
     *
     * @throws \ErrorException
     * @noinspection MagicMethodsValidityInspection
     * @noinspection PhpMissingParentConstructorInspection
     * @noinspection PhpMissingParamTypeInspection
     */
    public function __construct(
        $parent,
        $default = null
    ) {

        $this->_ChildQueryTrait__construct( $parent );

        // make sure the table name is set for static::loadRecords
        if ( static::$_tblName === null )
        {
            static::$_tblName = Core::tblLocationsQueries;
        }

        $row = $this->findCachedQuery();

        $this->_reload( $row ?? [ 'query_id' => null ], $default );

    }


    public function findCachedQuery(): ?object
    {

        $searchCountries = $this->parent->getCountryAsArray();
        $searchCountry   = null;
        $sqlCountries    = '= %s';

        switch ( true )
        {
        case $searchCountries === null:
        case empty( $searchCountries ):
            $sqlCountries = 'IS NULL';
            break;

        case count( $searchCountries ) === 1:
            $searchCountry = reset( $searchCountries );
            break;

        default:
            $searchCountry = '[]'; // literal '[]' is used if two or more countries are used in a query
            break;
        }

        if ( $searchCountry )
        {
            $sqlCountries = Core::$wpdb->prepare( $sqlCountries, $searchCountry );
        }

        // json-serialise for storage in the DB's json field
        $serializedParams    = $this->parent->getSearchParamsAsJson( $searchTerm );
        $serializedCountries = $this->parent->getCountryAsJson( true );
        $serializedCountries = $serializedCountries === null
            ? '`search_countries` IS NULL'
            : Core::$wpdb->prepare( "JSON_CONTAINS(`search_countries`, %s , '$')", $serializedCountries );

        $sql = <<<SQL
SELECT
    *
FROM
    `wp_geonames_locations_queries`
WHERE
       `search_term` = %s
   AND `search_country_index` $sqlCountries
   AND $serializedCountries
   AND JSON_CONTAINS(`search_params`, %s , '$')
;
SQL;

        $sql = Core::$wpdb->prepareAndReplaceTablePrefix(
            str_replace( 'wp_geonames_locations_queries', static::$_tblName, $sql ),
            mb_strtolower( $searchTerm ),
            $serializedParams
        );

        // look for existing searches
        $cachedQueries = Core::$wpdb->get_results( $sql );

        /** @noinspection LoopWhichDoesNotLoopInspection */
        foreach ( $cachedQueries as $i => $cachedQuery )
        {

//            // ignore incomplete caches
//            if ( $cachedQuery->result_count < ( $cachedQuery->result_total ?? 0 )
//                || $cachedQuery->result_count === $this->parent->getMaxStartRow()
//            )
//            {
//                continue;
//            }

            return $cachedQuery;
        }

        return null;
    }


    /**
     * @return false|int
     * @throws \ErrorException
     */
    protected function insert()
    {

        if ( ( $this->queryId ?? 0 ) !== 0 )
        {
            return $this->update();
        }

        $sqlSearchTerm      = mb_strtolower( $this->parent->getSearchTerm() );
        $sqlSearchCountries = $this->parent->getCountryAsJson( true );
        $sqlSearchParams    = $this->parent->getSearchParamsAsJson();

        $return = Core::$wpdb->insertAndReplaceTablePrefix(
            Core::tblLocationsQueries,
            [
                'query_count'      => 1,
                'search_term'      => $sqlSearchTerm,
                'search_countries' => $sqlSearchCountries,
                'search_params'    => $sqlSearchParams,
            ],
            [
                // query_count
                '%d',
                // search_term
                '%s',
                // search_countries
                '%s',
                // search_params
                '%s',
            ]
        );

        if ( $return === false )
        {
            throw new ErrorException( Core::$wpdb->last_error, Core::$wpdb->last_error_no );
        }

        $this->queryId = Core::$wpdb->insert_id;

        return $this->reload();

    }


    /**
     * @param  string  $output
     *
     * @return \WPGeonames\Query\Status
     * @throws \ErrorException
     */
    public function query( $output = Location::class ): Status
    {

        $this->save( true );

        $result = parent::query( $output );

        $this->save();

        return $result;
    }


    /**
     * @throws \ErrorException
     */
    protected function reload(): bool
    {

        $params = static::loadRecords( $this->queryId );

        return $this->_reload( $params );
    }


    /**
     * @param  bool  $updateQueryCount
     *
     * @return false|int
     * @throws \ErrorException
     */
    public function save( bool $updateQueryCount = false )
    {

        if ( ( $this->queryId ?? 0 ) === 0 )
        {
            $return = $this->insert();
        }
        elseif ( ! $updateQueryCount && ! $this->_isDirty )
        {
            return false;
        }
        else
        {
            $return = $this->update( $updateQueryCount );
        }

        return $return;

    }


    /**
     * @param  bool  $updateQueryCount
     *
     * @return false|int
     * @throws \ErrorException
     */
    protected function update( bool $updateQueryCount = false )
    {

        $sqlUpdateQueryDate = $updateQueryCount
            ? 'CURRENT_TIMESTAMP'
            : 'NULL';

        $sqlAddQueryCount = $updateQueryCount
            ? 1
            : 0;

        $sql = Core::$wpdb->prepareAndReplaceTablePrefix(
            <<<SQL
UPDATE
	`wp_geonames_locations_queries` q
LEFT JOIN (
    SELECT
          query_id
        , sum(result_count)                       AS result_count
        , sum(COALESCE(result_total,-2147483648)) AS result_total
    FROM
         `wp_geonames_locations_queries_sub`
    GROUP BY
          query_id
    ) sub
    ON  q.query_id      = sub.query_id
SET
      q.`query_queried` = COALESCE($sqlUpdateQueryDate, q.`query_queried`)
    , q.`query_count`   = q.`query_count` + $sqlAddQueryCount
    , q.`result_count`  = COALESCE(sub.result_count, 0)
    , q.`result_total`  = IF(sub.result_total < 1, NULL, sub.result_total)
WHERE
    q.query_id = %d
;
SQL,
            $this->queryId
        );

        if ( Core::$wpdb->query( $sql ) === false )
        {
            throw new ErrorException( Core::$wpdb->last_error, Core::$wpdb->last_error_no );
        }

        return $this->reload();

    }

}