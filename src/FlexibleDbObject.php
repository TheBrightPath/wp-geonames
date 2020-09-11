<?php

namespace WPGeonames;

use WPGeonames\Traits\FlexibleObjectTrait;

abstract class FlexibleDbObject
    implements FlexibleObject
{
    use FlexibleObjectTrait
    {
        __construct as protected ___construct;
    }

    public function __construct(
        $values,
        $defaults = []
    ) {

        if (!is_array($values) && !is_object($values))
        {
            $values = static::loadRecords($values);
        }

        $this->___construct($values, $defaults);
    }


    abstract public function save();


    abstract public static function load($ids);


    abstract protected static function loadRecords($ids);

}
