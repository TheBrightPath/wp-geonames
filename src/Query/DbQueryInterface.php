<?php

namespace WPGeonames\Query;

use WPGeonames\Helpers\FlexibleDbObjectInterface;

interface DbQueryInterface
    extends
    FlexibleDbObjectInterface,
    ChildQueryInterface,
    SubQueryInterface,
    QueryableInterface
{

}