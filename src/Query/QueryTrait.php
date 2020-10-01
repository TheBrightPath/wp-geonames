<?php

namespace WPGeonames\Query;

use ErrorException;
use WPGeonames\Helpers\FlexibleObjectTrait;

trait QueryTrait
{

    use FlexibleObjectTrait
    {
        FlexibleObjectTrait::__construct as protected _FlexibleObjectTrait__construct;
        //cleanArray as protected __FlexibleObjectTrait_cleanArray;
        //toArray as protected ___toArray;
    }

    /**
     * @param  null  $searchTypeFilter
     *
     * @param  bool  $returnKeys
     *
     * @return string[]
     */
    public function getSearchTypeAsArray(
        $searchTypeFilter = null,
        bool $returnKeys = false
    ): array {

        // use given array as keys. Use all keys, if no array given
        // fill the array with the current search type mask
        $searchTypeFilter = array_fill_keys(
            $searchTypeFilter ?? array_keys( static::SEARCH_TYPES ),
            $this->getSearchType()
        );

        // filter out those entries that do not match the mask
        $searchTypeFilter = array_filter(
            $searchTypeFilter,
            static function (
                $filterType,
                $selfType
            ) {

                return ( $filterType & $selfType );
            },
            ARRAY_FILTER_USE_BOTH
        );

        krsort( $searchTypeFilter, SORT_NUMERIC );

        // return keys as an array
        if ( $returnKeys )
        {
            return array_keys( $searchTypeFilter );
        }

        // translate the entries (overwriting the mask)
        array_walk(
            $searchTypeFilter,
            static function (
                &$filterName,
                $filterType
            ) {

                $filterName = static::translateSearchType( $filterType );
            }
        );

        return $searchTypeFilter;
    }


    /**
     * @return string|null
     */
    public function getSearchTypeName(): ?string
    {

        return static::translateSearchType( $this->getSearchType(), false );
    }


    /**
     * @param  bool|null  $mode
     *
     * @return string|null
     */
    public function getSearchTypeNames( ?bool $mode = null ): ?string
    {

        return static::translateSearchType( $this->getSearchType(), $mode );
    }


    /**
     * @param  bool|null  $mode
     *
     * @return array|null
     */
    public function getSearchTypeNamesAsArray( ?bool $mode = null ): ?array
    {

        return static::translateSearchType( $this->getSearchType(), $mode, true );
    }


    /**
     * shall only be used by QueryInterface and SubQuery implementations
     *
     * @param  int|string|null  $searchType
     *
     * @return \WPGeonames\Query\Query|\WPGeonames\Query\QueryTrait
     * @throws \ErrorException
     */
    protected function _setSearchType( $searchType ): Query
    {

        $this->searchType = is_numeric( $searchType )
            ? (int) $searchType
            : \WPGeonames\Query::translateSearchType( $searchType );

        if ( $this->searchType === false )
        {
            throw new ErrorException(
                sprintf( 'Parameter 1 to %s must be an integer or valid search type name!', __METHOD__ )
            );
        }

        if ( $this->searchType === null )
        {
            throw new ErrorException(
                sprintf( 'Parameter 1 to %s must be a valid search type!', __METHOD__ )
            );
        }

        return $this;
    }


    /**
     * @param  int|string|null  $searchType
     * @param  bool|null        $mode
     *
     * @param  bool             $returnAsArray
     *
     * @return false|int|string|string[]|null
     */
    public static function translateSearchType(
        $searchType,
        ?bool $mode = null,
        bool $returnAsArray = false
    ) {

        if ( $searchType === null )
        {
            return null;
        }

        if ( is_string( $searchType ) )
        {
            $types       = array_flip( self::SEARCH_TYPES );
            $result      = 0;
            $resultArray = [];
            $searchTypes = array_filter( explode( ',', $searchType ) );

            if ( $mode === false && count( $searchTypes ) !== 1 )
            {
                return null;
            }

            krsort( $searchTypes, SORT_NUMERIC );

            foreach ( $searchTypes as $search )
            {
                $search = trim( $search );

                if ( $mode === true && ! array_key_exists( $search, $types ) )
                {
                    return null;
                }

                $result        += $types[ $search ] ?? 0;
                $resultArray[] = $types[ $search ] ?? 0;
            }

            if ( $returnAsArray )
            {
                return $resultArray;
            }

            return $result
                ?: null;
        }

        if ( is_numeric( $searchType ) )
        {
            $result = [];

            if ( $mode === false )
            {
                return self::SEARCH_TYPES[ $searchType ] ?? null;
            }

            foreach ( self::SEARCH_TYPES as $type => $name )
            {
                if ( $type & $searchType )
                {
                    $result[]   = $name;
                    $searchType -= $type;
                }
            }

            if ( empty( $result ) || ( $mode === true && $searchType > 0 ) )
            {
                return null;
            }

            krsort( $result, SORT_NUMERIC );

            if ( $returnAsArray )
            {
                return $result;
            }

            return implode( ',', $result );

        }

        return false;
    }

}