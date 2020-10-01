<?php

namespace WPGeonames\Helpers;

interface FlexibleObjectInterface
{

// constants
    public const IGNORE_NON_EXISTING_PROPERTY_ON_SET_NOT        = false;
    public const IGNORE_NON_EXISTING_PROPERTY_ON_SET_ONCE       = true;
    public const IGNORE_NON_EXISTING_PROPERTY_ON_SET_REPEATEDLY = null;


    /**
     * wpGeonamesClientQuery constructor.
     *
     * @param         $values
     * @param  array  $defaults
     */
    public function __construct(
        $values,
        $defaults = []
    );


    /**
     * @param  bool|null  $ignoreNonExistingPropertyOnSet
     *
     * @return \WPGeonames\Helpers\FlexibleObjectInterface
     */
    public function setIgnoreNonExistingPropertyOnSet( ?bool $ignoreNonExistingPropertyOnSet
    ): FlexibleObjectInterface;


    public function __get( $property );


    public function __set(
        $property,
        $value
    );


    public function __isset( $name );


    public function __serialize(): array;


    public function cleanInput( &$values ): FlexibleObjectInterface;


    public function loadValues(
        $values,
        $default = null
    ):?int;


    public function serialize(): string;


    /**
     * @return array
     */
    public function toArray(): array;


    public static function parseArray(
        &$array,
        $key = '',
        $prefix = ''
    );

}