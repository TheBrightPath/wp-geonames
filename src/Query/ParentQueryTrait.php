<?php

namespace WPGeonames\Query;

trait ParentQueryTrait
{

    /**
     * @param  int|null  $searchType
     * @param  array     $additionalParams
     *
     * @return \WPGeonames\Query\ChildQueryInterface
     */
    public function createSubQuery(
        ?int $searchType = 0,
        array $additionalParams = []
    ): ChildQueryInterface {

        return new static::$_subQueryType( $this,
            $additionalParams + [
                'searchType' => ( $searchType
                    ?: $this->getSearchType() ),
            ]
        );
    }

}