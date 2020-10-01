<?php

namespace WPGeonames\Entities;

use WPGeonames\Helpers\FlexibleObjectInterface;
use WPGeonames\Helpers\FlexibleObjectTrait;

class BBox
    implements FlexibleObjectInterface
{
    use FlexibleObjectTrait;

// protected properties

    /** @var float */
    protected $east;

    /** @var float */
    protected $south;

    /** @var float */
    protected $north;

    /** @var float */
    protected $west;

    /** @var int */
    protected $accuracyLevel;


    /**
     * @return int
     */
    public function getAccuracyLevel(): int
    {

        return $this->accuracyLevel;
    }


    /**
     * @param  int  $accuracyLevel
     *
     * @return BBox
     */
    public function setAccuracyLevel( int $accuracyLevel ): BBox
    {

        $this->accuracyLevel = $accuracyLevel;

        return $this;
    }


    /**
     * @return float
     */
    public function getEast(): float
    {

        return $this->east;
    }


    /**
     * @param  float  $east
     *
     * @return BBox
     */
    public function setEast( float $east ): BBox
    {

        $this->east = $east;

        return $this;
    }


    /**
     * @return float
     */
    public function getNorth(): float
    {

        return $this->north;
    }


    /**
     * @param  float  $north
     *
     * @return BBox
     */
    public function setNorth( float $north ): BBox
    {

        $this->north = $north;

        return $this;
    }


    /**
     * @return float
     */
    public function getSouth(): float
    {

        return $this->south;
    }


    /**
     * @param  float  $south
     *
     * @return BBox
     */
    public function setSouth( float $south ): BBox
    {

        $this->south = $south;

        return $this;
    }


    /**
     * @return float
     */
    public function getWest(): float
    {

        return $this->west;
    }


    /**
     * @param  float  $west
     *
     * @return BBox
     */
    public function setWest( float $west ): BBox
    {

        $this->west = $west;

        return $this;
    }


    public function __toString(): string
    {

        return \GuzzleHttp\json_encode( $this->__serialize() );
    }

}
