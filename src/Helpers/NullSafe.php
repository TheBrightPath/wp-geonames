<?php

namespace WPGeonames\Helpers;

class NullSafe
{

// protected properties

    protected $value;


    /**
     * NullSafe constructor.
     *
     * @param $value
     */
    public function __construct( ?string $value = null )
    {

        $this->value = $value;
    }


    public function __call(
        $name,
        $arguments
    ) {

        return $this;
    }


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

        return $this->value ?? '';
    }

}