<?php

namespace WPGeonames\Query;

interface ChildQueryInterface
    extends
    QueryableInterface
{

    public function __construct( Query $parent );


    /**
     * @return Query
     */
    public function getParent(): Query;


    /**
     * @param  Query  $parent
     *
     * @return \WPGeonames\Query\ChildQueryTrait
     */
    public function setParent( Query $parent ): ChildQueryTrait;

}