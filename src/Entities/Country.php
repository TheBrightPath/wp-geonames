<?php

namespace WPGeonames\Entities;

use WPGeonames\Core;
use WPGeonames\FlexibleObject;

/**
 * Class Country
 *
 * @property int    $geonameId Geoname ID
 * @property string $iso2      Country Code as ISO2
 * @property string $iso3      Country Code as ISO3
 * @property int    $isoN      Country ID as ISO
 * @property string $name      Country Name
 * @property string $capital
 * @property string $continent
 * @property string $tld
 * @property string $currencyCode
 * @property string $currencyName
 * @property string $phone
 * @property string $postalCodeFormat
 * @property string $postalCodeRegex
 * @property string $languages
 * @property int    $area
 * @property int    $population
 * @property string $neighbours
 * @property string $equivalentFipsCode
 */
class Country
    extends FlexibleObject
{
    // protected properties
    /** @var \WPGeonames\Entities\Country[] */
    protected static $countries = [];
    protected static $aliases
                                = [
            'geoname_id'           => 'geonameId',
            'country'              => 'name',
            '$currency_code'       => 'currencyCode',
            'country_code'         => 'countryCode',
            'currency_name'        => 'currencyName',
            'postal_code_format'   => 'postalCodeFormat',
            'postal_code_regex'    => 'postalCodeRegex',
            'fips' => 'equivalentFipsCode',
            'equivalent_fips_code' => 'equivalentFipsCode',
        ];

    /** @var string */
    protected $iso2;
    /** @var string */
    protected $iso3;
    /** @var int */
    protected $isoN;
    /** @var string|null */
    protected $fips;
    /** @var string */
    protected $country;
    /** @var string */
    protected $capital;
    /** @var int|null (in sq km) */
    protected $area;
    /** @var int|null */
    protected $population;
    /** @var string|null enum('af','an','as','eu','na','oc','sa') */
    protected $continent;
    /** @var string|null */
    protected $tld;
    /** @var string|null */
    protected $currencyCode;
    /** @var string|null */
    protected $currencyName;
    /** @var string|null */
    protected $phone;
    /** @var string|null */
    protected $postalCodeFormat;
    /** @var string|null */
    protected $postalCodeRegex;
    /** @var string|null */
    protected $languages;
    /** @var int */
    protected $geonameId;
    /** @var string|null */
    protected $neighbours;
    /** @var int|null */
    protected $equivalentFipsCode;


    /**
     * @return int
     */
    public function getArea(): int
    {

        return $this->area;
    }


    /**
     * @param  int  $area
     *
     * @return Country
     */
    public function setArea(int $area): Country
    {

        $this->area = $area;

        return $this;
    }


    /**
     * @return string
     */
    public function getCapital(): string
    {

        return $this->capital;
    }


    /**
     * @param  string  $capital
     *
     * @return Country
     */
    public function setCapital(string $capital): Country
    {

        $this->capital = $capital;

        return $this;
    }


    /**
     * @return string
     */
    public function getContinent(): string
    {

        return $this->continent;
    }


    /**
     * @param  string  $continent
     *
     * @return Country
     */
    public function setContinent(string $continent): Country
    {

        $this->continent = $continent;

        return $this;
    }


    /**
     * @return string
     */
    public function getCurrencyCode(): string
    {

        return $this->currencyCode;
    }


    /**
     * @param  string  $currencyCode
     *
     * @return Country
     */
    public function setCurrencyCode(string $currencyCode): Country
    {

        $this->currencyCode = $currencyCode;

        return $this;
    }


    /**
     * @return string
     */
    public function getCurrencyName(): string
    {

        return $this->currencyName;
    }


    /**
     * @param  string  $currencyName
     *
     * @return Country
     */
    public function setCurrencyName(string $currencyName): Country
    {

        $this->currencyName = $currencyName;

        return $this;
    }


    /**
     * @return string
     */
    public function getEquivalentFipsCode(): string
    {

        return $this->equivalentFipsCode;
    }


    /**
     * @param  string  $equivalentFipsCode
     *
     * @return Country
     */
    public function setEquivalentFipsCode(string $equivalentFipsCode): Country
    {

        $this->equivalentFipsCode = $equivalentFipsCode;

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
     * @param  int  $geonameId
     *
     * @return Country
     */
    public function setGeonameId(int $geonameId): Country
    {

        $this->geonameId = $geonameId;

        return $this;
    }


    /**
     * @return string
     */
    public function getIso2(): string
    {

        return $this->iso2;
    }


    /**
     * @param  string  $iso2
     *
     * @return Country
     */
    public function setIso2(string $iso2): Country
    {

        $this->iso2 = $iso2;

        return $this;
    }


    /**
     * @return string
     */
    public function getIso3(): string
    {

        return $this->iso3;
    }


    /**
     * @param  string  $iso3
     *
     * @return Country
     */
    public function setIso3(string $iso3): Country
    {

        $this->iso3 = $iso3;

        return $this;
    }


    /**
     * @return int
     */
    public function getIsoN(): int
    {

        return $this->isoN;
    }


    /**
     * @param  int  $isoN
     *
     * @return Country
     */
    public function setIsoN(int $isoN): Country
    {

        $this->isoN = $isoN;

        return $this;
    }


    /**
     * @return string
     */
    public function getLanguages(): string
    {

        return $this->languages;
    }


    /**
     * @param  string  $languages
     *
     * @return Country
     */
    public function setLanguages(string $languages): Country
    {

        $this->languages = $languages;

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
     * @return string
     */
    public function getNeighbours(): string
    {

        return $this->neighbours;
    }


    /**
     * @param  string  $neighbours
     *
     * @return Country
     */
    public function setNeighbours(string $neighbours): Country
    {

        $this->neighbours = $neighbours;

        return $this;
    }


    /**
     * @return string
     */
    public function getPhone(): string
    {

        return $this->phone;
    }


    /**
     * @param  string  $phone
     *
     * @return Country
     */
    public function setPhone(string $phone): Country
    {

        $this->phone = $phone;

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
     * @param  int  $population
     *
     * @return Country
     */
    public function setPopulation(int $population): Country
    {

        $this->population = $population;

        return $this;
    }


    /**
     * @return string
     */
    public function getPostalCodeFormat(): string
    {

        return $this->postalCodeFormat;
    }


    /**
     * @param  string  $postalCodeFormat
     *
     * @return Country
     */
    public function setPostalCodeFormat(string $postalCodeFormat): Country
    {

        $this->postalCodeFormat = $postalCodeFormat;

        return $this;
    }


    /**
     * @return string
     */
    public function getPostalCodeRegex(): string
    {

        return $this->postalCodeRegex;
    }


    /**
     * @param  string  $postalCodeRegex
     *
     * @return Country
     */
    public function setPostalCodeRegex(string $postalCodeRegex): Country
    {

        $this->postalCodeRegex = $postalCodeRegex;

        return $this;
    }


    /**
     * @return string
     */
    public function getTld(): string
    {

        return $this->tld;
    }


    /**
     * @param  string  $tld
     *
     * @return Country
     */
    public function setTld(string $tld): Country
    {

        $this->tld = $tld;

        return $this;
    }


    /**
     * @param  string  $name
     *
     * @return Country
     */
    public function setName(string $name): Country
    {

        $this->name = $name;

        return $this;
    }


    public static function load(string $country): self
    {

        if (is_numeric($country))
        {
            if (array_key_exists("_$country", static::$countries))
            {
                return static::$countries["_$country"];
            }

            $sqlWhere = Core::$wpdb->prepare("geoname_id = %d", $country);
        }
        else
        {
            if (array_key_exists($country, static::$countries))
            {
                return static::$countries[$country];
            }

            $sqlWhere = Core::$wpdb->prepare("iso2 = %s", $country);
        }

        $table = Core::Factory()
                     ->getTblCountries()
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

        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $country = Core::$wpdb->get_row($sql);

        if (Core::$wpdb->last_error_no)
        {
            throw new \ErrorException(Core::$wpdb->last_error, Core::$wpdb->last_error_no);
        }

        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $country = new static($country);

        static::$countries[$country->iso2]         = $country;
        static::$countries["_$country->geonameId"] = $country;

        return $country;
    }

}