<?php

namespace WPGeonames\Query\DB;

use DateTime;
use DateTimeImmutable;
use WPGeonames\Helpers\DbObjectTrait;
use WPGeonames\Query\DbQueryInterface;
use WPGeonames\Query\QueryTrait;

trait DbQueryTrait
{

    use QueryTrait
    {
        //QueryTrait::__construct as private _QueryTrait__construct;
        QueryTrait::__set as private _QueryTrait__set;
        QueryTrait::loadValues as protected _QueryTrait_loadValues;
    }

    use DbObjectTrait;

// protected properties

    protected static $_aliases
        = [
            'query_id'      => 'queryId',
            'query_created' => 'queryCreated',
            'query_updated' => 'queryUpdated',
            'query_queried' => 'queryQueried',
            'query_count'   => 'queryCount',
            'result_count'  => 'resultCount',
            'result_total'  => 'resultTotal',
        ];

    /**
     * query_id
     *
     * @var int|null
     */
    protected $queryId;

    /**
     * query_count
     *
     * @var int
     */
    protected $queryCount;

    /**
     * query_queried
     *
     * @var \DateTimeImmutable
     */
    protected $queryQueried;

    /**
     * query_created
     *
     * @var \DateTimeImmutable
     */

    protected $queryCreated;
    /**
     * query_updated
     *
     * @var \DateTimeImmutable|null
     */

    protected $queryUpdated;
    /**
     * result_count
     *
     * @var int|null
     */
    protected $resultCount;

    /**
     * result_total
     *
     * @var int|null
     */
    protected $resultTotal;


    /**
     * @return int
     */
    public function getQueryCount(): int
    {

        return $this->queryCount;
    }


    /**
     * @param  int  $queryCount
     *
     * @return \WPGeonames\Query\DbQueryInterface|\WPGeonames\Query\DB\DbQueryTrait
     */
    public function setQueryCount( int $queryCount )
    {

        $this->queryCount = $queryCount;

        return $this;
    }


    /**
     * @return \DateTimeImmutable|null
     */
    public function getQueryCreated(): ?DateTimeImmutable
    {

        return $this->queryCreated;
    }


    /**
     * @param  \DateTimeImmutable|string|null  $queryCreated
     *
     * @return \WPGeonames\Query\DbQueryInterface|\WPGeonames\Query\DB\DbQueryTrait
     * @throws \Exception
     */
    public function setQueryCreated( $queryCreated ): DbQueryInterface
    {

        return $this->setDateProperty( $this->queryCreated, $queryCreated );
    }


    /**
     * @return int|null
     */
    public function getQueryId(): ?int
    {

        return $this->queryId;
    }


    /**
     * @param  int|null  $queryId
     *
     * @return \WPGeonames\Query\DbQueryInterface|\WPGeonames\Query\DB\DbQueryTrait
     */
    public function setQueryId( ?int $queryId ): DbQueryInterface
    {

        return $this->setProperty( $this->queryId, $queryId );
    }


    /**
     * @return \DateTimeImmutable
     */
    public function getQueryQueried(): DateTimeImmutable
    {

        return $this->queryQueried;
    }


    /**
     * @param  \DateTimeImmutable|string|int|null  $queryQueried
     *
     * @return \WPGeonames\Query\DbQueryInterface|\WPGeonames\Query\DB\DbQueryTrait
     * @throws \Exception
     */
    public function setQueryQueried( $queryQueried ): DbQueryInterface
    {

        return $this->setDateProperty( $this->queryQueried, $queryQueried );
    }


    /**
     * @return \DateTimeImmutable|null
     */
    public function getQueryUpdated(): ?DateTimeImmutable
    {

        return $this->queryUpdated;
    }


    /**
     * @param  \DateTimeImmutable|string|int|null  $queryUpdated
     *
     * @return \WPGeonames\Query\DbQueryInterface|\WPGeonames\Query\DB\DbQueryTrait
     * @throws \Exception
     */
    public function setQueryUpdated( $queryUpdated ): DbQueryInterface
    {

        return $this->setDateProperty( $this->queryUpdated, $queryUpdated );
    }


    /**
     * @return int|null
     */
    public function getResultCount(): ?int
    {

        return $this->resultCount;
    }


    /**
     * @param  int|null  $resultCount
     *
     * @return \WPGeonames\Query\DbQueryInterface|\WPGeonames\Query\DB\DbQueryTrait
     */
    public function setResultCount( ?int $resultCount ): DbQueryInterface
    {

        return $this->setProperty( $this->resultCount, $resultCount );
    }


    /**
     * @return int|null
     */
    public function getResultTotal(): ?int
    {

        return $this->resultTotal;
    }


    /**
     * @param  int|null  $resultTotal
     *
     * @return \WPGeonames\Query\DbQueryInterface|\WPGeonames\Query\DB\DbQueryTrait
     */
    public function setResultTotal( ?int $resultTotal ): DbQueryInterface
    {

        return $this->setProperty( $this->resultTotal, $resultTotal );
    }


    /**
     * @param  \DateTimeImmutable|null             $property
     * @param  \DateTimeImmutable|string|int|null  $newDate
     *
     * @return \WPGeonames\Query\DbQueryInterface|\WPGeonames\Query\DB\DbQueryTrait
     * @throws \Exception
     * @noinspection ReferencingObjectsInspection
     */
    protected function setDateProperty(
        ?DateTimeImmutable &$property,
        $newDate
    ): DbQueryInterface {

        $newDate = $this->parseDate( $newDate );

        return $this->setProperty( $property, $newDate );
    }


    /**
     * @param  mixed  $property
     * @param  mixed  $value
     *
     * @return \WPGeonames\Query\DbQueryInterface|\WPGeonames\Query\DB\DbQueryTrait
     */
    protected function setProperty(
        &$property,
        $value
    ): DbQueryInterface {

        $this->_isDirty = $this->_isDirty || $property !== $value;
        $property       = $value;

        return $this;
    }


    public function __set(
        $property,
        $value
    ) {

        if ( $this->_isLoading )
        {
            return $this->___set( $property, $value );
        }

        $old    = $this->__get( $property );
        $return = $this->___set( $property, $value );

        if ( ! $this->_isDirty && $old !== $this->__get( $property ) )
        {
            $this->_isDirty = true;
        }

        return $return;
    }


    /**
     * @param         $record
     * @param  array  $defaults
     *
     * @return bool
     */
    protected function _reload(
        $record,
        $defaults = []
    ): bool {

        // if we got this from a loadRecords call, it will be a result set ...
        if ( is_array( $record ) && key( $record ) === 0 && count( $record ) === 1 && is_object( $record[0] ) )
        {
            $record = reset( $record );
        }

        $record = (array) $record;

        if ( $this->queryId === null )
        {
            $this->queryId = (int) $record['query_id'];
        }

        unset(
            $record['query_id'],
            $record['search_term'],
            $record['search_type'],
            $record['search_params'],
            $record['search_countries'],
            $record['search_country_index']
        );

        $notLoaded = $this->loadValues( $record, $defaults );

        $this->_isDirty = false;

        return $notLoaded === 0;
    }


    public function loadValues(
        $values,
        $defaults = [],
        ?bool $ignoreNonExistingProperties = true
    ): ?int {

        $this->_isLoading = true;
        $missed           = $this->_QueryTrait_loadValues( $values, $defaults, $ignoreNonExistingProperties );
        $this->_isLoading = false;

        return $missed;
    }


    /**
     * @param $date
     *
     * @return \DateTimeImmutable|null
     * @throws \Exception
     */
    protected function parseDate( $date ): ?DateTimeImmutable
    {

        if ( $date instanceof DateTimeImmutable )
        {
            return $date;
        }

        if ( $date instanceof DateTime )
        {
            return DateTimeImmutable::createFromMutable( $date );
        }

        if ( is_string( $date ) )
        {
            return new DateTimeImmutable( $date );
        }

        if ( is_int( $date ) )
        {
            return new DateTimeImmutable( "@$date" );
        }

        return null;
    }

}