<?php

namespace WPGeonames;

abstract class FlexibleDbObject
    extends FlexibleObject
{
    public function __construct(
        &$values,
        $defaults = []
    ) {

        if (!is_array($values) && !is_object($values))
        {
            $values = static::loadRecords($values);
        }

        parent::__construct($values, $defaults);
    }


    abstract public static function load($ids);


    abstract protected static function loadRecords($ids);

}