<?php

namespace WPGeonames\Traits;

use WPGeonames\FlexibleObject;
use WPGeonames\WpDb;

trait FlexibleObjectTrait
{
    // private properties

    /** @var bool|null */
    private $ignoreNonExistingPropertyOnSet = FlexibleObject::IGNORE_NON_EXISTING_PROPERTY_ON_SET_NOT;


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

        $self = $this->cleanInput($values)
                     ->setIgnoreNonExistingPropertyOnSet(
                         FlexibleObject::IGNORE_NON_EXISTING_PROPERTY_ON_SET_REPEATEDLY
                     )
        ;
        $values = wp_parse_args($values, $defaults);
        $values = $this->cleanArray($values);

        array_walk(
            $values,
            static function (
                &$value,
                $property
            ) use
            (
                &
                $self
            )
            {

                // skip empty values
                if ($value === null)
                {
                    return;
                }

                $self->__set($property, $value);
            }
        );

        $this->setIgnoreNonExistingPropertyOnSet(FlexibleObject::IGNORE_NON_EXISTING_PROPERTY_ON_SET_NOT);

    }


    /**
     * @param  bool|null  $ignoreNonExistingPropertyOnSet
     *
     * @return \WPGeonames\FlexibleObject|\WPGeonames\Traits\FlexibleObjectTrait
     */
    public function setIgnoreNonExistingPropertyOnSet(?bool $ignoreNonExistingPropertyOnSet): FlexibleObject
    {

        $this->ignoreNonExistingPropertyOnSet = $ignoreNonExistingPropertyOnSet;

        return $this;
    }


    public function __get($property)
    {

        $getter = 'get' . ucfirst(static::$aliases[$property] ?? $property);

        return $this->$getter();
    }


    public function __set(
        $property,
        $value
    ) {

        $setter = 'set' . ucfirst(static::$aliases[$property] ?? $property);

        if ($this->ignoreNonExistingPropertyOnSet !== false)
        {
            // if it's a one-off setting (true vs null), reset to false
            if ($this->ignoreNonExistingPropertyOnSet === true)
            {
                $this->ignoreNonExistingPropertyOnSet = false;
            }

            if (!method_exists($this, $setter))
            {
                return $this;
            }
        }

        return $this->$setter($value);
    }


    public function __isset($name)
    {

        return property_exists($this, $name) || array_key_exists($name, static::$aliases);
    }


    public function __serialize(): array
    {

        return $this->toArray();

    }


    /**
     * @param  array  $array
     *
     * @return array
     */
    protected function cleanArray(array $array): array
    {

        $array = array_filter(
            $array,
            static function ($item)
            {

                return $item !== null && $item !== '';
            }
        );

        ksort($array);

        unset($array["ignoreNonExistingPropertyOnSet"]);

        return $array;

    }


    public function cleanInput(&$values): FlexibleObject
    {

        if (is_object($values))
        {

            if (method_exists($values, '__serialize'))
            {
                $values = $values->__serialize($values);
            }
            else
            {
                $values = get_object_vars($values);
            }

        }

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this;

    }


    public function serialize(): string
    {

        return serialize($this->toArray());

    }


    /**
     * @return array
     */
    public function toArray(): array
    {

        return $this->cleanArray(get_object_vars($this));

    }


    public static function parseArray(
        &$array,
        $key = '',
        $prefix = ''
    ) {

        return WpDb::formatOutput($array, static::class, $key, $prefix);

    }

}
