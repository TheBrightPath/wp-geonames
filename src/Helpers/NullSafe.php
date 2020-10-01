<?php

namespace WPGeonames\Helpers;

class NullSafe
{

    public function __get( $name )
    {

        return $this;
    }


    public function __set(
        $name,
        $value
    ) {

        user_error( sprintf( 'Property "%s" not set. This is a null-like object :-)', $name ), E_USER_WARNING );

        return $this;
    }


    public function __isset( $name )
    {

        return false;
    }


    public function __serialize(): array
    {

        return [];
    }


    public function __toString()
    {

        return '';
    }

}