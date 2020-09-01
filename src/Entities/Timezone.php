<?php

namespace WPGeonames\Entities;

use WPGeonames\Core;
use WPGeonames\FlexibleObject;

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
    extends FlexibleObject
{

    // protected properties
    /** @var \WPGeonames\Entities\Timezone[] */
    protected static $timezones = [];
    protected static $aliases
                                = [
            'time_zone_id' => 'timeZoneId',
            'country_code' => 'countryCode',
        ];

    /** @var string */
    protected $countryCode;
    /** @var string */
    protected $timeZoneId;
    /** @var string */
    protected $caption;
    /** @var string */
    protected $city;
    /** @var string */
    protected $php;
    /** @var int|null */
    protected $offsetJan;
    /** @var int|null */
    protected $offsetJul;
    /** @var int|null */
    protected $offsetRaw;


    /**
     * @return string
     */
    public function getCaption(): string
    {

        return $this->caption;
    }


    /**
     * @param  string  $caption
     *
     * @return Timezone
     */
    public function setCaption(string $caption): Timezone
    {

        $this->caption = $caption;

        return $this;
    }


    /**
     * @return string
     */
    public function getCity(): string
    {

        return $this->city;
    }


    /**
     * @param  string  $city
     *
     * @return Timezone
     */
    public function setCity(string $city): Timezone
    {

        $this->city = $city;

        return $this;
    }


    /**
     * @return string
     */
    public function getCountryCode(): string
    {

        return $this->countryCode;
    }


    /**
     * @param  string  $countryCode
     *
     * @return Timezone
     */
    public function setCountryCode(string $countryCode): Timezone
    {

        $this->countryCode = $countryCode;

        return $this;
    }


    /**
     * @return int|null
     */
    public function getOffsetJan(): ?int
    {

        return $this->offsetJan;
    }


    /**
     * @param  int|null  $offsetJan
     *
     * @return Timezone
     */
    public function setOffsetJan(?int $offsetJan): Timezone
    {

        $this->offsetJan = $offsetJan;

        return $this;
    }


    /**
     * @return int|null
     */
    public function getOffsetJul(): ?int
    {

        return $this->offsetJul;
    }


    /**
     * @param  int|null  $offsetJul
     *
     * @return Timezone
     */
    public function setOffsetJul(?int $offsetJul): Timezone
    {

        $this->offsetJul = $offsetJul;

        return $this;
    }


    /**
     * @return int|null
     */
    public function getOffsetRaw(): ?int
    {

        return $this->offsetRaw;
    }


    /**
     * @param  int|null  $offsetRaw
     *
     * @return Timezone
     */
    public function setOffsetRaw(?int $offsetRaw): Timezone
    {

        $this->offsetRaw = $offsetRaw;

        return $this;
    }


    /**
     * @return string|null
     */
    public function getPhp(): ?string
    {

        return $this->php;
    }


    /**
     * @param  string|null  $php
     *
     * @return Timezone
     */
    public function setPhp(?string $php): Timezone
    {

        $this->php = $php;

        return $this;
    }


    /**
     * @return string
     */
    public function getTimeZoneId(): string
    {

        return $this->timeZoneId;
    }


    /**
     * @param  string  $timeZoneId
     *
     * @return Timezone
     */
    public function setTimeZoneId(string $timeZoneId): Timezone
    {

        $this->timeZoneId = $timeZoneId;

        return $this;
    }


    public static function load($timezone)
    {

        if (is_array($timezone))
        {
            $timezone = $timezone['time_zone_id'] ?? $timezone[static::$aliases['time_zone_id']];
        }

        if (empty($timezone) || !is_string($timezone))
        {
            throw new \ErrorException('Invalid parameter');
        }

        if (array_key_exists($timezone, static::$timezones))
        {
            return static::$timezones[$timezone];
        }

        $sqlWhere = Core::$wpdb->prepare("time_zone_id = %s", $timezone);

        $table = Core::Factory()
                     ->getTblTimeZones()
        ;
        $sql   = <<<SQL
    SELECT
        *
    FROM 
        $table
    WHERE
        $sqlWhere
    ;
SQL;

        $timezone = Core::$wpdb->get_row($sql);

        if (Core::$wpdb->last_error_no)
        {
            throw new \ErrorException(Core::$wpdb->last_error, Core::$wpdb->last_error_no);
        }

        $timezone = new static($timezone);

        static::$timezones[$timezone->timeZoneId] = $timezone;

        return $timezone;
    }

}