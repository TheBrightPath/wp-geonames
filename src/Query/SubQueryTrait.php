<?php

namespace WPGeonames\Query;

use ErrorException;

trait SubQueryTrait
{

// protected properties

    /**
     * @var int
     */
    protected $searchType = 0;


    /**
     * SubQueryTrait constructor.
     *
     * @throws \ErrorException
     */
    public function __construct()
    {

        // checking if it is a "clean" search type, and not a search type mask
        if ( ! array_key_exists( $this->searchType, static::SEARCH_TYPES ) )
        {
            throw new ErrorException(
                sprintf(
                    '%s requires the property "searchType" to be an existing entry in %s::SEARCH_TYPES',
                    static::class,
                    static::class
                )
            );
        }

    }


    /**
     * @return int
     */
    public function getSearchType(): int
    {

        return $this->searchType ?? 0;
    }


    /**
     * @param $searchType
     *
     * @return \WPGeonames\Query\Query
     */
    public function setSearchType( $searchType ): Query
    {

        return $this->_setSearchType( $searchType );
    }

}