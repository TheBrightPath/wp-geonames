<?php

namespace WPGeonames\Helpers;

use WPGeonames\Query;

class ArrayComparison
{

//  public properties

    /** @var \WPGeonames\Query\Query */
    public $searchQuery;

    /** @var \WPGeonames\Query\Query */
    public $cacheQuery;

    public $property;


    /**
     * ArrayComparison constructor.
     *
     * @param  \WPGeonames\Query\Query  $searchQuery
     * @param  \WPGeonames\Query\Query  $cacheQuery
     * @param  string|null              $property
     */
    public function __construct(
        Query\Query $searchQuery,
        Query\Query $cacheQuery,
        string $property = null
    ) {

        $this->property    = $property;
        $this->searchQuery = $searchQuery;
        $this->cacheQuery  = $cacheQuery;
    }


    public function compare( bool $strict = false ): ?bool
    {

        if ( $this->property === null )
        {
            return null;
        }

        // get the value from the cached query
        $cacheQuery = $this->cacheQuery->{$this->property};

        // if cacheQuery has no restriction and we don't need strict comparison, we can assume ok
        if ( ! $strict && $cacheQuery === null )
        {
            return true;
        }

        // get the value from the search query
        $searchQuery = $this->searchQuery->{$this->property};

        // if they match, we're sorted
        if ( $searchQuery === $cacheQuery )
        {
            return true;
        }

        // if we need to be strict, we're screwed
        if ( $strict )
        {
            return false;
        }

        // otherwise we're good as long as all the entries from the search are included in the cache
        return empty( array_diff( $searchQuery, $cacheQuery ) );

    }

}