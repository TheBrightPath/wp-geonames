<?php

namespace WPGeonames\Entities;

use DateTime;
use DateTimeZone;
use WPGeonames\Helpers\FlexibleObjectInterface;
use WPGeonames\Helpers\FlexibleObjectTrait;

/**
 * class Timezone
 *
 * @property string      $countryCode
 * @property string      $timeZoneId
 * @property string      $caption
 * @property string      $city
 * @property string|null $php
 * @property int|null    $offsetJan
 * @property int|null    $offsetJul
 * @property int|null    $offsetRaw
 */
class Timezone
    extends
    DateTimeZone
    implements
    FlexibleObjectInterface
{

    use FlexibleObjectTrait;

// protected properties

    protected static $_aliases
        = [
            'time_zone_id' => 'name',
            'timezone'     => 'name',
            'timeZoneId'   => 'name',
            'timezoneId'   => 'name',
            'tz'           => 'name',
            'country_code' => 'countryCode',
        ];


    /**
     * Timezone constructor.
     *
     * @param         $timezone
     * @param  array  $defaultsAreIgnored
     *
     * @noinspection PhpUnusedParameterInspection*/
    public function __construct(
        $timezone,
        $defaultsAreIgnored = []
    ) {

        if ( $timezone instanceof DateTimeZone )
        {
            $timezone = $timezone->getName();
        }

        if ( is_array( $timezone ) )
        {
            $timezone = $timezone['timezone']
                ?? $timezone['timezoneId']
                ?? $timezone['timeZoneId']
                ?? $timezone['time_zone_id']
                ?? $timezone['tz'];
        }

        parent::__construct( $timezone );
    }


    /**
     * @return string
     */
    public function getCountryCode(): string
    {

        return $this->getLocation()['country_code'];
    }


    /**
     * @param  int|null  $year
     *
     * @return int|null
     */
    public function getOffsetJan( ?int $year = null ): ?int
    {

        return $this->getOffset(
            date_create()
                ->setDate( $year ?? (int) date_create()->format( 'Y' ), 1, 1 )
                ->setTime( 14, 0, 0 )
        );
    }


    /**
     * @param  int|null  $year
     *
     * @return int|null
     */
    public function getOffsetJul( ?int $year = null ): ?int
    {

        return $this->getOffset(
            date_create()
                ->setDate( $year ?? (int) date_create()->format( 'Y' ), 7, 1 )
                ->setTime( 14, 0, 0 )
        );
    }


    public function __toString()
    {

        return $this->getName();
    }


    /**
     * @param $dateTimeString
     *
     * @return \DateTime
     * @throws \Exception
     */
    public function createDateTime( $dateTimeString ): DateTime
    {

        return new DateTime( $dateTimeString, $this );
    }

}
