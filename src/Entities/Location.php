<?php

namespace WPGeonames\Entities;

use DateTimeInterface;
use ErrorException;
use IntlDateFormatter;
use WPGeonames\Core;
use WPGeonames\Helpers\FlexibleDbObjectInterface;
use WPGeonames\Helpers\FlexibleDbObjectTrait;
use WPGeonames\Helpers\FlexibleObjectInterface;
use WPGeonames\Helpers\NullSafe;
use WPGeonames\WpDb;

/**
 * Class Location
 *
 * @property int                                        $geonameId Geoname ID
 * @property string                                     $name      Location Name
 * @property string                                     $asciiName
 * @property string                                     $featureClass
 * @property string                                     $featureCode
 * @property string                                     $continentCode
 * @property \WPGeonames\Entities\Country               $country
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
 * @property string|array|null                          $alternateNames
 * @property int                                        $population
 * @property int                                        $elevation
 * @property \WPGeonames\Entities\Location[]|int[]|null $children
 * @property string                                     countryCode
 */
class Location
    implements
    FlexibleDbObjectInterface
{

    use FlexibleDbObjectTrait
    {
        __construct as private _FlexibleDbObjectTrait__construct;
        cleanInput as protected ___cleanInput;
    }

//  public properties

    /**
     * @var \WPGeonames\Entities\Timezone
     */
    public static $_timezoneClass = Timezone::class;
    /**
     * @var \WPGeonames\Entities\Country
     */
    public static $_countryClass = Country::class;

// protected properties

    /**
     * @var string[]
     */
    protected static $_aliases;

    /**
     * @var integer GeonameId returned from the API
     */
    protected $_idAPI;

    /**
     * @var int
     */
    protected $geonameId;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $asciiName;

    /**
     * @var string
     */
    protected $featureClass;

    /**
     * @var string
     */
    protected $featureCode;

    /**
     * @var string|null enum('af','an','as','eu','na','oc','sa')
     */
    protected $continentCode;

    /**
     * @var \WPGeonames\Entities\Country
     */
    protected $country;

    /**
     * @var string
     */
    protected $adminCode1;

    /**
     * @var int
     */
    protected $adminId1;

    /**
     * @var string
     */
    protected $adminCode2;

    /**
     * @var int
     */
    protected $adminId2;

    /**
     * @var string
     */
    protected $adminCode3;

    /**
     * @var int
     */
    protected $adminId3;

    /**
     * @var string
     */
    protected $adminCode4;

    /**
     * @var int
     */
    protected $adminId4;

    /**
     * @var float
     */
    protected $longitude;

    /**
     * @var float
     */
    protected $latitude;

    /**
     * @var string[]
     */
    protected $alternateNames;

    /**
     * @var int
     */
    protected $countryId;

    /**
     * @var int
     */
    protected $population;

    /**
     * @var \WPGeonames\Entities\BBox
     */
    protected $bbox;

    /**
     * @var int
     */
    protected $elevation;

    /**
     * @var \WPGeonames\Entities\Timezone|null
     */
    protected $timezone;

    /**
     * @var \WPGeonames\Entities\Location[]|null
     */
    protected $children = [];

    /**
     * @var float|null
     */
    protected $score;


    /**
     * Location constructor.
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

        $this->_ignoreNullPropertyOnSet = false;

        if ( static::$_aliases === null )
        {
            static::$_aliases = $this->getAliases();
        }

        $this->_FlexibleDbObjectTrait__construct( $values, $defaults );
    }


    /**
     * @param  int|string  $x
     * @param  string      $format
     *
     * @return string|array|null
     */
    protected function getAdminCode(
        $x,
        string $format
    ) {

        if ( is_numeric( $x ) )
        {
            $x = "adminCode$x";
        }

        if ( $this->$x === null )
        {
            return null;
        }

        return $format
            ? ( $this->$x )[ $format ]
            : $this->$x;
    }


    /**
     * @param  string  $format
     *
     * @return string|array|null
     */
    public function getAdminCode1( $format = 'ISO3166_2' )
    {

        return $this->getAdminCode( 1, $format );
    }


    /**
     * @param  string|array  $adminCode
     *
     * @return Location
     */
    public function setAdminCode1( $adminCode ): Location
    {

        return $this->setAdminCode( 1, $adminCode );
    }


    /**
     * @param  string  $format
     *
     * @return string|array|null
     */
    public function getAdminCode2( $format = 'ISO3166_2' )
    {

        return $this->getAdminCode( 2, $format );
    }


    /**
     * @param  string|array  $adminCode
     *
     * @return Location
     */
    public function setAdminCode2( $adminCode ): Location
    {

        return $this->setAdminCode( 2, $adminCode );
    }


    /**
     * @param  string  $format
     *
     * @return string|array|null
     */
    public function getAdminCode3( $format = 'ISO3166_2' )
    {

        return $this->getAdminCode( 3, $format );
    }


    /**
     * @param  string|array  $adminCode
     *
     * @return Location
     */
    public function setAdminCode3( $adminCode ): Location
    {

        return $this->setAdminCode( 3, $adminCode );
    }


    /**
     * @param  string  $format
     *
     * @return string|array|null
     */
    public function getAdminCode4( $format = 'ISO3166_2' )
    {

        return $this->getAdminCode( 4, $format );
    }


    /**
     * @param  string|array  $adminCode
     *
     * @return Location
     */
    public function setAdminCode4( $adminCode ): Location
    {

        return $this->setAdminCode( 4, $adminCode );
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
    public function setAdminId1( ?int $adminId1 ): Location
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
    public function setAdminId2( ?int $adminId2 ): Location
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
    public function setAdminId3( ?int $adminId3 ): Location
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
    public function setAdminId4( ?int $adminId4 ): Location
    {

        $this->adminId4 = $adminId4;

        return $this;
    }


    /**
     * @return string[]
     */
    protected function getAliases(): array
    {

        static $aliases = [
            'geoname_id'      => 'geonameId',
            'toponymName'     => 'name',
            'ascii_name'      => 'asciiName',
            'alternate_names' => 'alternateNames',
            'feature_class'   => 'featureClass',
            'fcl'             => 'featureClass',
            'feature_code'    => 'featureCode',
            'fcode'           => 'featureCode',
            'country_code'    => 'countryCode',
            'country_id'      => 'countryId',
            'continent'       => 'continentCode',
            'admin1_code'     => 'adminCode1',
            'admin1_id'       => 'adminId1',
            'admin2_code'     => 'adminCode2',
            'admin2_id'       => 'adminId2',
            'admin3_code'     => 'adminCode3',
            'admin3_id'       => 'adminId3',
            'admin4_code'     => 'adminCode4',
            'admin4_id'       => 'adminId4',
            'lng'             => 'longitude',
            'lat'             => 'latitude',
        ];

        return $aliases;
    }


    /**
     * @param  string|null  $lang
     *
     * @return mixed
     */
    public function getAlternateNames( $lang = null )
    {

        if ( $lang === null )
        {
            return $this->alternateNames;
        }

        if ( strtolower( $lang ) === 'json' )
        {
            return \GuzzleHttp\json_encode( $this->alternateNames );
        }

        return $this->alternateNames->$lang ?? null;
    }


    /**
     * @param  object|array|string|null  $alternateNames
     *
     * @return Location
     */
    public function setAlternateNames( $alternateNames ): Location
    {

        if ( is_string( $alternateNames ) )
        {
            $alternateNames = \GuzzleHttp\json_decode( $alternateNames );
        }

        if ( is_array( $alternateNames ) )
        {

            if ( key( $alternateNames ) === 0 )
            {
                $new = [];

                foreach ( $alternateNames as $alternateName )
                {
                    if ( isset( $alternateName->lang ) )
                    {
                        $new[ $alternateName->lang ] = $alternateName->name;
                    }
                }

                $alternateNames = $new;
                unset( $new );
            }

            $alternateNames = (object) $alternateNames;
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
    public function setAsciiName( string $asciiName ): Location
    {

        $this->asciiName = $asciiName;

        return $this;
    }


    /**
     * @param  string|null  $property
     *
     * @return mixed
     */
    public function getBbox( $property = null )
    {

        if ( $this->bbox === null )
        {
            return null;
        }

        if ( ! $this->bbox instanceof BBox )
        {
            $this->bbox = new BBox( $this->bbox );
        }

        if ( $property === null )
        {
            return $this->bbox;
        }

        if ( strtolower( $property ) === 'json' )
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
    public function setBbox( $bbox ): Location
    {

        $this->bbox = $bbox;

        return $this;
    }


    /**
     * @param  string     $hierarchy
     * @param  bool|null  $returnAsLocations
     *
     * @return int[]|\WPGeonames\Entities\Location[]|array|string|null
     * @throws \ErrorException
     */
    public function getChildren(
        $hierarchy = 'adm',
        ?bool $returnAsLocations = true
    ) {

        static $hierarchies = [
            'adm',
            'tourism',
            'geography',
            'dependency',
        ];

        if ( $hierarchy === null && $returnAsLocations === null )
        {
            return $this->children;
        }

        if ( $hierarchy === 'json' )
        {
            return empty( $this->children )
                ? null
                : \GuzzleHttp\json_encode( $this->getChildren( false, false ) );
        }

        if ( ! array_key_exists( $hierarchy, $hierarchies ) )
        {
            return null;
        }

        $keys = $hierarchy
            ? [ $hierarchy ]
            : $hierarchies;

        $result = [];
        $save   = false;

        foreach ( $keys as $key )
        {
            if ( $hierarchy !== false && ! array_key_exists( $hierarchy, $this->children ) )
            {
                $g      = Core::getGeoNameClient();
                $params = [
                    'geonameId' => $this->getGeonameId(),
                    'style'     => 'full',
                    'maxRows'   => 1000,
                ];

                if ( $hierarchy !== 'adm' )
                {
                    $params['hierarchy'] = $hierarchy;
                }

                $this->children[ $key ] = $g->children( $params );

                array_walk(
                    $this->children[ $key ],
                    static function ( &$child )
                    {

                        $child = new Location( $child );
                        $child->save();
                    }
                );

                $save = true;
            }

            foreach ( $this->children[ $key ] as $value )
            {
                switch ( true )
                {
                case $returnAsLocations === true:
                    $result[ $key ][] = $value instanceof static
                        ? $value
                        : new static( $value );
                    break;

                case $returnAsLocations === false:
                    $result[ $key ][] = $value instanceof static
                        ? $value->getGeonameId()
                        : $value;
                    break;

                case $returnAsLocations === null:
                    $result[ $key ][] = $value;
                }
            }
        }

        if ( $save )
        {
            $this->save();
        }

        return $hierarchy === null
            ? $result
            : $result[ $hierarchy ];
    }


    /**
     * @param  int[]|\WPGeonames\Entities\Location[]|null  $children
     *
     * @return Location
     */
    public function setChildren( ?array $children ): Location
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
    public function setContinentCode( string $continentCode ): Location
    {

        $this->continentCode = $continentCode;

        return $this;
    }


    /**
     * @return Country|null
     */
    public function getCountry(): ?Country
    {

        if ( $this->country instanceof Country || $this->country === null )
        {
            return $this->country;
        }

        $this->country = Country::load( $this->country );

        return $this->country;
    }


    /**
     * @param  string|null  $format
     *
     * @param  bool         $autoload
     *
     * @return string|null
     */
    public function getCountryCode(
        ?string $format = 'iso2',
        bool $autoload = true
    ): ?string {

        if ( $this->country === null
            || ( is_string( $this->country )
                && $format === 'iso2'
                && strlen( $this->country ) === 2
            )
        )
        {
            return $this->country;
        }

        return $autoload
            ? $this->getCountry()->$format
            : null;
    }


    /**
     * @param  bool  $autoload
     *
     * @return int
     */
    public function getCountryId( bool $autoload = true ): ?int
    {

        if ( is_int( $this->country ) || $this->country === null )
        {
            return $this->country;
        }

        return $autoload
            ? $this->getCountry()->geonameId
            : null;
    }


    /**
     * @param  int|null  $countryId
     *
     * @return Location
     */
    public function setCountryId( ?int $countryId ): Location
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
    public function setElevation( $elevation ): Location
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
    public function setFeatureClass( $featureClass ): Location
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
    public function setFeatureCode( $featureCode ): Location
    {

        $this->featureCode = $featureCode;

        return $this;
    }


    /**
     * @return int
     */
    public function getGeonameId(): int
    {

        return $this->geonameId ?? 0;
    }


    /**
     * @param  int  $geonameId
     *
     * @return Location
     */
    public function setGeonameId( int $geonameId ): Location
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
    public function setLatitude( $latitude ): Location
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
    public function setLongitude( $longitude ): Location
    {

        $this->longitude = $longitude;

        return $this;
    }


    /**
     * @return string
     */
    public function getName( $langCode = null ): string
    {

        // WPML integration
        if ( $langCode === null and defined( 'ICL_LANGUAGE_CODE' ) )
        {
            $langCode = ICL_LANGUAGE_CODE;
        }

        if ( $langCode !== null )
        {
            $name = $this->getAlternateNames( $langCode );
        }

        return $name ?? $this->name ?? (string) $this->geonameId;
    }


    /**
     * @param  null  $name
     *
     * @return Location
     */
    public function setName( $name ): Location
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
     * @param  int|null  $population
     *
     * @return $this
     */
    public function setPopulation( ?int $population ): Location
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
    public function setScore( ?float $score ): Location
    {

        $this->score = $score;

        return $this;
    }


    /**
     * @return \WPGeonames\Entities\Timezone|\WPGeonames\Helpers\NullSafe
     */
    public function getTimezone()
    {

        if ( $this->timezone instanceof Timezone )
        {
            return $this->timezone;
        }

        if ( $this->timezone === null )
        {
            return new NullSafe();
        }

        return $this->timezone = new static::$_timezoneClass( $this->timezone );
    }


    /**
     * @param  \WPGeonames\Entities\Timezone|string|string[]|null  $timezone
     *
     * @return Location
     */
    public function setTimezone( $timezone ): Location
    {

        switch ( true )
        {
        case $timezone instanceof Timezone:
            break;
        case is_array( $timezone ):
            $timezone = $timezone['timeZoneId'];
            break;
        case is_object( $timezone ):
            $timezone = $timezone->timeZoneId;
            break;
        }

        $this->timezone = $timezone;

        return $this;
    }


    public function isCountry(): bool
    {

        return static::isItACountry( $this, 'featureClass', 'featureCode' );
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

        if ( is_numeric( $x ) )
        {
            $x = "adminCode$x";
        }

        if ( ! is_array( $adminCode ) )
        {
            $adminCode = [
                'ISO3166_2' => $adminCode,
            ];
        }

        $this->$x = $adminCode;

        return $this;
    }


    public function setAstergdem( $elevation ): Location
    {

        $this->elevation = $elevation;

        return $this;
    }


    /**
     * @param  null  $countryCode
     *
     * @return Location
     */
    public function setCountryCode( $countryCode ): Location
    {

        $this->country = $countryCode;

        return $this;
    }


    public function setSrtm3( $elevation ): Location
    {

        $this->elevation = $elevation;

        return $this;
    }


    public function cleanInput( &$values ): FlexibleObjectInterface
    {

        $this->___cleanInput( $values );

        if ( array_key_exists( 'toponymName', $values ) )
        {
            unset( $values['name'] );
        }

        return $this;
    }


    public function format(
        DateTimeInterface $dateTime,
        string $format,
        $locale = null
    ) {

        if ( $locale === false )
        {
            return $dateTime->format( $format );
        }

        if ( class_exists( "IntlDateFormatter" ) )
        {
            /**
             * @see https://unicode-org.github.io/icu/userguide/format_parse/datetime/
             */
            return IntlDateFormatter::formatObject( $dateTime, $format, $locale ?? setlocale( LC_TIME, 0 ) );
        }

        $oldLocale = setlocale( LC_TIME, $locale ?? 0 );
        $result    = utf8_encode( strftime( $format, $dateTime->getTimestamp() ) );
        setlocale( LC_TIME, $oldLocale );

        return $result;

    }


    /**
     * @throws \ErrorException
     */
    public function save(): void
    {

        $alternateNames = $this->getAlternateNames( 'json' );
        $bbox           = $this->getBbox( 'json' );
        $children       = $this->getChildren( 'json' );
        $country        = $this instanceof Country
            ? $this
            : $this->getCountry();

        $sql = Core::$wpdb->prepareAndReplaceTablePrefix(
            <<<SQL
INSERT INTO
    `wp_geonames_locations_cache`
(
      `geoname_id`
    , `name`
    , `ascii_name`
    , `alternate_names`
    , `feature_class`
    , `feature_code`
    , `continent`
    , `country_code`
    , `country_id`
    , `latitude`
    , `longitude`
    , `population`
    , `elevation`
    , `admin1_code`
    , `admin1_id`
    , `admin2_code`
    , `admin2_id`
    , `admin3_code`
    , `admin3_id`
    , `admin4_code`
    , `admin4_id`
    , `timezone`
    , `bbox`
    , `children`
)
VALUES
(
      %d                            -- `geoname_id`
    , NULLIF(%s, '')                -- `name`
    , NULLIF(%s, '')                -- `ascii_name`
    , NULLIF(NULLIF(%s, '{}'), '')  -- `alternate_names`
    , NULLIF(%s, '')                -- `feature_class`
    , NULLIF(%s, '')                -- `feature_code`
    , NULLIF(%s, '')                -- `continent`
    , NULLIF(%s, '')                -- `country_code`
    , NULLIF(%d, 0 )                -- `country_id`
    , %f                            -- `latitude`
    , %f                            -- `longitude`
    , NULLIF(%d, 0 )                -- `population`
    , NULLIF(%d, -32768 )           -- `elevation`
    , NULLIF(%s, '')                -- `admin1_code`
    , NULLIF(%d, 0 )                -- `admin1_id`
    , NULLIF(%s, '')                -- `admin2_code`
    , NULLIF(%d, 0 )                -- `admin2_id`
    , NULLIF(%s, '')                -- `admin3_code`
    , NULLIF(%d, 0 )                -- `admin3_id`
    , NULLIF(%s, '')                -- `admin4_code`
    , NULLIF(%d, 0 )                -- `admin4_id`
    , NULLIF(%s, '')                -- `timezone`
    , NULLIF(NULLIF(%s, '{}'), '')  -- `bbox`
    , NULLIF(NULLIF(%s, '{}'), '')  -- `children`
)

ON DUPLICATE KEY UPDATE 
      `db_update`                   = CURRENT_TIMESTAMP
    , `name`                        = COALESCE(NULLIF(%s, ''), `name`                )
    , `ascii_name`                  = COALESCE(NULLIF(%s, ''), `ascii_name`          )
    , `alternate_names`             = COALESCE(NULLIF(%s, ''), `alternate_names`     )
    , `feature_class`               = COALESCE(NULLIF(%s, ''), `feature_class`       )
    , `feature_code`                = COALESCE(NULLIF(%s, ''), `feature_code`        )
    , `continent`                   = COALESCE(NULLIF(%s, ''), `continent`           )
    , `country_code`                = COALESCE(NULLIF(%s, ''), `country_code`        )
    , `country_id`                  = COALESCE(NULLIF(%s, ''), `country_id`          )
    , `latitude`                    = COALESCE(NULLIF(%s, 0 ), `latitude`            )
    , `longitude`                   = COALESCE(NULLIF(%s, 0 ), `longitude`           )
    , `population`                  = COALESCE(NULLIF(%s, 0 ), `population`          )
    , `elevation`                   = COALESCE(NULLIF(%s, 0 ), `elevation`           )
    , `admin1_code`                 = COALESCE(NULLIF(%s, ''), `admin1_code`         )
    , `admin1_id`                   = COALESCE(NULLIF(%s, 0 ), `admin1_id`           )
    , `admin2_code`                 = COALESCE(NULLIF(%s, ''), `admin2_code`         )
    , `admin2_id`                   = COALESCE(NULLIF(%s, 0 ), `admin2_id`           )
    , `admin3_code`                 = COALESCE(NULLIF(%s, ''), `admin3_code`         )
    , `admin3_id`                   = COALESCE(NULLIF(%s, 0 ), `admin3_id`           )
    , `admin4_code`                 = COALESCE(NULLIF(%s, ''), `admin4_code`         )
    , `admin4_id`                   = COALESCE(NULLIF(%s, 0 ), `admin4_id`           )
    , `timezone`                    = COALESCE(NULLIF(%s, ''), `timezone`            )
    , `bbox`                        = COALESCE(NULLIF(%s, ''), `bbox`                )
    , `children`                    = COALESCE(NULLIF(%s, ''), `children`            )
    
SQL,
            // insert
            $this->getGeonameId(),
            $this->getName(),
            $this->getAsciiName(),
            $alternateNames,
            $this->getFeatureClass(),
            $this->getFeatureCode(),
            $this->getContinentCode(),
            $country
                ? $country->getIso2()
                : null,
            $country
                ? $country->getGeonameId()
                : null,
            $this->getLatitude(),
            $this->getLongitude(),
            $this->getPopulation(),
            $this->getElevation(),
            $this->getAdminCode1(),
            $this->getAdminId1(),
            $this->getAdminCode2(),
            $this->getAdminId2(),
            $this->getAdminCode3(),
            $this->getAdminId3(),
            $this->getAdminCode4(),
            $this->getAdminId4(),
            $this->getTimezone()
                ? $this->getTimezone()
                       ->getName()
                : null,
            $bbox,
            $children,

            // update
            $this->getName(),
            $this->getAsciiName(),
            $alternateNames,
            $this->getFeatureClass(),
            $this->getFeatureCode(),
            $this->getContinentCode(),
            $country
                ? $country->getIso2()
                : null,
            $country
                ? $country->getGeonameId()
                : null,
            $this->getLatitude(),
            $this->getLongitude(),
            $this->getPopulation(),
            $this->getElevation(),
            $this->getAdminCode1(),
            $this->getAdminId1(),
            $this->getAdminCode2(),
            $this->getAdminId2(),
            $this->getAdminCode3(),
            $this->getAdminId3(),
            $this->getAdminCode4(),
            $this->getAdminId4(),
            $this->getTimezone()
                ? $this->getTimezone()
                       ->getName()
                : null,
            $bbox,
            $children,

        );

        if ( Core::$wpdb->query( $sql ) === false )
        {
            throw new ErrorException( Core::$wpdb->last_error );
        }
    }


    public static function isItACountry(
        $object,
        $featureClassProperty,
        $featureCodeProperty
    ): bool {

        if ( $object instanceof Country )
        {
            return true;
        }

        if ( $object->$featureClassProperty === null || $object->$featureCodeProperty === null )
        {
            return false;
        }

        return array_key_exists( $object->$featureClassProperty, Core::FEATURE_FILTERS['countriesOnly'] )
            && in_array(
                $object->$featureCodeProperty,
                Core::FEATURE_FILTERS['countriesOnly'][ $object->$featureClassProperty ],
                true
            );
    }


    /**
     * @param $ids
     *
     * @return array|null
     * @throws \ErrorException
     */
    protected static function loadRecords( $ids ): ?array
    {

        if ( false === ( is_array( $ids )
                ? array_reduce(
                    $ids,
                    static function (
                        $carry,
                        $item
                    ) {

                        return $carry && is_numeric( $item );
                    },
                    true
                )
                : is_numeric( $ids ) ) )
        {
            throw new ErrorException( 'Supplied id(s) are not numeric' );
        }

        $tblCacheLocations = Core::Factory()
                                 ->getTblCacheLocations()
        ;
        $sqlWhere          = sprintf(
            "geoname_id %s",
            is_array( $ids )
                ? sprintf( 'IN (%s)', implode( ',', $ids ) )
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

        $locations = Core::$wpdb->get_results( $sql );

        if ( Core::$wpdb->last_error_no )
        {
            throw new ErrorException( Core::$wpdb->last_error, Core::$wpdb->last_error_no );
        }

        return static::parseArray( $locations );
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
        $key = 'geoname_id',
        $prefix = '_'
    ): ?array {

        WpDb::formatOutput( $array, static::class, $key, $prefix );

        array_walk(
            $array,
            static function ( Location &$location )
            {

                if ( ! $location instanceof static::$_countryClass && $location->isCountry() )
                {
                    $location = new static::$_countryClass( $location );
                }

            }
        );

        return $array;

    }

}
