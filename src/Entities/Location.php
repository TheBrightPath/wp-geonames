<?php

namespace WPGeonames\Entities;

use ErrorException;
use WPGeonames\Core;
use WPGeonames\FlexibleDbObject;
use WPGeonames\FlexibleObject;

/**
 * Class Location
 *
 * @property int                                        $geonameId
 * @property string                                     $name
 * @property string                                     $asciiName
 * @property string                                     $featureClass
 * @property string                                     $featureCode
 * @property string                                     $continentCode
 * @property string                                     $country
 * @property string|null                                $adminCode1
 * @property int|null                                   $adminId1
 * @property string|null                                $adminCode2
 * @property int|null                                   $adminId2
 * @property string|null                                $adminCode3
 * @property int|null                                   $adminId3
 * @property string|null                                $adminCode4
 * @property int|null                                   $adminId4
 * @property float                                      $latitude
 * @property float                                      $longitude
 * @property string                                     $alternateNames
 * @property int                                        $population
 * @property int                                        $elevation
 * @property \WPGeonames\Entities\Location[]|int[]|null $children
 */
class Location
    extends FlexibleDbObject
{

    // protected properties
    protected static $aliases
                               = [
            'geoname_id'      => 'geonameId',
            'toponymName'     => 'name',
            'ascii_name'      => 'asciiName',
            'alternate_names' => 'alternateNames',
            'feature_class'   => 'featureClass',
            'fcl'             => 'featureClass',
            'feature_code'    => 'featureCode',
            'fcode'           => 'featureCode',
            'countryCode'     => 'country',
            'country_code'    => 'country',
            'country_id'      => 'country',
            'continent'       => 'continentCode',
            'admin1_code'     => 'adminCode1',
            'admin2_code'     => 'adminCode2',
            'admin3_code'     => 'adminCode3',
            'admin4_code'     => 'adminCode4',
            'lng'             => 'longitude',
            'lat'             => 'latitude',
        ];
    protected        $geonameId;
    protected        $name;
    protected        $asciiName;
    protected        $featureClass;
    protected        $featureCode;
    protected        $country;
    protected        $adminCode1;
    protected        $adminId1;
    protected        $adminCode2;
    protected        $adminId2;
    protected        $adminCode3;
    protected        $adminId3;
    protected        $adminCode4;
    protected        $adminId4;
    protected        $longitude;
    protected        $latitude;
    protected        $alternateNames;
    protected        $countryId;
    protected        $population;
    protected        $bbox;
    protected        $elevation;
    protected        $timezone;
    protected        $children = [];
    /** @var string */
    protected $continentCode;
    /** @var float|null */
    protected $score;


    /**
     * @param  int|string  $x
     * @param  string      $format
     *
     * @return string|array|null
     */
    protected function getAdminCode(
        $x,
        $format
    ) {

        if (is_numeric($x))
        {
            $x = "adminCode$x";
        }

        if ($this->$x === null)
        {
            return null;
        }

        return $format
            ? ($this->$x)[$format]
            : $this->$x;
    }


    /**
     * @param  string  $format
     *
     * @return string|array|null
     */
    public function getAdminCode1($format = 'ISO3166_2')
    {

        return $this->getAdminCode(1, $format);
    }


    /**
     * @param  string|array  $adminCode
     *
     * @return Location
     */
    public function setAdminCode1($adminCode): Location
    {

        return $this->setAdminCode(1, $adminCode);
    }


    /**
     * @param  string  $format
     *
     * @return string|array|null
     */
    public function getAdminCode2($format = 'ISO3166_2')
    {

        return $this->getAdminCode(2, $format);
    }


    /**
     * @param  string|array  $adminCode
     *
     * @return Location
     */
    public function setAdminCode2($adminCode): Location
    {

        return $this->setAdminCode(2, $adminCode);
    }


    /**
     * @param  string  $format
     *
     * @return string|array|null
     */
    public function getAdminCode3($format = 'ISO3166_2')
    {

        return $this->getAdminCode(3, $format);
    }


    /**
     * @param  string|array  $adminCode
     *
     * @return Location
     */
    public function setAdminCode3($adminCode): Location
    {

        return $this->setAdminCode(3, $adminCode);
    }


    /**
     * @param  string  $format
     *
     * @return string|array|null
     */
    public function getAdminCode4($format = 'ISO3166_2')
    {

        return $this->getAdminCode(4, $format);
    }


    /**
     * @param  string|array  $adminCode
     *
     * @return Location
     */
    public function setAdminCode4($adminCode): Location
    {

        return $this->setAdminCode(4, $adminCode);
    }


    /**
     * @return int|null
     */
    public function getAdminId1(): ?int
    {

        return $this->adminId1;
    }


    /**
     * @param  int|null  $adminId1
     *
     * @return Location
     */
    public function setAdminId1(?int $adminId1): Location
    {

        $this->adminId1 = $adminId1;

        return $this;
    }


    /**
     * @return int|null
     */
    public function getAdminId2(): ?int
    {

        return $this->adminId2;
    }


    /**
     * @param  int|null  $adminId2
     *
     * @return Location
     */
    public function setAdminId2(?int $adminId2): Location
    {

        $this->adminId2 = $adminId2;

        return $this;
    }


    /**
     * @return int|null
     */
    public function getAdminId3(): ?int
    {

        return $this->adminId3;
    }


    /**
     * @param  int|null  $adminId3
     *
     * @return Location
     */
    public function setAdminId3(?int $adminId3): Location
    {

        $this->adminId3 = $adminId3;

        return $this;
    }


    /**
     * @return int|null
     */
    public function getAdminId4(): ?int
    {

        return $this->adminId4;
    }


    /**
     * @param  int|null  $adminId4
     *
     * @return Location
     */
    public function setAdminId4(?int $adminId4): Location
    {

        $this->adminId4 = $adminId4;

        return $this;
    }


    /**
     * @return mixed
     */
    public function getAlternateNames($lang = null)
    {

        if ($lang === null)
        {
            return $this->alternateNames;
        }

        if (strtolower($lang) === 'json')
        {
            return \GuzzleHttp\json_encode($this->alternateNames);
        }

        return $this->alternateNames[$lang] ?? null;
    }


    /**
     * @param  object|array|string|null  $alternateNames
     *
     * @return Location
     */
    public function setAlternateNames($alternateNames): Location
    {

        if (is_string($alternateNames))
        {
            $alternateNames = \GuzzleHttp\json_decode($alternateNames);
        }

        if (is_array($alternateNames))
        {

            if (key($alternateNames) === 0)
            {
                $new = [];

                foreach ($alternateNames as $alternateName)
                {
                    if (isset($alternateName->lang))
                    {
                        $new[$alternateName->lang] = $alternateName->name;
                    }
                }

                $alternateNames = $new;
                unset($new);
            }

            $alternateNames = (object)$alternateNames;
        }

        $this->alternateNames = $alternateNames;

        return $this;
    }


    /**
     * @return string
     */
    public function getAsciiName(): string
    {

        return $this->asciiName;
    }


    /**
     * @param  string  $asciiName
     *
     * @return Location
     */
    public function setAsciiName(string $asciiName): Location
    {

        $this->asciiName = $asciiName;

        return $this;
    }


    /**
     * @return mixed
     */
    public function getBbox($property = null)
    {

        if ($this->bbox === null)
        {
            return null;
        }

        if (!$this->bbox instanceof BBox)
        {
            $this->bbox = new BBox($this->bbox);
        }

        if ($property === null)
        {
            return $this->bbox;
        }

        if (strtolower($property) === 'json')
        {
            return $this->bbox->__toString();
        }

        return $this->bbox->$property;
    }


    /**
     * @param  mixed  $bbox
     *
     * @return Location
     */
    public function setBbox($bbox)
    {

        $this->bbox = $bbox;

        return $this;
    }


    /**
     * @return int[]|\WPGeonames\Entities\Location[]|array|string|null
     */
    public function getChildren(
        $hierarchy = 'adm',
        ?bool $returnAsLocations = true
    ) {

        static $hierarchies = ['adm', 'tourism', 'geography', 'dependency'];

        if ($hierarchy === null && $returnAsLocations === null)
        {
            return $this->children;
        }

        if ($hierarchy === 'json')
        {
            return empty($this->children)
                ? null
                : \GuzzleHttp\json_encode($this->getChildren(false, false));
        }

        if (!array_key_exists($hierarchy, $hierarchies))
        {
            return null;
        }

        $keys = $hierarchy
            ? [$hierarchy]
            : $hierarchies;

        $result = [];
        $save   = false;

        foreach ($keys as $key)
        {
            if ($hierarchy !== false && !array_key_exists($hierarchy, $this->children))
            {
                $g      = Core::getGeoNameClient();
                $params = [
                    'geonameId' => $this->getGeonameId(),
                    'style'     => 'full',
                    'maxRows'   => 1000,
                ];

                if ($hierarchy !== 'adm')
                {
                    $params['hierarchy'] = $hierarchy;
                }

                $this->children[$key] = $g->children($params);

                array_walk(
                    $this->children[$key],
                    static function (&$child)
                    {

                        $child = new Location($child);
                        $child->save();
                    }
                );

                $save = true;
            }

            foreach ($this->children[$key] as $value)
            {
                switch (true)
                {
                case $returnAsLocations === true:
                    $result[$key][] = $value instanceof Location
                        ? $value
                        : new Location($value);
                    break;

                case $returnAsLocations === false:
                    $result[$key][] = $value instanceof Location
                        ? $value->getGeonameId()
                        : $value;
                    break;

                case $returnAsLocations === null:
                    $result[$key][] = $value;
                }
            }
        }

        if ($save)
        {
            $this->save();
        }

        return $hierarchy === null
            ? $result
            : $result[$hierarchy];
    }


    /**
     * @param  int[]|\WPGeonames\Entities\Location[]|null  $children
     *
     * @return Location
     */
    public function setChildren($children)
    {

        $this->children = $children;

        return $this;
    }


    /**
     * @return string
     */
    public function getContinentCode(): string
    {

        return $this->continentCode;
    }


    /**
     * @param  string  $continentCode
     *
     * @return Location
     */
    public function setContinentCode(string $continentCode): Location
    {

        $this->continentCode = $continentCode;

        return $this;
    }


    /**
     * @return Country
     */
    public function getCountry(): Country
    {

        if ($this->country instanceof Country)
        {
            return $this->country;
        }

        $this->country = Country::load($this->country);

        return $this->country;
    }


    /**
     * @return string
     */
    public function getCountryCode($format = 'iso2'): string
    {

        return $this->getCountry()->$format;
    }


    /**
     * @return int
     */
    public function getCountryId(): int
    {

        return $this->getCountry()->geonameId;
    }


    /**
     * @param  null  $countryId
     *
     * @return Location
     */
    public function setCountryId($countryId): Location
    {

        $this->country = $countryId;

        return $this;
    }


    /**
     * @return mixed
     */
    public function getElevation()
    {

        return $this->elevation;
    }


    /**
     * @param  mixed  $elevation
     *
     * @return Location
     */
    public function setElevation($elevation)
    {

        $this->elevation = $elevation;

        return $this;
    }


    /**
     * @return string
     */
    public function getFeatureClass(): string
    {

        return $this->featureClass;
    }


    /**
     * @param  null  $featureClass
     *
     * @return Location
     */
    public function setFeatureClass($featureClass): Location
    {

        $this->featureClass = $featureClass;

        return $this;
    }


    /**
     * @return string
     */
    public function getFeatureCode(): string
    {

        return $this->featureCode;
    }


    /**
     * @param  null  $featureCode
     *
     * @return Location
     */
    public function setFeatureCode($featureCode): Location
    {

        $this->featureCode = $featureCode;

        return $this;
    }


    /**
     * @return int
     */
    public function getGeonameId(): int
    {

        return $this->geonameId;
    }


    /**
     * @param  null  $geonameId
     *
     * @return Location
     */
    public function setGeonameId($geonameId): Location
    {

        $this->geonameId = $geonameId;

        return $this;
    }


    /**
     * @return float
     */
    public function getLatitude(): float
    {

        return $this->latitude;
    }


    /**
     * @param  null  $latitude
     *
     * @return Location
     */
    public function setLatitude($latitude): Location
    {

        $this->latitude = $latitude;

        return $this;
    }


    /**
     * @return float
     */
    public function getLongitude(): float
    {

        return $this->longitude;
    }


    /**
     * @param  null  $longitude
     *
     * @return Location
     */
    public function setLongitude($longitude): Location
    {

        $this->longitude = $longitude;

        return $this;
    }


    /**
     * @return string
     */
    public function getName(): string
    {

        return $this->name;
    }


    /**
     * @param  null  $name
     *
     * @return Location
     */
    public function setName($name): Location
    {

        $this->name = $name;

        return $this;
    }


    /**
     * @return int
     */
    public function getPopulation(): int
    {

        return $this->population;
    }


    /**
     * @param  null  $population
     *
     * @return Location
     */
    public function setPopulation($population): Location
    {

        $this->population = $population;

        return $this;
    }


    /**
     * @return float|null
     */
    public function getScore(): ?float
    {

        return $this->score;
    }


    /**
     * @param  float|null  $score
     *
     * @return Location
     */
    public function setScore(?float $score): Location
    {

        $this->score = $score;

        return $this;
    }


    /**
     * @return mixed
     */
    public function getTimezone()
    {

        if ($this->timezone instanceof Timezone)
        {
            return $this->timezone;
        }

        return $this->timezone = Timezone::load($this->timezone);
    }


    /**
     * @param  mixed  $timezone
     *
     * @return Location
     */
    public function setTimezone($timezone)
    {

        switch (true)
        {
        case $timezone instanceof Timezone:
            break;
        case is_array($timezone):
            $timezone = $timezone['timeZoneId'];
            break;
        case is_object($timezone):
            $timezone = $timezone->timeZoneId;
            break;
        }

        $this->timezone = $timezone;

        return $this;
    }


    /**
     * @param $x
     * @param $adminCode
     *
     * @return Location
     */
    protected function setAdminCode(
        $x,
        $adminCode
    ): Location {

        if (is_numeric($x))
        {
            $x = "adminCode$x";
        }

        if (!is_array($adminCode))
        {
            $adminCode = [
                'ISO3166_2' => $adminCode,
            ];
        }

        $this->$x = $adminCode;

        return $this;
    }


    public function setAstergdem($elevation): Location
    {

        $this->elevation = $elevation;

        return $this;
    }


    /**
     * @param  null  $countryCode
     *
     * @return Location
     */
    public function setCountryCode($countryCode): Location
    {

        $this->country = $countryCode;

        return $this;
    }


    public function setSrtm3($elevation): Location
    {

        $this->elevation = $elevation;

        return $this;
    }


    public function cleanInput(&$values): FlexibleObject
    {

        parent::cleanInput($values);

        if (array_key_exists('toponymName', $values))
        {
            unset($values['name']);
        }

        return $this;
    }


    public function save()
    {

        if (false === Core::$wpdb->replace(
                Core::Factory()
                    ->getTblCacheLocations(),
                [
                    'geoname_id'      => $this->geonameId,
                    'name'            => $this->name,
                    'ascii_name'      => $this->asciiName,
                    'alternate_names' => $this->getAlternateNames('json'),
                    'feature_class'   => $this->featureClass,
                    'feature_code'    => $this->featureCode,
                    'continent'       => $this->continent,
                    'country_code'    => $this->getCountry()->iso2,
                    'country_id'      => $this->getCountry()->geonameId,
                    'latitude'        => $this->latitude,
                    'longitude'       => $this->longitude,
                    'population'      => $this->population,
                    'elevation'       => $this->elevation,
                    'admin1_code'     => $this->getAdminCode1(),
                    'admin1_id'       => $this->getAdminId1(),
                    'admin2_code'     => $this->getAdminCode2(),
                    'admin2_id'       => $this->getAdminId2(),
                    'admin3_code'     => $this->getAdminCode3(),
                    'admin3_id'       => $this->getAdminId3(),
                    'admin4_code'     => $this->getAdminCode4(),
                    'admin4_id'       => $this->getAdminId4(),
                    'timezone'        => $this->getTimezone()->timeZoneId,
                    'bbox'            => $this->getBbox('json'),
                    'children'        => $this->getChildren('json'),
                ],
                [
                    '%d', // geoname_id
                    '%s', // name
                    '%s', // ascii_name
                    '%s', // alternate_names
                    '%s', // feature_class
                    '%s', // feature_code
                    '%s', // continent
                    '%s', // country_code
                    '%d', // country_id
                    '%f', // latitude
                    '%f', // longitude
                    '%d', // population
                    '%d', // elevation
                    '%s', // admin1_code
                    '%d', // admin1_id
                    '%s', // admin2_code
                    '%d', // admin2_id
                    '%s', // admin3_code
                    '%d', // admin3_id
                    '%s', // admin4_code
                    '%d', // admin4_id
                    '%s', // timezone
                    '%s', // bbox
                    '%s', // children
                ]
            ))
        {
            throw new ErrorException(Core::$wpdb->last_error);
        }
    }


    public static function load($ids)
    {

        $locations = static::loadRecords($ids);

        array_walk(
            $locations,
            static function (&$item)
            {

                $item = new static($item);
            }
        );

        return is_array($ids)
            ? $locations
            : reset($locations);
    }


    protected static function loadRecords($ids)
    {

        if (false === (is_array($ids)
                ? array_reduce(
                    $ids,
                    static function (
                        $carry,
                        $item
                    )
                    {

                        return $carry && is_numeric($item);
                    },
                    true
                )
                : is_numeric($ids)))
        {
            throw new \ErrorException('Supplied id(s) are not numeric');
        }

        $tblCacheLocations = Core::Factory()
                                 ->getTblCacheLocations()
        ;
        $sqlWhere          = sprintf(
            "geoname_id %s",
            is_array($ids)
                ? sprintf('IN (%s)', implode(',', $ids))
                : "= $ids"
        );

        $sql = <<<SQL
    SELECT
        *
    FROM
        $tblCacheLocations
    WHERE
        $sqlWhere
    ;
SQL;

        $locations = Core::$wpdb->get_results($sql);

        if (Core::$wpdb->last_error_no)
        {
            throw new \ErrorException(Core::$wpdb->last_error);
        }

        return $locations;
    }


    public static function parseArray(
        &$array,
        $key = 'geoname_id',
        $prefix = '_'
    ) {

        return parent::parseArray($array, $key, $prefix);

    }

}
