<?php

namespace WPGeonames\Query;

use ErrorException;
use WPGeonames\Entities\Country;
use WPGeonames\Entities\Location;

class Executor
    implements
    ChildQueryInterface,
    ParentQueryInterface
{

    use ChildQueryTrait,
        ParentQueryTrait,
        QueryTrait,
        QueryableTrait
    {
        ChildQueryTrait::__construct insteadof QueryTrait;
    }

//  public properties

    /** @var \WPGeonames\Query\ChildQueryInterface */
    public static $_subQueryType = ApiQuery::class;


    /**
     * @param  string  $output
     *
     * @return \WPGeonames\Query\Status
     * @throws \ErrorException
     */
    public function query( $output = Location::class ): Status
    {

        /** @var \WPGeonames\QueryInterface $parent */
        $parent                 = $this->parent;
        $parentSearchType       = $parent->getSearchType();
        $globalResultSet        = [];
        $status                 = new Status( $this, $globalResultSet );
        $status->result         =& $globalResultSet;
        $status->startAt        = $parent->getStartRow();
        $status->maxRecords     = $parent->getMaxRows();
        $status->total          = 0;
        $status->classLocations = $output;
        $status->classCountries = $output::$_countryClass
            ?? Location::$_countryClass
            ?? Country::class;

        foreach ( $parent->getSearchTypeAsArray() as $searchType => $searchTypeName )
        {
            if ( ( $parentSearchType & $searchType ) === 0 )
            {
                continue;
            }

            $subStatus = $this->createSubQuery( $searchType )
                              ->getStatus()
            ;

            $subStatus->globalResultSet =& $globalResultSet;
            $subStatus->startAt         = $status->startAt;
            $subStatus->maxRecords      = $status->maxRecords;
            $subStatus->classLocations  = $status->classLocations;
            $subStatus->classCountries  = $status->classCountries;

            $subStatus = $subStatus->query->query( $output );

            // go to next search type if there have no records been processed
            if ( $subStatus->processRecords === 0 )
            {
                $globalResultSet[ $searchTypeName ] = [];
                continue;
            }

            // if we have more than allowed records, splice the rest away
            if ( $subStatus->count > $status->maxRecords )
            {
                array_splice( $subStatus->result, $status->maxRecords );
            }

            if ( ! empty(
            $duplicates = array_intersect_key(
                $subStatus->result,
                [],
                ...
                array_values( $globalResultSet )
            )
            ) )
            {
                throw new ErrorException( 'Duplicate keys in result set: ' . implode( ',', $duplicates ) );
            }
            unset( $duplicates );

            $status->result[ $searchTypeName ] = $subStatus->result;
            $status->count                     += $subStatus->count;
            $status->total                     += $subStatus->total;
            $status->processRecords            += $subStatus->processRecords;

            if ( $status->startAt > 0 )
            {
                $status->startAt = max( 0, $status->startAt - $subStatus->processRecords );
            }

            $status->maxRecords -= $subStatus->count;

            if ( $status->maxRecords <= 0 )
            {
                break;
            }

        }

        return $status;

    }

}