<?php

namespace WPGeonames;

/**
 * Class FlexibleObject
 *
 */
class FlexibleObject
{

    // protected properties
    protected static $aliases
        = [
        ];


    /**
     * wpGeonamesClientQuery constructor.
     *
     * @param         $values
     * @param  array  $defaults
     */
    public function __construct(
        &$values,
        $defaults = []
    ) {

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

        $self = $this;
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

                if ($value !== null
                    && (property_exists($self, $property)
                        || array_key_exists($property, static::$aliases)))
                {
                    $self->__set($property, $value);
                }
            }
        );

    }


    public function __get($property)
    {

        $p = static::$aliases[$property]
            ?: null;

        if ($p)
        {
            return $this->$p;
        }

        return $this->$property;

    }


    public function __set(
        $property,
        $value
    ) {

        $setter = 'set' . ucfirst(static::$aliases[$property] ?? $property);

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
    protected function cleanArray($array): array
    {

        $array = array_filter(
            $array,
            static function ($item)
            {

                return $item !== null && $item !== '';
            }
        );

        ksort($array);

        return $array;

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