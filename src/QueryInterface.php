<?php

namespace WPGeonames;

use WPGeonames\Query\ParentQueryInterface;
use WPGeonames\Query\Query;

/**
 * Class ApiQuery
 *
 * @property $q               string      search over all attributes of a place : place name, country name, continent,
 *           admin codes, ... (Important:urlencoded utf8)
 * @property $name            string   place name only(Important:urlencoded utf8)
 * @property $name_equals     string         (q,name or name_equals required)    exact place name
 * @property $name_startsWith string (optional)    place name starts with given characters
 *
 */
interface QueryInterface
    extends
    Query,
    ParentQueryInterface
{

    /**
     * @return string
     */
    public function getAdminCode1(): string;

    /**
     * @return string
     */
    public function getAdminCode2(): string;

    /**
     * @return string
     */
    public function getAdminCode3(): string;

    /**
     * @return string
     */
    public function getAdminCode4(): string;

    /**
     * @return string
     */
    public function getAdminCode5(): string;

    /**
     * @return string
     */
    public function getCharset(): string;

    /**
     * @return string
     */
    public function getCities(): string;

    /**
     * @return string|string[]|null
     */
    public function getContinentCode();

    /**
     * @return string|string[]|null
     */
    public function getCountry();

    public function getCountryAsArray( bool $returnNullIfEmpty = false ): ?array;

    public function getCountryAsJson( bool $returnNullIfEmpty = false ): ?string;

    /**
     * @return string
     */
    public function getCountryBias(): string;

    /**
     * @return float
     */
    public function getEast(): float;

    /**
     * @return string[]|null
     */
    public function getFeatureClass(): ?array;

    /**
     * @return string|string[]|null
     */
    public function getFeatureCode();

    /**
     * @return float
     */
    public function getFuzzy(): float;

    /**
     * @return string
     */
    public function getLang(): string;

    /**
     * @return int
     */
    public function getMaxRows(): int;

    /**
     * @return int
     */
    public function getMaxStartRow(): int;

    /**
     * @return float
     */
    public function getNorth(): float;

    /**
     * @return string
     */
    public function getOperator(): string;

    /**
     * @return string
     */
    public function getOrderby(): string;

    /**
     * @return string
     */
    public function getSearchLang(): string;

    public function getSearchParamsAsArray( ?string &$searchTerm = null ): array;

    public function getSearchParamsAsJson(
        ?string &$searchTerm = null,
        array &$myParams = []
    ): string;

    /**
     * @return string
     */
    public function getSearchTerm(): string;

    /**
     * @return int
     */
    public function getSearchType(): int;

    public function getSearchTypeAsArray(
        $searchTypeFilter = null,
        bool $returnKeys = false
    );

    public function getSingleCountry(): ?string;

    /**
     * @return float
     */
    public function getSouth(): float;

    /**
     * @return int
     */
    public function getStartRow(): int;

    /**
     * @return string
     */
    public function getStyle(): string;

    /**
     * @return string
     */
    public function getTag(): string;

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @return float
     */
    public function getWest(): float;

    /**
     * @return bool
     */
    public function isInclBbox(): bool;

    /**
     * @return bool
     */
    public function isNameRequired(): bool;

    /**
     * @param  string  $adminCode1
     *
     * @return Query
     */
    public function setAdminCode1( string $adminCode1 ): Query;

    /**
     * @param  string  $adminCode2
     *
     * @return Query
     */
    public function setAdminCode2( string $adminCode2 ): Query;

    /**
     * @param  string  $adminCode3
     *
     * @return Query
     */
    public function setAdminCode3( string $adminCode3 ): Query;

    /**
     * @param  string  $adminCode4
     *
     * @return Query
     */
    public function setAdminCode4( string $adminCode4 ): Query;

    /**
     * @param  string  $adminCode5
     *
     * @return Query
     */
    public function setAdminCode5( string $adminCode5 ): Query;

    /**
     * @param  string  $charset
     *
     * @return Query
     */
    public function setCharset( string $charset ): Query;

    /**
     * @param  string  $cities
     *
     * @return Query
     */
    public function setCities( string $cities ): Query;

    /**
     * @param  string|string[]|null  $continentCode
     *
     * @return Query
     */
    public function setContinentCode( $continentCode ): Query;

    /**
     * @param  string|string[]|null  $country
     *
     * @return Query
     */
    public function setCountry( $country ): Query;

    /**
     * @param  string  $countryBias
     *
     * @return Query
     */
    public function setCountryBias( string $countryBias ): Query;

    /**
     * @param  float  $east
     *
     * @return Query
     */
    public function setEast( float $east ): Query;

    /**
     * @param  string|string[]|null  $featureClass
     *
     * @return Query
     */
    public function setFeatureClass( $featureClass ): Query;

    /**
     * @param  string|string[]|null  $featureCode
     *
     * @return Query
     */
    public function setFeatureCode( $featureCode ): Query;

    /**
     * @param  float  $fuzzy
     *
     * @return Query
     */
    public function setFuzzy( float $fuzzy ): Query;

    /**
     * @param  bool  $inclBbox
     *
     * @return Query
     */
    public function setInclBbox( bool $inclBbox ): Query;

    /**
     * @param  bool  $isNameRequired
     *
     * @return Query
     */
    public function setIsNameRequired( bool $isNameRequired ): Query;

    /**
     * @param  string  $lang
     *
     * @return Query
     */
    public function setLang( string $lang ): Query;

    /**
     * @param  int  $maxRows
     *
     * @return Query
     */
    public function setMaxRows( int $maxRows ): Query;

    /**
     * @param  int  $maxStartRow
     *
     * @return Query
     */
    public function setMaxStartRow( int $maxStartRow ): Query;

    /**
     * @param  string  $name
     *
     * @return Query
     */
    public function setName( string $name ): Query;

    /**
     * @param  string  $name_equals
     *
     * @return Query
     */
    public function setNameEquals( string $name_equals ): Query;

    /**
     * @param  string  $name_startsWith
     *
     * @return Query
     */
    public function setNameStartsWith( string $name_startsWith ): Query;

    /**
     * @param  float  $north
     *
     * @return Query
     */
    public function setNorth( float $north ): Query;

    /**
     * @param  string  $operator
     *
     * @return Query
     */
    public function setOperator( string $operator ): Query;

    /**
     * @param  string  $orderby
     *
     * @return Query
     */
    public function setOrderby( string $orderby ): Query;

    public function setPaged( $page );

    /**
     * @param  string  $q
     *
     * @return Query
     */
    public function setQ( string $q ): Query;

    /**
     * @param  string  $searchTerm
     *
     * @return Query
     */
    public function setSearchTerm( string $searchTerm ): Query;

    /**
     * @param  int|string  $searchType
     *
     * @return Query
     */
    public function setSearchType( $searchType ): Query;

    /**
     * @param  string  $searchLang
     *
     * @return Query
     */
    public function setSearchLang( string $searchLang ): Query;

    /**
     * @param  float  $south
     *
     * @return Query
     */
    public function setSouth( float $south ): Query;

    /**
     * @param  int  $startRow
     *
     * @return Query
     */
    public function setStartRow( int $startRow ): Query;

    /**
     * @param  string  $style
     *
     * @return Query
     */
    public function setStyle( string $style ): Query;

    /**
     * @param  string  $tag
     *
     * @return Query
     */
    public function setTag( string $tag ): Query;


    /**
     * @param  string  $type
     *
     * @return Query
     */
    public function setType( string $type ): Query;


    /**
     * @param  float  $west
     *
     * @return Query
     */
    public function setWest( float $west ): Query;


    /**
     * @param  array|null  $array
     * @param  null        $unset
     *
     * @return array
     * @throws \ErrorException
     */
    public function cleanArray(
        array $array = null,
        $unset = null
    ): array;


    /**
     * @param  int|null  $searchType
     *
     * @return array|false|string[]
     */
    public function toArray( ?int $searchType = null ): array;

}