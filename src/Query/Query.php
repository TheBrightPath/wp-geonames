<?php

namespace WPGeonames\Query;

use WPGeonames\Helpers\FlexibleObjectInterface;

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
interface Query
    extends
    FlexibleObjectInterface
{

// constants
    public const MAX_ROWS                  = 1000;
    public const MAX_START_ROW_FREE        = 5000;
    public const MAX_START_ROW_PREMIUM     = 25000;
    public const QUERY_DEFAULTS
                                           = [
            // the maximal number of rows in the document returned by the service. Default is 100, the maximal allowed value is 1000.
            'maxRows'        => 20,

            // Used for paging results. If you want to get results 30 to 40, use startRow=30 and maxRows=10. Default is 0, the maximal allowed value is 5000 for the free services and 25000 for the premium services
            'startRow'       => 0,

            //At least one of the search term needs to be part of the place name. Example : A normal search for Berlin will return all places within the state of Berlin. If we only want to find places with 'Berlin' in the name we set the parameter isNameRequired to 'true'. The difference to the name_equals parameter is that this will allow searches for 'Berlin, Germany' as only one search term needs to be part of the name.
            'isNameRequired' => true,

            // [population,elevation,relevance]	in combination with the name_startsWith, if set to 'relevance' than the result is sorted by relevance.
            'orderby'        => 'relevance',

            // With the parameter 'fuzzy' the search will find results even if the search terms are incorrectly spelled. Example: http://api.geonames.org/search?q=londoz&fuzzy=0.8&username=demo
            // default is '1', defines the fuzziness of the search terms. float between 0 and 1. The search term is only applied to the name attribute.
            'fuzzy'          => 0.8,

            // default is 'AND', with the operator 'OR' not all search terms need to be matched by the response
            // required for removing irrelevant search parameters
            'operator'       => 'AND',

            // place name and country name will be returned in the specified language. Default is English. Feature classes and codes are only available in English and Bulgarian.
            // string ISO-639 2-letter language code; en,de,fr,it,es,... (optional)
            'lang'           => 'en',

            'search_type' => self::SEARCH_TYPE_EXACT_NAME
                + self::SEARCH_TYPE_FUZZY_NAME
                + self::SEARCH_TYPE_START_OF_NAME
                + self::SEARCH_TYPE_Q,
        ];
    public const SEARCH_NAME_EXACT_NAME    = 'name_equals';
    public const SEARCH_NAME_FUZZY_NAME    = 'name_fuzzy';
    public const SEARCH_NAME_NAME          = 'name';
    public const SEARCH_NAME_Q             = 'q';
    public const SEARCH_NAME_START_OF_NAME = 'name_startsWith';
    public const SEARCH_TYPES
                                           = [
            self::SEARCH_TYPE_Q             => self::SEARCH_NAME_Q,
            self::SEARCH_TYPE_START_OF_NAME => self::SEARCH_NAME_START_OF_NAME,
            self::SEARCH_TYPE_FUZZY_NAME    => self::SEARCH_NAME_FUZZY_NAME,
            self::SEARCH_TYPE_NAME          => self::SEARCH_NAME_NAME,
            self::SEARCH_TYPE_EXACT_NAME    => self::SEARCH_NAME_EXACT_NAME,
        ];
    public const SEARCH_TYPE_EXACT_NAME    = 2 ** 5; // 32
    public const SEARCH_TYPE_FUZZY_NAME    = 2 ** 3; //  8
    public const SEARCH_TYPE_NAME          = 2 ** 4; // 16
    public const SEARCH_TYPE_Q             = 2 ** 1; //  2
    public const SEARCH_TYPE_START_OF_NAME = 2 ** 2; //  4


    /**
     * @return int
     */
    public function getSearchType(): int;


    public function getSearchTypeAsArray( $searchTypeFilter = null );


    /**
     * @return string|null
     */
    public function getSearchTypeName(): ?string;


    /**
     * @param  bool|null  $mode
     *
     * @return string|null
     */
    public function getSearchTypeNames( ?bool $mode = null ): ?string;


    /**
     * @param  bool|null  $mode
     *
     * @return array|null
     */
    public function getSearchTypeNamesAsArray( ?bool $mode = null ): ?array;


    /**
     * @param  int|string  $searchType
     *
     * @return Query
     */
    public function setSearchType( $searchType ): Query;


    /**
     * @param  array|null  $array
     *
     * @return array
     * @throws \ErrorException
     */
    public function cleanArray(
        ?array $array = null
    ): array;

}