<?php

namespace WPGeonames\Helpers;

use Throwable;
use WPGeonames\WpDb;

trait FlexibleObjectTrait
{

// protected properties

    /** @var bool */
    protected $_ignoreEmptyPropertyOnSet = true;

    /** @var bool */
    protected $_ignoreNullPropertyOnSet = true;

    /** @var bool */
    protected $_setNullOnEmptyPropertyOnSet = true;

// private properties

    /** @var bool|null */
    private $_ignoreNonExistingPropertyOnSet = FlexibleObjectInterface::IGNORE_NON_EXISTING_PROPERTY_ON_SET_NOT;


    /**
     * wpGeonamesClientQuery constructor.
     *
     * @param         $values
     * @param  array  $defaults
     */
    public function __construct(
        $values,
        $defaults = []
    ) {

        $this->loadValues( $values, $defaults );
    }


    /**
     * @param             $property
     * @param             $value
     * @param  bool|null  $propertyMissing
     *
     * @return \WPGeonames\Helpers\FlexibleObjectInterface|\WPGeonames\Helpers\FlexibleObjectTrait
     * @noinspection MagicMethodsValidityInspection
     */
    protected function ___set(
        &$property,
        &$value,
        ?bool &$propertyMissing = null
    ) {

        $setter = 'set' . ucfirst( static::$_aliases[ $property ] ?? $property );

        if ( $this->_ignoreNonExistingPropertyOnSet !== false )
        {
            // if it's a one-off setting (true vs null), reset to false
            if ( $this->_ignoreNonExistingPropertyOnSet === true )
            {
                $this->_ignoreNonExistingPropertyOnSet = false;
            }

            if ( $propertyMissing = ! method_exists( $this, $setter ) )
            {
                return $this;
            }
        }

        return $this->$setter( $value );
    }


    public function __get( $property )
    {

        $getter = 'get' . ucfirst( static::$_aliases[ $property ] ?? $property );

        try
        {
            return $this->$getter();
        }
        catch ( Throwable $e )
        {
            return $this->$property;
        }
    }


    public function __set(
        $property,
        $value
    ) {

        return $this->___set( $property, $value );
    }


    public function __isset( $name )
    {

        return
            property_exists( $this, $name )
            || array_key_exists( $name, static::$_aliases )
            || method_exists( $this, 'get' . ucfirst( static::$_aliases[ $name ] ?? $name ) );
    }


    public function __serialize(): array
    {

        return $this->toArray();

    }


    public function __toString()
    {

        return (string) print_r( $this->toArray(), true );
    }


    /**
     * @param  bool|null  $ignoreNonExistingPropertyOnSet
     *
     * @return \WPGeonames\Helpers\FlexibleObjectInterface|\WPGeonames\Helpers\FlexibleObjectTrait
     */
    public function setIgnoreNonExistingPropertyOnSet( ?bool $ignoreNonExistingPropertyOnSet ): FlexibleObjectInterface
    {

        $this->_ignoreNonExistingPropertyOnSet = $ignoreNonExistingPropertyOnSet;

        return $this;
    }


    /**
     * @param  array|null  $array  $array
     *
     * @return array
     */
    public function cleanArray( ?array $array = null ): array
    {

        if ( $array === null )
        {
            return [];
        }

        $self  = $this;
        $array = array_filter(
            $array,
            static function (
                &$item,
                $key
            ) use
            (
                $self
            )
            {

                if ( $self->_setNullOnEmptyPropertyOnSet && empty( $item ) )
                {
                    $item = null;
                }

                return ( $item !== null || $self->_ignoreNullPropertyOnSet === false )
                    && ( $item !== '' || $self->_ignoreEmptyPropertyOnSet === false )
                    && ( ! is_string( $key ) || $key[0] !== '_' );
            },
            ARRAY_FILTER_USE_BOTH
        );

        ksort( $array, SORT_NATURAL | SORT_FLAG_CASE );

        return $array;

    }


    /**
     * @param $values
     *
     * @return $this|\WPGeonames\Helpers\FlexibleObjectInterface
     */
    public function cleanInput( &$values ): FlexibleObjectInterface
    {

        if ( is_array( $values ) && key( $values ) === 0 && count( $values ) === 1 )
        {
            $values = $values[0];
        }

        if ( is_object( $values ) )
        {

            if ( method_exists( $values, '__serialize' ) )
            {
                $values = $values->__serialize( $values );
            }
            else
            {
                $values = get_object_vars( $values );
            }

        }

        return $this;

    }


    /**
     * wpGeonamesClientQuery constructor.
     *
     * @param             $values
     * @param  array      $defaults
     *
     * @param  bool|null  $ignoreNonExistingProperties
     *
     * @return int|null
     */
    public function loadValues(
        $values,
        $defaults = [],
        ?bool $ignoreNonExistingProperties = true
    ): ?int {

        $missedValues = 0;

        $this->cleanInput( $values );

        $values = wp_parse_args( $values, $defaults );
        $values = $this->cleanArray( $values );
        $self   = $this;

        if ( empty( $values ) )
        {
            return null;
        }

        if ( $ignoreNonExistingProperties !== null )
        {
            $_ignoreNonExistingProperties = $this->_ignoreNonExistingPropertyOnSet;

            $this->setIgnoreNonExistingPropertyOnSet(
                $ignoreNonExistingProperties
                    ? FlexibleObjectInterface::IGNORE_NON_EXISTING_PROPERTY_ON_SET_REPEATEDLY
                    : FlexibleObjectInterface::IGNORE_NON_EXISTING_PROPERTY_ON_SET_NOT
            );
        }

        array_walk(
            $values,
            static function (
                &$value,
                $property
            ) use
            (
                &
                $self,
                &
                $missedValues
            )
            {

                $self->___set( $property, $value, $propertyMissing );

                if ( $propertyMissing )
                {
                    ++ $missedValues;
                }
            }
        );

        if ( $ignoreNonExistingProperties !== null )
        {
            $this->setIgnoreNonExistingPropertyOnSet( $_ignoreNonExistingProperties );
        }

        return $missedValues;
    }


    public function serialize(): string
    {

        return serialize( $this->toArray() );

    }


    /**
     * @return array
     */
    public function toArray(): array
    {

        return $this->cleanArray( get_object_vars( $this ) );

    }


    /**
     * @param          $array
     * @param  string  $key
     * @param  string  $prefix
     *
     * @return array|null
     * @throws \ErrorException
     */
    public static function parseArray(
        &$array,
        $key = '',
        $prefix = ''
    ): ?array {

        return WpDb::formatOutput( $array, static::class, $key, $prefix );

    }

}
