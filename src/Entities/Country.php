<?php

namespace WPGeonames\Entities;

use ErrorException;
use Locale;
use WPGeonames\Core;
use WPGeonames\Helpers\FlexibleObjectTrait;
use WPGeonames\Helpers\NullSafe;

/**
 * Class Country
 *
 * @property string $iso2      Country Code as ISO2
 * @property string $iso3      Country Code as ISO3
 * @property int    $isoN      Country ID as ISO
 * @property string $capital
 * @property string $tld
 * @property string $currencyCode
 * @property string $currencyName
 * @property string $phone
 * @property string $postalCodeFormat
 * @property string $postalCodeRegex
 * @property string $languages
 * @property int    $area
 * @property string $neighbours
 * @property string $fipsCode
 */
class Country
    extends
    Location
{

    use FlexibleObjectTrait
    {
        FlexibleObjectTrait::__construct as private _FlexibleObjectTrait__construct;
        __toString as private _FlexibleDbObjectTrait__toString;
    }

// constants

    public const API_UPDATE_INFO_BOTH     = 0;
    public const API_UPDATE_INFO_COUNTRY  = 1;
    public const API_UPDATE_INFO_LOCATION = - 1;

// protected properties

    /**
     * Is required here to separate it from the parent class
     *
     * @var string[]
     */
    protected static $_aliases;

    /**
     * @var \WPGeonames\Entities\Country[]
     */
    protected static $_countries = [];

    /**
     * @var integer GeonameId of the wp_geonames_countries table
     */
    protected $_idCountry;

    /**
     * @var string
     */
    protected $iso2;

    /**
     * @var string
     */
    protected $iso3;

    /**
     * @var int
     */
    protected $isoN;

    /**
     * @var string|null
     */
    protected $fipsCode;

    /**
     * @var string
     */
    protected $capital;

    /**
     * @var int|null (in sq km)
     */
    protected $area;

    /**
     * @var string|null
     */
    protected $tld;

    /**
     * @var string|null
     */
    protected $currencyCode;

    /**
     * @var string|null
     */
    protected $currencyName;

    /**
     * @var string|null
     */
    protected $phone;

    /**
     * @var string|null
     */
    protected $postalCodeFormat;

    /**
     * @var string|null
     */
    protected $postalCodeRegex;

    /**
     * @var string|null
     */
    protected $languages;

    /**
     * @var string|null
     */
    protected $neighbours;


    /**
     * Country constructor is required to avoid jumping directly to FlexibleObjectTrait::__construct
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

        parent::__construct( $values, $defaults );

        if ( $values instanceof Country )
        {
            $this->_idCountry = $values->_idCountry;
        }

        // unless no values are provided, make sure thae country code is set so that it is available through static::äcountries
        if ( ! empty( $values ) )
        {
            if ( empty( $this->getIso2() ) )
            {
                throw new \ErrorException( sprintf( 'Country code missing for geonameId %d', $this->getGeonameId() ) );
            }
        }

    }


    public function __toString()
    {

        return (string) ( $this->getIso2( false ) ?? $this->getGeonameId() ?? '' );
    }


    public function getAdmin1( bool $autoload = true ): ?object
    {

        return new NullSafe();
    }


    public function getAdmin2( bool $autoload = true ): ?object
    {

        return new NullSafe();
    }


    public function getAdmin3( bool $autoload = true ): ?object
    {

        return new NullSafe();
    }


    public function getAdmin4( bool $autoload = true ): ?object
    {

        return new NullSafe();
    }


    /**
     * @return string[]
     */
    protected function getAliases(): array
    {

        static $_aliases
            = [
            'id'                   => 'geonameId',
            'country_id'           => 'geonameId',
            'countryName'          => 'country',
            'currency_code'        => 'currencyCode',
            'currencyCode'         => 'currencyCode',
            'country_code'         => 'iso2',
            'countryCode'          => 'iso2',
            'isoAlpha3'            => 'iso3',
            'isoNumeric'           => 'isoN',
            'areaInSqKm'           => 'area',
            'currency_name'        => 'currencyName',
            'postal_code_format'   => 'postalCodeFormat',
            'postal_code_regex'    => 'postalCodeRegex',
            'fips'                 => 'fipsCode',
            'equivalentFipsCode'   => 'fipsCode',
            'equivalent_fips_code' => 'fipsCode',
        ];

        /** @noinspection AdditionOperationOnArraysInspection */
        return $_aliases + parent::getAliases();
    }


    /**
     * @param  bool  $autoload
     *
     * @return int|null
     * @throws \ErrorException
     */
    public function getArea( bool $autoload = true ): ?int
    {

        return $this->__getOrUpdate( $this->area, $autoload );
    }


    /**
     * @param  int|null  $area
     *
     * @return Country
     */
    public function setArea( ?int $area ): Country
    {

        $this->area = $area ?? $this->area;

        return $this;
    }


    /**
     * @param  bool  $autoload
     *
     * @return string|null
     * @throws \ErrorException
     */
    public function getCapital( bool $autoload = true ): ?string
    {

        return $this->__getOrUpdate( $this->capital, $autoload );
    }


    /**
     * @param  string|null  $capital
     *
     * @return Country
     */
    public function setCapital( ?string $capital ): Country
    {

        $this->capital = $capital ?? $this->capital;

        return $this;
    }


    /**
     * @param  bool         $autoload
     * @param  bool         $nullSafe
     * @param  string|null  $countryClass
     *
     * @return Country|NullSafe|null
     */
    public function getCountry(
        bool $autoload = true,
        bool $nullSafe = true,
        ?string $countryClass = null
    ): object {

        return $this;
    }


    public function getCountryCode(
        ?string $format = 'iso2',
        bool $autoload = true
    ): ?string {

        return $this->__get( $format );
    }


    public function getCountryId( bool $autoload = true ): ?int
    {

        return $this->getGeonameId();
    }


    /**
     * @param  bool  $autoload
     *
     * @return string|null
     * @throws \ErrorException
     */
    public function getCurrencyCode( bool $autoload = true ): ?string
    {

        return $this->__getOrUpdate( $this->currencyCode, $autoload );
    }


    /**
     * @param  string|null  $currencyCode
     *
     * @return Country
     */
    public function setCurrencyCode( ?string $currencyCode ): Country
    {

        $this->currencyCode = $currencyCode ?? $this->currencyCode;

        return $this;
    }


    /**
     * @param  bool  $autoload
     *
     * @return string|null
     * @throws \ErrorException
     */
    public function getCurrencyName( bool $autoload = true ): ?string
    {

        return $this->__getOrUpdate( $this->currencyName, $autoload );
    }


    /**
     * @param  string|null  $currencyName
     *
     * @return Country
     */
    public function setCurrencyName( ?string $currencyName ): Country
    {

        $this->currencyName = $currencyName ?? $this->currencyName;

        return $this;
    }


    /**
     * @param  bool  $autoload
     *
     * @return string|null
     * @throws \ErrorException
     */
    public function getFipsCode( bool $autoload = true ): ?string
    {

        return $this->__getOrUpdate( $this->fipsCode, $autoload );
    }


    /**
     * @param  string|null  $fipsCode
     *
     * @return Country
     */
    public function setFipsCode( ?string $fipsCode ): Country
    {

        $this->fipsCode = $fipsCode ?? $this->fipsCode;

        return $this;
    }


    /**
     * @param  bool  $autoload
     *
     * @return string|null
     * @throws \ErrorException
     */
    public function getIso2( bool $autoload = true ): ?string
    {

        return $this->__getOrUpdate( $this->iso2, $autoload );
    }


    /**
     * @param  string|null  $iso2
     *
     * @return Country
     * @throws \ErrorException
     */
    public function setIso2( ?string $iso2 ): Country
    {

        // ignore if new value is null or they're the same
        if ( $iso2 === null || $iso2 === '' || $this->iso2 === ( $iso2 = strtoupper( $iso2 ) ) )
        {
            return $this;
        }

        // fail, if already set
        if ( ! empty( $this->iso2 ) )
        {
            throw new ErrorException(
                sprintf( 'ISO2 country code of an object cannot be changed. Old: %s, New: %s', $this->iso2, $iso2 )
            );
        }

        // fail, if already exists
        if ( array_key_exists( $iso2, static::$_countries ) )
        {
            throw new ErrorException(
                sprintf( 'An instance with this country code already exists. ISO2: %s', $iso2 )
            );
        }

        $this->iso2 = $iso2;

        static::$_countries[ $iso2 ] = $this;

        return $this;
    }


    /**
     * @param  bool  $autoload
     *
     * @return string|null
     * @throws \ErrorException
     */
    public function getIso3( bool $autoload = true ): ?string
    {

        return $this->__getOrUpdate( $this->iso3, $autoload );
    }


    /**
     * @param  string|null  $iso3
     *
     * @return Country
     */
    public function setIso3( ?string $iso3 ): Country
    {

        $this->iso3 = $iso3 ?? $this->iso3;

        return $this;
    }


    /**
     * @param  bool  $autoload
     *
     * @return int|null
     * @throws \ErrorException
     */
    public function getIsoN( bool $autoload = true ): ?int
    {

        return $this->__getOrUpdate( $this->isoN, $autoload );
    }


    /**
     * @param  int|null  $isoN
     *
     * @return Country
     */
    public function setIsoN( ?int $isoN ): Country
    {

        $this->isoN = $isoN ?? $this->isoN;

        return $this;
    }


    /**
     * @param  bool  $autoload
     *
     * @return string|null
     * @throws \ErrorException
     */
    public function getLanguages( bool $autoload = true ): ?string
    {

        return $this->__getOrUpdate( $this->languages, $autoload );
    }


    /**
     * @param  string|null  $languages
     *
     * @return Country
     */
    public function setLanguages( ?string $languages ): Country
    {

        $this->languages = $languages ?? $this->languages;

        return $this;
    }


    /**
     * @param  string|null  $langCode
     * @param  bool         $autoload
     *
     * @return string
     * @throws \ErrorException
     */
    public function getNameIntl(
        ?string $langCode = null,
        bool $autoload = true
    ): string {

        // WPML integration
        if ( $langCode === null && defined( 'ICL_LANGUAGE_CODE' ) )
        {
            $langCode = ICL_LANGUAGE_CODE;
        }

        return Locale::getDisplayRegion( '-' . $this->getIso2( $autoload ), $langCode ) ?? $this->getName(
                $langCode
            );
    }


    /**
     * @param  bool  $autoload
     *
     * @return string|null
     * @throws \ErrorException
     */
    public function getNeighbours( bool $autoload = true ): ?string
    {

        return $this->__getOrUpdate( $this->neighbours, $autoload );
    }


    /**
     * @param  string|null  $neighbours
     *
     * @return Country
     */
    public function setNeighbours( ?string $neighbours ): Country
    {

        $this->neighbours = $neighbours ?? $this->neighbours;

        return $this;
    }


    /**
     * @param  bool  $autoload
     *
     * @return string|null
     * @throws \ErrorException
     */
    public function getPhone( bool $autoload = true ): ?string
    {

        return $this->__getOrUpdate( $this->phone, $autoload );
    }


    /**
     * @param  string|null  $phone
     *
     * @return Country
     */
    public function setPhone( ?string $phone ): Country
    {

        $this->phone = $phone ?? $this->phone;

        return $this;
    }


    /**
     * @param  bool  $autoload
     *
     * @return string|null
     * @throws \ErrorException
     */
    public function getPostalCodeFormat( bool $autoload = true ): ?string
    {

        return $this->__getOrUpdate( $this->postalCodeFormat, $autoload );
    }


    /**
     * @param  string|null  $postalCodeFormat
     *
     * @return Country
     */
    public function setPostalCodeFormat( ?string $postalCodeFormat ): Country
    {

        $this->postalCodeFormat = $postalCodeFormat ?? $this->postalCodeFormat;

        return $this;
    }


    /**
     * @param  bool  $autoload
     *
     * @return string|null
     * @throws \ErrorException
     */
    public function getPostalCodeRegex( bool $autoload = true ): ?string
    {

        return $this->__getOrUpdate( $this->postalCodeRegex, $autoload );
    }


    /**
     * @param  string|null  $postalCodeRegex
     *
     * @return Country
     */
    public function setPostalCodeRegex( ?string $postalCodeRegex ): Country
    {

        $this->postalCodeRegex = $postalCodeRegex ?? $this->postalCodeRegex;

        return $this;
    }


    /**
     * @param  bool  $autoload
     *
     * @return string|null
     * @throws \ErrorException
     */
    public function getTld( bool $autoload = true ): ?string
    {

        return $this->__getOrUpdate( $this->tld, $autoload );
    }


    /**
     * @param  string|null  $tld
     *
     * @return Country
     */
    public function setTld( ?string $tld ): self
    {

        $this->tld = $tld ?? $this->tld;

        return $this;
    }


    /**
     * @see self::setName()
     *
     * @param  string|null  $countryName
     *
     * @return Country
     */
    public function setCountry( ?string $countryName ): Country
    {

        if ( empty( $this->name ) )
        {
            $this->name = $countryName;
        }

        return $this;
    }


    /**
     * @param  null  $countryCode
     *
     * @return \WPGeonames\Entities\Location
     * @throws \ErrorException
     */
    public function setCountryCode( $countryCode ): Location
    {

        return $this->setIso2( $countryCode );
    }


    /**
     * @param  int|null  $countryId
     *
     * @return \WPGeonames\Entities\Location
     * @throws \ErrorException
     */
    public function setCountryId( ?int $countryId ): Location
    {

        return $this->setGeonameId( $countryId );
    }


    /**
     * @param  int|null  $cId
     *
     * @return Country
     * @throws \ErrorException
     */
    protected function setIdCountry( ?int $cId ): Country
    {

        $this->setGeonameId( $cId );
        $this->_idCountry = $cId;

        return $this;
    }


    /**
     * @param  bool  $skipUpdateMissing
     * @param  bool  $force
     *
     * @throws \ErrorException
     */
    public function save(
        bool $skipUpdateMissing = false,
        bool $force = false
    ): void {

        static $saving = false;

        // infinite loop prevention
        if ( $saving )
        {
            return;
        }
        $saving = true;

        if ( ! $skipUpdateMissing )
        {
            $this->updateMissingData();
        }

        if ( ! $this->_isDirty && ! $force )
        {
            return;
        }

        // save country info

        $sql = Core::$wpdb->prepareAndReplaceTablePrefix(
            <<<SQL
INSERT INTO
    `wp_geonames_countries`
(
      `geoname_id`
    , `iso2`
    , `iso3`
    , `isoN`
    , `fips`
    , `country`
    , `capital`
    , `languages`
    , `continent`
    , `neighbours`
    , `area`
    , `population`
    , `tld`
    , `currency_code`
    , `currency_name`
    , `phone`
    , `postal_code_format`
    , `postal_code_regex`
)
VALUES
(
      %d -- `geoname_id`
    , %s -- `iso2`
    , %s -- `iso3`
    , %d -- `isoN`
    , %s -- `fips`
    , %s -- `country`
    , %s -- `capital`
    , %s -- `languages`
    , %s -- `continent`
    , %s -- `neighbours`
    , %d -- `area`
    , %d -- `population`
    , %s -- `tld`
    , %s -- `currency_code`
    , %s -- `currency_name`
    , %d -- `phone`
    , %s -- `postal_code_format`
    , %s -- `postal_code_regex`
)

ON DUPLICATE KEY UPDATE 
      `db_update`                   = CURRENT_TIMESTAMP
    , `iso2`                        = COALESCE(NULLIF(%s, ''), `iso2`                )
    , `iso3`                        = COALESCE(NULLIF(%s, ''), `iso3`                )
    , `isoN`                        = COALESCE(NULLIF(%s, 0 ), `isoN`                )
    , `fips`                        = COALESCE(NULLIF(%s, ''), `fips`                )
    , `country`                     = COALESCE(NULLIF(%s, ''), `country`             )
    , `capital`                     = COALESCE(NULLIF(%s, ''), `capital`             )
    , `languages`                   = COALESCE(NULLIF(%s, ''), `languages`           )
    , `continent`                   = COALESCE(NULLIF(%s, ''), `continent`           )
    , `neighbours`                  = COALESCE(NULLIF(%s, ''), `neighbours`          )
    , `area`                        = COALESCE(NULLIF(%s, 0 ), `area`                )
    , `population`                  = COALESCE(NULLIF(%s, 0 ), `population`          )
    , `tld`                         = COALESCE(NULLIF(%s, ''), `tld`                 )
    , `currency_code`               = COALESCE(NULLIF(%s, ''), `currency_code`       )
    , `currency_name`               = COALESCE(NULLIF(%s, ''), `currency_name`       )
    , `phone`                       = COALESCE(NULLIF(%s, 0 ), `phone`               )
    , `postal_code_format`          = COALESCE(NULLIF(%s, ''), `postal_code_format`  )
    , `postal_code_regex`           = COALESCE(NULLIF(%s, ''), `postal_code_regex`   )
    
SQL,
            // insert
            $this->getGeonameId(),
            $this->getIso2(),
            $this->getIso3(),
            $this->getIsoN(),
            $this->getFipsCode(),
            $this->getAsciiName(),
            $this->getCapital(),
            $this->getLanguages(),
            $this->getContinentCode(),
            $this->getNeighbours(),
            $this->getArea(),
            $this->getPopulation(),
            $this->getTld(),
            $this->getCurrencyCode(),
            $this->getCurrencyName(),
            $this->getPhone(),
            $this->getPostalCodeFormat(),
            $this->getPostalCodeRegex(),
            // update
            $this->getIso2(),
            $this->getIso3(),
            $this->getIsoN(),
            $this->getFipsCode(),
            $this->getAsciiName(),
            $this->getCapital(),
            $this->getLanguages(),
            $this->getContinentCode(),
            $this->getNeighbours(),
            $this->getArea(),
            $this->getPopulation(),
            $this->getTld(),
            $this->getCurrencyCode(),
            $this->getCurrencyName(),
            $this->getPhone(),
            $this->getPostalCodeFormat(),
            $this->getPostalCodeRegex(),

        );

        $saving = false;

        if ( Core::$wpdb->query( $sql ) === false )
        {
            throw new ErrorException( Core::$wpdb->last_error );
        }

        if ( $this->_idCountry === null && ( $this->_idAPI ?? $this->_idLocation ) )
        {
            $this->_idCountry = ( $this->_idAPI ?? $this->_idLocation );
        }

        parent::save( true, $force );
    }


    public function updateFromApi( int $what = self::API_UPDATE_INFO_BOTH ): Location
    {

        if ( $what <= self::API_UPDATE_INFO_BOTH || $this->iso2 === null )
        {
            // update location
            parent::updateFromApi();
        }

        if ( $what >= self::API_UPDATE_INFO_BOTH && $this->iso2 )
        {
            // update country
            $item = Core::getGeoNameClient()
                        ->countryInfo(
                            [
                                'country' => $this->iso2,
                            ]
                        )
            ;

            $this->_idCountry = $this->geonameId;
            $this->setIdAPI( $this->geonameId );
            $this->loadValues( $item );
            $this->save( true );
        }

        return $this;
    }


    protected function updateMissingData( int $what = self::API_UPDATE_INFO_BOTH ): Location
    {

        // load location if it has not been loaded nor from the database nor the API
        if ( $what <= self::API_UPDATE_INFO_BOTH )
        {
            parent::updateMissingData( self::API_UPDATE_INFO_LOCATION );
        }

        // load country
        if ( ( $this->geonameId !== null || $this->iso2 !== null ) && $what >= self::API_UPDATE_INFO_BOTH && $this->_idCountry === null )
        {
            // load country from database
            if ( $item = Core::$wpdb->get_row(
                Core::$wpdb->prepareAndReplaceTablePrefix(
                    'SELECT * FROM `wp_geonames_countries` WHERE geoname_id = %d OR iso2 = %s',
                    $this->geonameId ?? - 1,
                    $this->iso2 == '--'
                )
            ) )
            {
                $this->_idCountry = $this->geonameId;
                $this->loadValues( $item );
            }
            elseif ( $this->iso2 !== null )
            {
                // or api
                $this->updateFromApi( self::API_UPDATE_INFO_COUNTRY );
            }
        }

        return $this;
    }


    protected static function loadDetectId(
        &$id,
        $index,
        object $options
    ): void {

        $options               = $options ?? new \stdClass();
        $options->countryClass = $options->countryClass ?? static::$_countryClass;

        if ( $id instanceof $options->countryClass )
        {

            return;
        }

        $options->skipVerification = true;

        parent::loadDetectId(
            $id,
            $index,
            $options
        );

        if ( $id instanceof Location )
        {
            if ( $id->getGeonameId() === 0 && $id->getCountryCode( 'iso2', false ) === null )
            {
                /** @noinspection ForgottenDebugOutputInspection */
                error_log( 'Received invalid Location object while loading a country object', E_USER_WARNING );

                $id = null;

                return;
            }
        }
    }


    /**
     * @param  null        $ids
     * @param  array|null  $countryFeatures
     *
     * @return array|null
     * @throws \ErrorException
     */
    public static function loadRecords(
        $ids = null,
        object $options = null
    ): ?array {

        static $allLoaded = false;

        $options                = $options ?? new \stdClass();
        $options->countryClass  = $options->countryClass ?? static::$_countryClass;
        $options->locationClass = $options->countryClass;
        $loadingAll             = 0;

        if ( $ids === null )
        {
            if ( $allLoaded )
            {
                return static::$_countries;
            }

            $ids        = static::$_countries;
            $allLoaded  = true;
            $loadingAll = 1;
        }

        $ids = is_object( $ids )
            ? [ $ids ]
            : (array) $ids;

        array_walk(
            $ids,
            [
                static::class,
                'loadDetectId',
            ],
            $options
        );

        $sqlGeonameIds = array_reduce(
            $ids,
            static function (
                ?string $carry,
                $id
            ) use
            (
                $loadingAll
            ): ?string
            {

                if ( $loadingAll === 1 && $id instanceof Location )
                {
                    $id = $id->getGeonameId();
                }

                if ( is_int( $id ) )
                {
                    return $carry === null
                        ? (string) $id
                        : $carry . "," . $id;
                }

                return $carry;
            }
        );

        $sqlCountryCodes = array_reduce(
            $loadingAll
                ? []
                : $ids,
            static function (
                ?string $carry,
                $id
            ): ?string {

                if ( is_string( $id ) )
                {
                    return $carry === null
                        ? (string) $id
                        : $carry . "," . Core::$wpdb->prepare( "%s", $id );
                }

                return $carry;
            }
        );

        // only keep countries
        $ids = array_filter(
            $ids,
            static function ( $id )
            {

                return $id instanceof Country;
            }
        );

        parent::parseArray( $ids, null, '_', $options->countryClass, null, $options->countryClass );

        if ( $loadingAll === 0 && $sqlGeonameIds === null && $sqlCountryCodes === null )
        {

            return $ids;
        }

        $sqlGeonameIds      = $sqlGeonameIds ?? '-1';
        $sqlCountryCodes    = $sqlCountryCodes ?? "'--'";
        $sqlCountryFeatures = $options->countryFeatures ?? Core::FEATURE_FILTERS['countriesOnly'];

        array_walk(
            $sqlCountryFeatures,
            static function (
                &$array,
                $featureClass
            ) {

                $array = sprintf(
                    "(feature_class = '%s' AND feature_code IN ('%s'))",
                    $featureClass,
                    implode( "','", $array )
                );
            }
        );

        $sqlCountryFeatures = implode( ' OR ', $sqlCountryFeatures );
        $sqlNOT             = $loadingAll
            ? 'NOT'
            : '';

        $sql = Core::$wpdb::replaceTablePrefix(
            <<<SQL
SELECT
     COALESCE(l.geoname_id ,c.geoname_id)   AS ID
    ,l.geoname_id                           as idLocation
    ,c.geoname_id                           as idCountry
    ,l.*
    ,c.*
    ,COALESCE(l.geoname_id ,c.geoname_id)   AS geoname_id

FROM
    (
        SELECT
             geoname_id
        FROM
            `wp_geonames_countries`             c
        WHERE
                geoname_id          IS NOT NULL
            AND (
                0
                OR c.geoname_id     $sqlNOT IN ($sqlGeonameIds)
                OR c.iso2           IN ($sqlCountryCodes)
            )
        
        UNION
        
        SELECT
             geoname_id
        FROM
            `wp_geonames_locations_cache`       l
        WHERE
                geoname_id          IS NOT NULL
            AND (
                0
                OR   l.geoname_id     $sqlNOT IN ($sqlGeonameIds)
                OR ( l.country_code   IN ($sqlCountryCodes) AND ($sqlCountryFeatures) )
            )
   )                                    id
LEFT JOIN
    `wp_geonames_countries`             c   ON id.geoname_id = c.geoname_id
LEFT JOIN
    `wp_geonames_locations_cache`       l   ON id.geoname_id = l.geoname_id

WHERE
        $loadingAll = 0
    OR  (
            feature_class IS NULL 
        OR  ($sqlCountryFeatures)
        )
;
SQL
        );

        $countries = Core::$wpdb->get_results( $sql );

        if ( Core::$wpdb->last_error_no )
        {
            throw new ErrorException( Core::$wpdb->last_error, Core::$wpdb->last_error_no );
        }

        parent::parseArray( $countries, null, '_', $options->countryClass, null, $options->countryClass );

        /** @noinspection AdditionOperationOnArraysInspection */
        return $countries + $ids;
    }

}
