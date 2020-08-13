<?php

namespace WPGeonames;

/**
 * Class FlexibleObject
 *
 */
class FlexibleObject
{
    // constants
    public const IGNORE_NON_EXISTING_PROPERTY_ON_SET_NOT        = false;
    public const IGNORE_NON_EXISTING_PROPERTY_ON_SET_ONCE       = true;
    public const IGNORE_NON_EXISTING_PROPERTY_ON_SET_REPEATEDLY = null;

    // protected properties
    protected static $aliases
        = [
        ];

    // private properties

    /** @var bool|null */
    private $ignoreNonExistingPropertyOnSet = self::IGNORE_NON_EXISTING_PROPERTY_ON_SET_NOT;


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

        $self   = $this->setIgnoreNonExistingPropertyOnSet(self::IGNORE_NON_EXISTING_PROPERTY_ON_SET_REPEATEDLY);
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

        $this->setIgnoreNonExistingPropertyOnSet(self::IGNORE_NON_EXISTING_PROPERTY_ON_SET_NOT);

    }


    /**
     * @param  bool|null  $ignoreNonExistingPropertyOnSet
     *
     * @return FlexibleObject
     */
    public function setIgnoreNonExistingPropertyOnSet(?bool $ignoreNonExistingPropertyOnSet): FlexibleObject
    {

        $this->ignoreNonExistingPropertyOnSet = $ignoreNonExistingPropertyOnSet;

        return $this;
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