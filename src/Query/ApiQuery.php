<?php

namespace WPGeonames\Query;

use ErrorException;
use WPGeonames\Entities\Location;
use WPGeonames\WpDb;

class ApiQuery
    implements
    ChildQueryInterface,
    SubQueryInterface,
    Query
{

    use SubQueryTrait;

    use ChildQueryTrait
    {
        ChildQueryTrait::__construct as private __ChildQueryTrait_construct;
        SubQueryTrait::getSearchType insteadof ChildQueryTrait;
        SubQueryTrait::setSearchType insteadof ChildQueryTrait;
    }

    use QueryTrait
    {
        ChildQueryTrait::__construct insteadof QueryTrait;
    }

    use QueryableTrait;

// constants
    public const DEFAULT_PAGE_SIZE_TO_FETCH = 100;


    /**
     * ApiQuery constructor.
     *
     * @param         $parent
     * @param  array  $params
     *
     * @throws \ErrorException
     */
    public function __construct(
        $parent,
        $params = []
    ) {

        // save the parent
        $this->__ChildQueryTrait_construct( $parent );

        // save the params
        $this->loadValues( $params );

        // make sure the search type has been provided
        if ( $this->getSearchType() <= 0 )
        {
            throw new ErrorException(
                sprintf( 'SearchType not provided for %s', static::class )
            );
        }

        $this->_status = new ApiStatus( $this );

    }


    /**
     * @param  string  $output
     *
     * @return \WPGeonames\Query\Status
     * @throws \ErrorException
     * @noinspection AdditionOperationOnArraysInspection
     */

    public function query( $output = null ): Status
    {

        $searchType     = $this->getSearchType();
        $searchTypeName = $this->getSearchTypeName();
        $status         = $this->_status;

        // get query parameters
        if ( empty( $status->parameters = $status->mainQuery->toArray( $searchType ) ) )
        {
            throw new ErrorException( 'No search parameters found' );
        }

        $status->parameters['orderby']  = 'relevance';
        $status->parameters['startRow'] = $status->startAt;
        $status->parameters['maxRows']  = static::DEFAULT_PAGE_SIZE_TO_FETCH;
        $status->parameters['style']    = 'full';

        // allow parameters to be changed
        $status = apply_filters( "geonames/api/params/type=$searchType", $status );
        $status = apply_filters( "geonames/api/params/name=$searchTypeName", $status );
        $status = apply_filters( "geonames/api/params", $status );

        if ( $status !== $this->_status )
        {
            return $status;
        }

        if ( $status->parameters === null )
        {
            return $this->_status;
        }

        $api            =& $status->geonamesClient;
        $params         =& $status->parameters;
        $maxStart       = $status->mainQuery->getMaxStartRow();
        $duplicates     = array_merge( ... array_values( $status->globalResultSet ) );
        $loopPrevention = (int) ceil( $maxStart / $params['maxRows'] );

        unset( $params['maxStartRow'], $params['page'] );

        do
        {

            $result             = $api->search( $params );
            $params['startRow'] += count( $result );
            $status->total      = $status->total ?? $api->getLastTotalResultsCount();

            if ( method_exists( $api, 'getLastUrlUsed' ) )
            {
                /** @noinspection ForgottenDebugOutputInspection */
                error_log(
                    sprintf( "%s %d\n", $api->getLastUrlUsed(), count( $result ) ),
                    3,
                    ABSPATH . '/wp-content/uploads/api_debug.log'
                );
            }

            if ( empty( $result ) )
            {
                unset( $result );
                continue;
            }

            // generate key names
            WpDb::formatOutput( $result, OBJECT_K, 'geonameId', '_' );

            // filter out duplicates
            $status->duplicates = array_intersect_key( $result, $duplicates );

            array_walk(
                $result,
                static function ( object $location ) use
                (
                    &
                    $status
                )
                {

                    if ( $location instanceof Location )
                    {
                        return;
                    }

                    // copy geoname id to Api ID in order to remember that we've just loaded this from the API
                    $location->idAPI = $location->geonameId;

                    // check if it's a country
                    if ( Location::isItACountry( $location, 'fcl', 'fcode' ) )
                    {
                        $location->__CLASS__ = $status->classCountries;
                    }

                }
            );

            // convert result
            $class  = $output ?? $status->classLocations;
            $result = $class::load(
                $result,
                (object) [
                    'locationClass' => $status->classLocations,
                    'countryClass'  => $status->classCountries,
                ]
            );

            // filter out duplicates
            $duplicates += array_diff_key( $result, $duplicates );

            // append result
            $status->result += $result;
            $status->count  = count( $status->result );

            unset ( $result );
        }
        while (

            // we should not do this more often that to get ALL possible results
            -- $loopPrevention > 0

            // start row is still below max start row
            && $params['startRow'] < $maxStart

            // we kno know that there are more
            && $status->count < ( $status->total && 0 )

            // but we haven't received as many records yet, as required, considering that we don't count duplicates
            && $status->count - count( $duplicates ) < $status->maxRecords
        );

        $status = apply_filters( "geonames/api/result", $status );
        $status = apply_filters( "geonames/api/result/name=$searchTypeName", $status );
        $status = apply_filters( "geonames/api/result/type=$searchType", $status );

        return $status;
    }

}