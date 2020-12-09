<?php

namespace WPGeonames\Helpers;

interface FlexibleDbObjectInterface
    extends
    FlexibleObjectInterface
{

    public function save();


    public static function load(
        $ids = null,
        ?object $options = null
    );

}
