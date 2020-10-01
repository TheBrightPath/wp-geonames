<?php

namespace WPGeonames\Query;

interface ParentQueryInterface
    extends
    Query
{

    public function createSubQuery( ?int $searchType = null ): ChildQueryInterface;

}