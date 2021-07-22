<?php

namespace WPGeonames\Helpers;

trait FlexibleDbObjectTrait
{

    use FlexibleObjectTrait
    {
        FlexibleObjectTrait::__construct as private _FlexibleObjectTrait__construct;
    }

    use DbObjectTrait;

    /**
     * FlexibleDbObjectTrait constructor.
     *
     * @param         $values
     * @param  array  $defaults
     *
     * @throws \ErrorException
     */
    public function __construct(
        $values,
        $defaults = []
    ) {

        if ( ! is_array( $values ) && ! is_object( $values ) && $values !== null )
        {
            $values = static::loadRecords( $values );

            if ( is_array( $values ) && count( $values === 1 ) )
            {
                $values = reset( $values );
            }
        }

        $this->_FlexibleObjectTrait__construct( $values, $defaults );
    }

}
