<?php

namespace WPGeonames;

/**
 * Class ApiQuery
 *
 * @property $q string      search over all attributes of a place : place name, country name, continent, admin codes,... (Important:urlencoded utf8)
 * @property $name string   place name only(Important:urlencoded utf8)
 * @property $name_equals string         (q,name or name_equals required)    exact place name
 * @property $name_startsWith string (optional)    place name starts with given characters
 *
 */
class ApiQuery
	extends FlexibleObject {

// constants
	const MAX_START_ROW_FREE = 5000;
	const MAX_START_ROW_PREMIUM = 25000;
	const SEARCH_NAME_EXACT_NAME = 'name_equals';
	const SEARCH_NAME_FUZZY_NAME = 'name_fuzzy';
	const SEARCH_NAME_NAME = 'name';
	const SEARCH_NAME_Q = 'q';
	const SEARCH_NAME_START_OF_NAME = 'name_startsWith';
	const SEARCH_TYPES = [
		self::SEARCH_TYPE_Q             => self::SEARCH_NAME_Q,
		self::SEARCH_TYPE_START_OF_NAME => self::SEARCH_NAME_START_OF_NAME,
		self::SEARCH_TYPE_FUZZY_NAME    => self::SEARCH_NAME_FUZZY_NAME,
		self::SEARCH_TYPE_NAME          => self::SEARCH_NAME_NAME,
		self::SEARCH_TYPE_EXACT_NAME    => self::SEARCH_NAME_EXACT_NAME,
	];
	const SEARCH_TYPE_EXACT_NAME = 2 ** 5;
	const SEARCH_TYPE_FUZZY_NAME = 2 ** 3;
	const SEARCH_TYPE_NAME = 2 ** 4;
	const SEARCH_TYPE_Q = 2 ** 1;
	const SEARCH_TYPE_START_OF_NAME = 2 ** 2;
// protected properties
	protected static $aliases = [
		'feature_class'                     => 'featureClass',
		'feature_code'                      => 'featureCode',
		'country_code'                      => 'country',
		's'                                 => 'searchTerm',
		ApiQuery::SEARCH_NAME_Q             => 'q',
		ApiQuery::SEARCH_NAME_NAME          => 'name',
		'nameEquals'                        => 'nameEquals',
		ApiQuery::SEARCH_NAME_EXACT_NAME    => 'nameEquals',
		'nameStartsWith'                    => 'nameStartsWith',
		ApiQuery::SEARCH_NAME_START_OF_NAME => 'nameStartsWith',
	];

	/**
	 * @var string
	 */
	protected $searchTerm = '';

	/**
	 * @var int
	 */
	protected $searchType = self::SEARCH_TYPE_EXACT_NAME + self::SEARCH_TYPE_NAME + self::SEARCH_TYPE_START_OF_NAME;

	/**
	 * @var int (optional)    the maximal number of rows in the document returned by the service. default is 100, the maximal allowed value is 1000.
	 */
	protected $maxRows = 0;

	/**
	 * @var int (optional)    Used for paging results. if you want to get results 30 to 40, use startRow=30 and maxRows=10. default is 0, the maximal allowed value is 5000 for the free services and 25000 for the premium services
	 */
	protected $startRow = 0;

	/**
	 * @var null|string|string[] country code, ISO-3166 (optional)    default is all countries. The country parameter may occur more than once, example: country=FR&country=GP
	 */
	protected $country = null;

	/**
	 * @var string (option), two letter country code ISO-3166    records from the countryBias are listed first
	 */
	protected $countryBias = '';

	/**
	 * @var string : continent code : AF,as,EU,NA,OC,SA,AN (optional)    restricts the search for toponym of the given continent.
	 */
	protected $continentCode = '';

	/**
	 * @var string  admin code (optional)    code of administrative subdivision
	 */
	protected $adminCode1 = '', $adminCode2 = '', $adminCode3 = '', $adminCode4 = '', $adminCode5 = '';

	/**
	 * @var string|string[] character A,H,L,P,R,S,T,U,V (optional)    featureclass(es) (default= all feature classes); this parameter may occur more than once, example: featureClass=P&featureClass=A
	 */
	protected $featureClass;

	/**
	 * @var string (optional)    featurecode(s) (default= all feature codes); this parameter may occur more than once, example: featureCode=PPLC&featureCode=PPLX
	 */
	protected $featureCode = '';

	/**
	 * @var string string (optional)    optional filter parameter with three possible values 'cities1000', 'cities5000','cities15000' used to categorize the populated places into three groups according to size/relevance. See the download readme for further infos
	 */
	protected $cities = '';

	/**
	 * @var string ISO-639 2-letter language code; en,de,fr,it,es,... (optional)    place name and country name will be returned in the specified language. default is English. Feature classes and codes are only available in English and Bulgarian. Any help in translating is welcome.
	 */
	protected $lang = '';

	/**
	 * @var string xml,json,rdf    the format type of the returned document, default = xml
	 */
	protected $type = 'json';

	/**
	 * @var string SHORT,MEDIUM,LONG,FULL (optional)    verbosity of returned xml document, default = MEDIUM
	 */
	protected $style = '';

	/**
	 * @var boolean (optional)    At least one of the search term needs to be part of the place name. Example : A normal search for Berlin will return all places within the state of Berlin. if we only want to find places with 'Berlin' in the name we set the parameter isNameRequired to 'true'. The difference to the name_equals parameter is that this will allow searches for 'Berlin, Germany' as only one search term needs to be part of the name.
	 */
	protected $isNameRequired = true;

	/**
	 * @var string (optional)    search for toponyms tagged with the specified tag
	 */
	protected $tag = '';

	/**
	 * @var string (optional)    default is 'AND', with the operator 'OR' not all search terms need to be matched by the response
	 */
	protected $operator;

	/**
	 * @var string (optional)    default is 'UTF8', defines the encoding used for the document returned by the web service.
	 */
	protected $charset = 'UTF8';

	/**
	 * @var float (optional)    default is '1', defines the fuzziness of the search terms. float between 0 and 1. The search term is only applied to the name attribute.
	 */
	protected $fuzzy = 1;

	/**
	 * @var float (optional)    bounding box, only features within the box are returned
	 */
	protected $east, $west, $north, $south;

	/**
	 * @var string (optional)    in combination with the name parameter, the search will only consider names in the specified language. Used for instance to query for IATA airport codes.
	 */
	protected $searchlang = '';

	/**
	 * @var string (optional)[population,elevation,relevance]    in combination with the name_startsWith, if set to 'relevance' than the result is sorted by relevance.
	 */
	protected $orderby = 'relevance';

	/**
	 * @var bool (option) [true]    include Bbox info, regardelss of style setting. (normally only included with style=FULL
	 */
	protected $inclBbox = false;
// private properties
	/**
	 * @var int
	 */
	private $maxStartRow = self::MAX_START_ROW_FREE;


	/**
	 * wpGeonamesClientQuery constructor.
	 *
	 * @param $query
	 * @param array $defaults
	 */
	public function __construct(
		$query, $defaults = [
		'maxRows'        => 1000,
		// the maximal number of rows in the document returned by the service. Default is 100, the maximal allowed value is 1000.
		'startRow'       => 0,
		// Used for paging results. If you want to get results 30 to 40, use startRow=30 and maxRows=10. Default is 0, the maximal allowed value is 5000 for the free services and 25000 for the premium services
		'isNameRequired' => true,
		//At least one of the search term needs to be part of the place name. Example : A normal search for Berlin will return all places within the state of Berlin. If we only want to find places with 'Berlin' in the name we set the parameter isNameRequired to 'true'. The difference to the name_equals parameter is that this will allow searches for 'Berlin, Germany' as only one search term needs to be part of the name.
		'orderby'        => 'relevance',
		// [population,elevation,relevance]	in combination with the name_startsWith, if set to 'relevance' than the result is sorted by relevance.
		'fuzzy'          => 0.8,
		// default is '1', defines the fuzziness of the search terms. float between 0 and 1. The search term is only applied to the name attribute.
		// With the parameter 'fuzzy' the search will find results even if the search terms are incorrectly spelled. Example: http://api.geonames.org/search?q=londoz&fuzzy=0.8&username=demo
		'operator'       => 'AND',
		// default is 'AND', with the operator 'OR' not all search terms need to be matched by the response
		// required for removing irrelevant search parameters
	]
	) {

		parent::__construct( $query, $defaults );

	}


	/**
	 * @return string
	 */
	public function getAdminCode1(): string {

		return $this->adminCode1;
	}


	/**
	 * @param string $adminCode1
	 *
	 * @return ApiQuery
	 */
	public function setAdminCode1( $adminCode1 ) {

		$this->adminCode1 = $adminCode1;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getAdminCode2(): string {

		return $this->adminCode2;
	}


	/**
	 * @return string
	 */
	public function getAdminCode3(): string {

		return $this->adminCode3;
	}


	/**
	 * @return string
	 */
	public function getAdminCode4(): string {

		return $this->adminCode4;
	}


	/**
	 * @return string
	 */
	public function getAdminCode5(): string {

		return $this->adminCode5;
	}


	/**
	 * @return string
	 */
	public function getCharset(): string {

		return $this->charset;
	}


	/**
	 * @param string $charset
	 *
	 * @return ApiQuery
	 */
	public function setCharset( $charset ) {

		$this->charset = $charset;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getCities(): string {

		return $this->cities;
	}


	/**
	 * @param string $cities
	 *
	 * @return ApiQuery
	 */
	public function setCities( $cities ) {

		$this->cities = $cities;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getContinentCode(): string {

		return $this->continentCode;
	}


	/**
	 * @param string $continentCode
	 *
	 * @return ApiQuery
	 */
	public function setContinentCode( $continentCode ) {

		$this->continentCode = $continentCode;

		return $this;
	}


	/**
	 * @return string|string[]|null
	 */
	public function getCountry() {

		return $this->country;
	}


	/**
	 * @param string|string[]|null $country
	 *
	 * @return ApiQuery
	 */
	public function setCountry( $country ) {

		$this->country = $country;

		return $this;
	}


	public function getCountryAsArray(): array {

		$countries = array_filter( acf_get_array( $this->country, ',' ) );
		sort( $countries );

		return $countries;
	}


	/**
	 * @return string
	 */
	public function getCountryBias(): string {

		return $this->countryBias;
	}


	/**
	 * @param string $countryBias
	 *
	 * @return ApiQuery
	 */
	public function setCountryBias( $countryBias ) {

		$this->countryBias = $countryBias;

		return $this;
	}


	/**
	 * @return float
	 */
	public function getEast(): float {

		return $this->east;
	}


	/**
	 * @param float $east
	 *
	 * @return ApiQuery
	 */
	public function setEast( $east ) {

		$this->east = $east;

		return $this;
	}


	/**
	 * @return string|string[]
	 */
	public function getFeatureClass() {

		return $this->featureClass;
	}


	/**
	 * @param string|string[] $featureClass
	 *
	 * @return ApiQuery
	 */
	public function setFeatureClass( $featureClass ) {

		$this->featureClass = $featureClass;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getFeatureCode(): string {

		return $this->featureCode;
	}


	/**
	 * @param string $featureCode
	 *
	 * @return ApiQuery
	 */
	public function setFeatureCode( $featureCode ) {

		$this->featureCode = $featureCode;

		return $this;
	}


	/**
	 * @return float
	 */
	public function getFuzzy(): float {

		return $this->fuzzy;
	}


	/**
	 * @param float $fuzzy
	 *
	 * @return ApiQuery
	 */
	public function setFuzzy( $fuzzy ) {

		$this->fuzzy = $fuzzy;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getLang(): string {

		return $this->lang;
	}


	/**
	 * @param string $lang
	 *
	 * @return ApiQuery
	 */
	public function setLang( $lang ) {

		$this->lang = $lang;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getMaxRows(): int {

		return $this->maxRows;
	}


	/**
	 * @param int $maxRows
	 *
	 * @return ApiQuery
	 */
	public function setMaxRows( $maxRows ) {

		$this->maxRows = $maxRows;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getMaxStartRow(): int {

		return $this->maxStartRow;
	}


	/**
	 * @param int $maxStartRow
	 *
	 * @return ApiQuery
	 */
	public function setMaxStartRow( int $maxStartRow ): ApiQuery {

		$this->maxStartRow = $maxStartRow;

		return $this;
	}


	/**
	 * @return float
	 */
	public function getNorth(): float {

		return $this->north;
	}


	/**
	 * @return string
	 */
	public function getOperator(): string {

		return $this->operator;
	}


	/**
	 * @param string $operator
	 *
	 * @return ApiQuery
	 */
	public function setOperator( $operator ) {

		$this->operator = strtoupper( $operator );

		return $this;
	}


	/**
	 * @return string
	 */
	public function getOrderby(): string {

		return $this->orderby;
	}


	/**
	 * @param string $orderby
	 *
	 * @return ApiQuery
	 */
	public function setOrderby( $orderby ) {

		$this->orderby = $orderby;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getSearchTerm(): string {

		return $this->searchTerm;
	}


	/**
	 * @param string $searchTerm
	 *
	 * @return ApiQuery
	 */
	public function setSearchTerm( string $searchTerm ): ApiQuery {

		$this->searchTerm = $searchTerm;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getSearchType(): int {

		return $this->searchType;
	}


	/**
	 * @param int $searchType
	 *
	 * @return ApiQuery
	 */
	public function setSearchType( int $searchType ): ApiQuery {

		$this->searchType = $searchType;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getSearchlang(): string {

		return $this->searchlang;
	}


	/**
	 * @param string $searchlang
	 *
	 * @return ApiQuery
	 */
	public function setSearchlang( $searchlang ) {

		$this->searchlang = $searchlang;

		return $this;
	}


	public function getSingleCountry(): ?string {

		$country = array_filter( $this->country );

		if ( is_array( $country ) ) {
			$country = array_filter( $country );
		}

		if ( empty( $country ) ) {
			return null;
		}

		if ( is_array( $country ) && count( $country ) === 1 ) {
			return reset( $country );
		}

		if ( is_string( $country ) ) {
			return $country ?: null;
		}

		return null;

	}


	/**
	 * @return float
	 */
	public function getSouth(): float {

		return $this->south;
	}


	/**
	 * @return int
	 */
	public function getStartRow(): int {

		return $this->startRow;
	}


	/**
	 * @param int $startRow
	 *
	 * @return ApiQuery
	 */
	public function setStartRow( $startRow ) {

		$this->startRow = $startRow;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getStyle(): string {

		return $this->style;
	}


	/**
	 * @param string $style
	 *
	 * @return ApiQuery
	 */
	public function setStyle( $style ) {

		$this->style = $style;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getTag(): string {

		return $this->tag;
	}


	/**
	 * @param string $tag
	 *
	 * @return ApiQuery
	 */
	public function setTag( $tag ) {

		$this->tag = $tag;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getType(): string {

		return $this->type;
	}


	/**
	 * @param string $type
	 *
	 * @return ApiQuery
	 */
	public function setType( $type ) {

		$this->type = $type;

		return $this;
	}


	/**
	 * @return float
	 */
	public function getWest(): float {

		return $this->west;
	}


	/**
	 * @return bool
	 */
	public function isInclBbox(): bool {

		return $this->inclBbox;
	}


	/**
	 * @param bool $inclBbox
	 *
	 * @return ApiQuery
	 */
	public function setInclBbox( $inclBbox ) {

		$this->inclBbox = $inclBbox;

		return $this;
	}


	/**
	 * @return bool
	 */
	public function isNameRequired(): bool {

		return $this->isNameRequired;
	}


	/**
	 * @param bool $isNameRequired
	 *
	 * @return ApiQuery
	 */
	public function setIsNameRequired( $isNameRequired ) {

		$this->isNameRequired = $isNameRequired;

		return $this;
	}


	/**
	 * @param string $adminCode2
	 *
	 * @return ApiQuery
	 */
	public function setAdminCode2( $adminCode2 ) {

		$this->adminCode2 = $adminCode2;

		return $this;
	}


	/**
	 * @param string $adminCode3
	 *
	 * @return ApiQuery
	 */
	public function setAdminCode3( $adminCode3 ) {

		$this->adminCode3 = $adminCode3;

		return $this;
	}


	/**
	 * @param string $adminCode4
	 *
	 * @return ApiQuery
	 */
	public function setAdminCode4( $adminCode4 ) {

		$this->adminCode4 = $adminCode4;

		return $this;
	}


	/**
	 * @param string $adminCode5
	 *
	 * @return ApiQuery
	 */
	public function setAdminCode5( $adminCode5 ) {

		$this->adminCode5 = $adminCode5;

		return $this;
	}


	/**
	 * @param string $name
	 *
	 * @return ApiQuery
	 */
	public function setName( $name ) {

		$this->searchTerm = $name;
		$this->searchType = self::SEARCH_TYPE_NAME;

		return $this;
	}


	/**
	 * @param string $name_equals
	 *
	 * @return ApiQuery
	 */
	public function setNameEquals( $name_equals ) {

		$this->searchTerm = $name_equals;
		$this->searchType = self::SEARCH_TYPE_EXACT_NAME;

		return $this;
	}


	/**
	 * @param string $name_startsWith
	 *
	 * @return ApiQuery
	 */
	public function setNameStartsWith( $name_startsWith ) {

		$this->searchTerm = $name_startsWith;
		$this->searchType = self::SEARCH_TYPE_START_OF_NAME;

		return $this;
	}


	/**
	 * @param float $north
	 *
	 * @return ApiQuery
	 */
	public function setNorth( $north ) {

		$this->north = $north;

		return $this;
	}


	/**
	 * @param string $q
	 *
	 * @return ApiQuery
	 */
	public function setQ( $q ) {

		$this->searchTerm = $q;
		$this->searchType = self::SEARCH_TYPE_Q;

		return $this;
	}


	/**
	 * @param float $south
	 *
	 * @return ApiQuery
	 */
	public function setSouth( $south ) {

		$this->south = $south;

		return $this;
	}


	/**
	 * @param float $west
	 *
	 * @return ApiQuery
	 */
	public function setWest( $west ) {

		$this->west = $west;

		return $this;
	}


	public function cleanArray( $array = null, $unset = null ) {

		$array = parent::cleanArray(
			wp_parse_args(
				$array ?? get_object_vars( $this ),
				[ 'operator' => 'AND' ]
			)
		);

		switch ( $array['operator'] ) {
			case 'OR':
				break;

			case 'AND':
				$searchKeys = [
					ApiQuery::SEARCH_NAME_EXACT_NAME,
					ApiQuery::SEARCH_NAME_START_OF_NAME,
					ApiQuery::SEARCH_NAME_NAME,
					ApiQuery::SEARCH_NAME_Q,
				];

				while ( $searchKey = array_shift( $searchKeys ) ) {

					if ( array_key_exists( $searchKey, $array ) ) {

						foreach ( $searchKeys as $search_key ) {
							unset ( $array[ $search_key ] );
						}

						break;

					}

				}

				unset ( $searchKeys );

				break;

			default:
				_e( 'Unknown query operator.' );
				die( $array['operator'] );
		}

		if ( $unset !== null ) {

			if ( ! is_array( $unset ) ) {
				$unset = [
					'maxRows',
					'startRow',
					'fuzzy',
					'charset',
					'formatted',
					'type',
					'isNameRequired',
					'inclBbox',
					ApiQuery::SEARCH_NAME_EXACT_NAME,
					ApiQuery::SEARCH_NAME_START_OF_NAME,
					ApiQuery::SEARCH_NAME_NAME,
					ApiQuery::SEARCH_NAME_Q,
				];
			}

			foreach ( $unset as $param ) {

				unset( $array[ $param ] );

			}

		}

		return $array;

	}


	public function query() {

		$searchTypes = [
			self::SEARCH_TYPE_Q,
			self::SEARCH_TYPE_START_OF_NAME,
			self::SEARCH_TYPE_NAME,
			self::SEARCH_TYPE_EXACT_NAME
		];
		$results     = [];
		$g           = Core::getGeoNameClient();
		$apiResult   = null;

		rsort( $searchTypes, SORT_NUMERIC );

		foreach ( $searchTypes as $searchType ) {

			if ( ( $this->searchType & $searchType ) === 0 ) {
				continue;
			}

			if ( ( $params = $this->toArray( $searchType ) ) === false ) {
				continue;
			}

			$search_type = key( $params );

			$params = wp_parse_args(
				[
					'maxRows'  => 1000,
					'startRow' => 0,
				],
				$params
			);


			$apiResult = new ApiQueryStatus(
				$searchType,
				$this,
				$params,
				$results,
				$apiResult ? $apiResult->processRecords : null
			);

			$params = apply_filters( "geonames/api/params/type=$searchType", $params, $apiResult );
			$params = apply_filters( "geonames/api/params/name=$search_type", $params, $apiResult );
			$params = apply_filters( "geonames/api/params", $params, $apiResult );

			if ( $params === null || $params['startRow'] >= $this->maxStartRow ) {
				continue;
			}

			do {

				$result             = $g->search( $params );
				$count              = count( $result );
				$apiResult->total   = $g->getLastTotalResultsCount();
				$apiResult->result  += Location::parseArray( $result, 'geonameId', '_' );
				$params['startRow'] += $count;

				unset ( $result );

			} while ( $count === $params['maxRows'] && $params['startRow'] < $this->maxStartRow );

			unset ( $count );

			$apiResult = apply_filters( "geonames/api/result", $apiResult );
			$apiResult = apply_filters( "geonames/api/result/type=$searchType", $apiResult );

			if ( ! empty( $apiResult->result ) ) {
				$results += $apiResult->result;
			}

		}

		return $results;

	}


	/**
	 * @param int|null $searchType
	 *
	 * @return array|false|string[]
	 */
	public function toArray( $searchType = null ) {

		$params = parent::toArray();

		if ( $searchType === null ) {
			return $params;
		}

		$search_type = null;

		unset( $params['searchType'], $params['searchTerm'] );

		switch ( $searchType ) {

			case self::SEARCH_TYPE_Q:
				$search_type = ApiQuery::SEARCH_NAME_Q;
				break;

			case self::SEARCH_TYPE_START_OF_NAME:
				$search_type = ApiQuery::SEARCH_NAME_START_OF_NAME;
				unset( $params['fuzzy'] );
				break;

			/** @noinspection PhpMissingBreakStatementInspection */
			case self::SEARCH_TYPE_NAME:
				unset( $params['fuzzy'] );
			// continue with next

			case self::SEARCH_TYPE_FUZZY_NAME:
				$search_type = ApiQuery::SEARCH_NAME_NAME;
				break;

			case self::SEARCH_TYPE_EXACT_NAME:
				$search_type = ApiQuery::SEARCH_NAME_EXACT_NAME;
				unset( $params['fuzzy'] );
				break;

		}

		if ( $search_type === null ) {
			return false;
		}

		return [ $search_type => $this->searchTerm ] + $params;

	}


	/**
	 * @return string[]
	 */
	public static function getAliases(): array {

		return self::$aliases;
	}


	public static function translateSearchType( $searchType ) {

		if ( $searchType === null ) {
			return null;
		}

		if ( is_string( $searchType ) ) {
			return array_flip( self::SEARCH_TYPES )[ $searchType ] ?? null;
		}

		if ( is_numeric( $searchType ) ) {
			return self::SEARCH_TYPES[ $searchType ] ?? null;
		}

		return false;
	}

}