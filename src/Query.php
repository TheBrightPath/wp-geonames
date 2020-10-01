<?php
/**
 * @noinspection SpellCheckingInspection
 */

namespace WPGeonames;

use ErrorException;
use WPGeonames\Entities\Location;
use WPGeonames\Query\ChildQueryInterface;
use WPGeonames\Query\ChildQueryTrait;
use WPGeonames\Query\QueryTrait;
use WPGeonames\Query\Status;

/**
 * Class ApiQuery
 *
 * @property $q               string      search over all attributes of a place : place name, country name, continent,
 *           admin codes,... (Important:urlencoded utf8)
 * @property $name            string   place name only(Important:urlencoded utf8)
 * @property $name_equals     string         (q,name or name_equals required)    exact place name
 * @property $name_startsWith string (optional)    place name starts with given characters
 *
 */
class Query
    implements
    QueryInterface
{

    use QueryTrait
    {
        QueryTrait::__construct as protected _QueryTrait__construct;
        QueryTrait::cleanArray as protected _QueryTrait__cleanArray;
        QueryTrait::toArray as protected _QueryTrait__toArray;
    }

//  public properties

    /**
     * @var \WPGeonames\Query\DB\MainQuery
     */
    public static $_dbExecutorType = Query\DB\MainQuery::class;

    /**
     * @var \WPGeonames\Query\Executor
     */
    public static $_apiExecutorType = Query\Executor::class;

// protected properties

    protected static $_aliases
        = [
            'feature_class'                        => 'featureClass',
            'feature_code'                         => 'featureCode',
            'country_code'                         => 'country',
            'search_countries'                     => 'country',
            's'                                    => 'searchTerm',
            'search_term'                          => 'searchTerm',
            'search_type'                          => 'searchType',
            Query\Query::SEARCH_NAME_Q             => 'q',
            Query\Query::SEARCH_NAME_NAME          => 'name',
            'nameEquals'                           => 'nameEquals',
            Query\Query::SEARCH_NAME_EXACT_NAME    => 'nameEquals',
            'nameStartsWith'                       => 'nameStartsWith',
            Query\Query::SEARCH_NAME_START_OF_NAME => 'nameStartsWith',
        ];

    /**
     * @var bool
     */
    protected $_useCache = true;

    /**
     * @var string
     */
    protected $searchTerm = '';

    /** @var ChildQueryTrait|string */
    protected $_subQueryType = ChildQueryTrait::class;

    /**
     * @var int
     */
    protected $searchType = 0;

    /**
     * @var int (optional)    the maximal number of rows in the document returned by the service. default is 100, the
     *      maximal allowed value is 1000.
     */
    protected $maxRows = 0;

    /**
     * @var int (optional)    Used for paging results. if you want to get results 30 to 40, use startRow=30 and
     *      maxRows=10. default is 0, the maximal allowed value is 5000 for the free services and 25000 for the premium
     *      services
     */
    protected $startRow = 0;

    /**
     * @var null|string|string[] country code, ISO-3166 (optional)    default is all countries. The country parameter
     *      may occur more than once, example: country=FR&country=GP
     */
    protected $country;

    /**
     * @var string (option), two letter country code ISO-3166    records from the countryBias are listed first
     */
    protected $countryBias = '';

    /**
     * @var string : continent code : AF,as,EU,NA,OC,SA,AN (optional)    restricts the search for toponym of the given
     *      continent.
     */
    protected $continentCode = '';

    /**
     * @var string  admin code (optional)    code of administrative subdivision
     */
    protected $adminCode1 = '', $adminCode2 = '', $adminCode3 = '', $adminCode4 = '', $adminCode5 = '';

    /**
     * @var string|string[] character A,H,L,P,R,S,T,U,V (optional)    featureclass(es) (default= all feature classes);
     *      this parameter may occur more than once, example: featureClass=P&featureClass=A
     */
    protected $featureClass;

    /**
     * @var string (optional)    featurecode(s) (default= all feature codes); this parameter may occur more than once,
     *      example: featureCode=PPLC&featureCode=PPLX
     */
    protected $featureCode = '';

    /**
     * @var string string (optional)    optional filter parameter with three possible values 'cities1000',
     *      'cities5000','cities15000' used to categorize the populated places into three groups according to
     *      size/relevance. See the download readme for further infos
     */
    protected $cities = '';

    /**
     * @var string ISO-639 2-letter language code; en,de,fr,it,es,... (optional)    place name and country name will be
     *      returned in the specified language. default is English. Feature classes and codes are only available in
     *      English and Bulgarian. Any help in translating is welcome.
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
     * @var boolean (optional)    At least one of the search term needs to be part of the place name. Example : A
     *      normal search for Berlin will return all places within the state of Berlin. if we only want to find places
     *      with 'Berlin' in the name we set the parameter isNameRequired to 'true'. The difference to the name_equals
     *      parameter is that this will allow searches for 'Berlin, Germany' as only one search term needs to be part
     *      of the name.
     */
    protected $isNameRequired = true;

    /**
     * @var string (optional)    search for toponyms tagged with the specified tag
     */
    protected $tag = '';

    /**
     * @var string (optional)    default is 'AND', with the operator 'OR' not all search terms need to be matched by
     *      the response
     */
    protected $operator;

    /**
     * @var string (optional)    default is 'UTF8', defines the encoding used for the document returned by the web
     *      service.
     */
    protected $charset = 'UTF8';

    /**
     * @var float (optional)    default is '1', defines the fuzziness of the search terms. float between 0 and 1. The
     *      search term is only applied to the name attribute.
     */
    protected $fuzzy = 1;

    /**
     * @var float (optional)    bounding box, only features within the box are returned
     */
    protected $east, $west, $north, $south;

    /**
     * @var string (optional)    in combination with the name parameter, the search will only consider names in the
     *      specified language. Used for instance to query for IATA airport codes.
     */
    protected $searchlang = '';

    /**
     * @var string (optional)[population,elevation,relevance]    in combination with the name_startsWith, if set to
     *      'relevance' than the result is sorted by relevance.
     */
    protected $orderby = 'relevance';

    /**
     * @var bool (option) [true]    include Bbox info, regardelss of style setting. (normally only included with
     *      style=FULL
     */
    protected $inclBbox = false;

    // private properties
    /**
     * @var int
     */
    private $maxStartRow = self::MAX_START_ROW_FREE;

    /**
     * @var int|null
     */
    private $page;


    /**
     * WpGeonames\ApiQuery constructor.
     *
     * @param         $values
     * @param  array  $defaults
     */
    public function __construct(
        $values,
        $defaults = Query::QUERY_DEFAULTS
    ) {

        $this->_QueryTrait__construct( $values, wp_parse_args( $defaults, Query\Query::QUERY_DEFAULTS ) );

    }


    /**
     * @return string
     */
    public function getAdminCode1(): string
    {

        return $this->adminCode1;
    }


    /**
     * @param  string  $adminCode1
     *
     * @return \WPGeonames\Query\Query
     */
    public function setAdminCode1( string $adminCode1 ): Query\Query
    {

        $this->adminCode1 = $adminCode1;

        return $this;
    }


    /**
     * @return string
     */
    public function getAdminCode2(): string
    {

        return $this->adminCode2;
    }


    /**
     * @return string
     */
    public function getAdminCode3(): string
    {

        return $this->adminCode3;
    }


    /**
     * @return string
     */
    public function getAdminCode4(): string
    {

        return $this->adminCode4;
    }


    /**
     * @return string
     */
    public function getAdminCode5(): string
    {

        return $this->adminCode5;
    }


    /**
     * @return string
     */
    public function getCharset(): string
    {

        return $this->charset;
    }


    /**
     * @param  string  $charset
     *
     * @return \WPGeonames\Query\Query
     */
    public function setCharset( string $charset ): Query\Query
    {

        $this->charset = $charset;

        return $this;
    }


    /**
     * @return string
     */
    public function getCities(): string
    {

        return $this->cities;
    }


    /**
     * @param  string  $cities
     *
     * @return \WPGeonames\Query\Query
     */
    public function setCities( string $cities ): Query\Query
    {

        $this->cities = $cities;

        return $this;
    }


    /**
     * @return string|string[]|null
     */
    public function getContinentCode()
    {

        return $this->continentCode;
    }


    /**
     * @param  string|string[]|null  $continentCode
     *
     * @return \WPGeonames\Query\Query
     */
    public function setContinentCode( $continentCode ): Query\Query
    {

        $this->continentCode = $continentCode;

        return $this;
    }


    /**
     * @return string|string[]|null
     */
    public function getCountry()
    {

        return $this->country;
    }


    /**
     * @param  string|string[]|null  $country
     *
     * @return \WPGeonames\Query\Query
     */
    public function setCountry( $country ): Query\Query
    {

        $this->country = $country;

        if ( is_array( $this->country ) )
        {
            sort( $this->country, SORT_STRING | SORT_NATURAL );
        }

        return $this;
    }


    public function getCountryAsArray( bool $returnNullIfEmpty = false ): ?array
    {

        $countries = array_filter( acf_get_array( $this->country, ',' ) );
        sort( $countries, SORT_STRING | SORT_FLAG_CASE );

        return $returnNullIfEmpty && empty( $countries )
            ? null
            : $countries;
    }


    public function getCountryAsJson( bool $returnNullIfEmpty = false ): ?string
    {

        $countries = $this->getCountryAsArray( $returnNullIfEmpty );

        return $returnNullIfEmpty && $countries === null
            ? null
            : \GuzzleHttp\json_encode( $countries );
    }


    /**
     * @return string
     */
    public function getCountryBias(): string
    {

        return $this->countryBias;
    }


    /**
     * @param  string  $countryBias
     *
     * @return \WPGeonames\Query\Query
     */
    public function setCountryBias( string $countryBias ): Query\Query
    {

        $this->countryBias = $countryBias;

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
     * @return \WPGeonames\Query\Query
     */
    public function setEast( float $east ): Query\Query
    {

        $this->east = $east;

        return $this;
    }


    /**
     * @return string[]|null
     */
    public function getFeatureClass(): ?array
    {

        return $this->featureClass;
    }


    /**
     * @param  string|string[]|null  $featureClass
     *
     * @return \WPGeonames\Query\Query
     */
    public function setFeatureClass( $featureClass ): Query\Query
    {

        return $this->setArrayProperty( $this->featureClass, $featureClass );
    }


    /**
     * @return string|string[]|null
     */
    public function getFeatureCode()
    {

        return $this->featureCode;
    }


    /**
     * @param  string|string[]|null  $featureCode
     *
     * @return \WPGeonames\Query\Query
     */
    public function setFeatureCode( $featureCode ): Query\Query
    {

        return $this->setArrayProperty( $this->featureCode, $featureCode );
    }


    /**
     * @return float
     */
    public function getFuzzy(): float
    {

        return $this->fuzzy;
    }


    /**
     * @param  float  $fuzzy
     *
     * @return \WPGeonames\Query\Query
     */
    public function setFuzzy( float $fuzzy ): Query\Query
    {

        $this->fuzzy = $fuzzy;

        return $this;
    }


    /**
     * @return string
     */
    public function getLang(): string
    {

        return $this->lang;
    }


    /**
     * @param  string  $lang
     *
     * @return \WPGeonames\Query\Query
     */
    public function setLang( string $lang ): Query\Query
    {

        $this->lang = $lang;

        return $this;
    }


    /**
     * @return int
     */
    public function getMaxRows(): int
    {

        return $this->maxRows;
    }


    /**
     * @param  int  $maxRows
     *
     * @return \WPGeonames\Query\Query
     */
    public function setMaxRows( int $maxRows ): Query\Query
    {

        $this->maxRows = $maxRows;

        return $this;
    }


    /**
     * @return int
     */
    public function getMaxStartRow(): int
    {

        return $this->maxStartRow;
    }


    /**
     * @param  int  $maxStartRow
     *
     * @return \WPGeonames\Query\Query
     */
    public function setMaxStartRow( int $maxStartRow ): Query\Query
    {

        $this->maxStartRow = $maxStartRow;

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
     * @return string
     */
    public function getOperator(): string
    {

        return $this->operator;
    }


    /**
     * @param  string  $operator
     *
     * @return \WPGeonames\Query\Query
     */
    public function setOperator( string $operator ): Query\Query
    {

        $this->operator = strtoupper( $operator );

        return $this;
    }


    /**
     * @return string
     */
    public function getOrderby(): string
    {

        return $this->orderby;
    }


    /**
     * @param  string  $orderby
     *
     * @return \WPGeonames\Query\Query
     */
    public function setOrderby( string $orderby ): Query\Query
    {

        $this->orderby = $orderby;

        return $this;
    }


    /**
     * @return string
     */
    public function getSearchLang(): string
    {

        return $this->searchlang;
    }


    /**
     * @param  string  $searchLang
     *
     * @return \WPGeonames\Query\Query
     */
    public function setSearchLang( string $searchLang ): Query\Query
    {

        $this->searchlang = $searchLang;

        return $this;
    }


    public function &getSearchParamsAsArray( ?string &$searchTerm = null ): array
    {

        // get search parameters (unfiltered)
        $myParams   = $this->toArray( - 1 );
        $searchTerm = mb_strtolower( $myParams['searchTerm'] );

        // remove "technical" parameters plus searchType and searchTerm which are stored individually
        $myParams = array_diff_key(
            $myParams,
            [
                'inclBbox'    => true,
                'maxStartRow' => true,
                'maxRows'     => true,
                'page'        => true,
                'searchTerm'  => true,
                'searchType'  => true,
                'startRow'    => true,
                'type'        => true,
            ]
        );

        // remove defaults
        $myParams = array_diff_assoc(
            $myParams,
            [
                'charset'  => 'UTF8',
                'operator' => 'and',
                'orderby'  => 'relevance',
            ]
        );

        ksort( $myParams, SORT_FLAG_CASE | SORT_NATURAL );

        foreach ( [ 'country' ] as $key )
        {
            if ( is_array( $myParams[ $key ] ?? null ) )
            {
                sort( $myParams[ $key ], SORT_FLAG_CASE | SORT_NATURAL );
            }
        }

        return $myParams;
    }


    public function getSearchParamsAsJson(
        ?string &$searchTerm = null,
        array &$myParams = []
    ): string {

        $myParams = $this->getSearchParamsAsArray( $searchTerm );

        return \GuzzleHttp\json_encode( $myParams );
    }


    /**
     * @return string
     */
    public function getSearchTerm(): string
    {

        return $this->searchTerm;
    }


    /**
     * @param  string  $searchTerm
     *
     * @return \WPGeonames\Query\Query
     */
    public function setSearchTerm( string $searchTerm ): Query\Query
    {

        $this->searchTerm = trim( $searchTerm );

        return $this;
    }


    /**
     * @return int
     */
    public function getSearchType(): int
    {

        return $this->searchType ?? 0;
    }


    /**
     * @param $searchType
     *
     * @return \WPGeonames\Query\Query
     * @throws \ErrorException
     */
    public function setSearchType( $searchType ): Query\Query
    {

        return $this->_setSearchType( $searchType );
    }


    public function getSingleCountry(): ?string
    {

        $country = is_array( $this->country )
            ? array_filter( $this->country )
            : $this->country;

        if ( empty( $country ) )
        {
            return null;
        }

        if ( is_array( $country ) && count( $country ) === 1 )
        {
            return reset( $country );
        }

        if ( is_string( $country ) )
        {
            return $country
                ?: null;
        }

        return null;

    }


    /**
     * @return float
     */
    public function getSouth(): float
    {

        return $this->south;
    }


    /**
     * @return int
     */
    public function getStartRow(): int
    {

        return $this->startRow;
    }


    /**
     * @param  int  $startRow
     *
     * @return \WPGeonames\Query\Query
     */
    public function setStartRow( int $startRow ): Query\Query
    {

        if ( $this->page === null )
        {
            $this->startRow = $startRow;
        }

        return $this;
    }


    /**
     * @return string
     */
    public function getStyle(): string
    {

        return $this->style;
    }


    /**
     * @param  string  $style
     *
     * @return \WPGeonames\Query\Query
     */
    public function setStyle( string $style ): Query\Query
    {

        $this->style = $style;

        return $this;
    }


    /**
     * @return string
     */
    public function getTag(): string
    {

        return $this->tag;
    }


    /**
     * @param  string  $tag
     *
     * @return \WPGeonames\Query\Query
     */
    public function setTag( string $tag ): Query\Query
    {

        $this->tag = $tag;

        return $this;
    }


    /**
     * @return string
     */
    public function getType(): string
    {

        return $this->type;
    }


    /**
     * @param  string  $type
     *
     * @return \WPGeonames\Query\Query
     */
    public function setType( string $type ): Query\Query
    {

        $this->type = $type;

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
     * @return bool
     */
    public function isInclBbox(): bool
    {

        return $this->inclBbox;
    }


    /**
     * @param  bool  $inclBbox
     *
     * @return \WPGeonames\Query\Query
     */
    public function setInclBbox( bool $inclBbox ): Query\Query
    {

        $this->inclBbox = $inclBbox;

        return $this;
    }


    /**
     * @return bool
     */
    public function isNameRequired(): bool
    {

        return $this->isNameRequired;
    }


    /**
     * @param  bool  $isNameRequired
     *
     * @return \WPGeonames\Query\Query
     */
    public function setIsNameRequired( bool $isNameRequired ): Query\Query
    {

        $this->isNameRequired = $isNameRequired;

        return $this;
    }


    /**
     * @param  string  $adminCode2
     *
     * @return \WPGeonames\Query\Query
     */
    public function setAdminCode2( string $adminCode2 ): Query\Query
    {

        $this->adminCode2 = $adminCode2;

        return $this;
    }


    /**
     * @param  string  $adminCode3
     *
     * @return \WPGeonames\Query\Query
     */
    public function setAdminCode3( string $adminCode3 ): Query\Query
    {

        $this->adminCode3 = $adminCode3;

        return $this;
    }


    /**
     * @param  string  $adminCode4
     *
     * @return \WPGeonames\Query\Query
     */
    public function setAdminCode4( string $adminCode4 ): Query\Query
    {

        $this->adminCode4 = $adminCode4;

        return $this;
    }


    /**
     * @param  string  $adminCode5
     *
     * @return \WPGeonames\Query\Query
     */
    public function setAdminCode5( string $adminCode5 ): Query\Query
    {

        $this->adminCode5 = $adminCode5;

        return $this;
    }


    /**
     * @param $property
     * @param $array
     *
     * @return \WPGeonames\Query\Query
     */
    protected function setArrayProperty(
        &$property,
        &$array
    ): Query\Query {

        if ( is_array( $array ) )
        {
            $property = $array;
        }
        else
        {
            $property = $array
                ? (array) $array
                : null;
        }

        if ( $property !== null )
        {
            sort( $property );
        }

        return $this;
    }


    /**
     * @param  string  $name
     *
     * @return \WPGeonames\Query\Query
     */
    public function setName( string $name ): Query\Query
    {

        $this->searchTerm = $name;
        $this->searchType = self::SEARCH_TYPE_NAME;

        return $this;
    }


    /**
     * @param  string  $name_equals
     *
     * @return \WPGeonames\Query\Query
     */
    public function setNameEquals( string $name_equals ): Query\Query
    {

        $this->searchTerm = $name_equals;
        $this->searchType = self::SEARCH_TYPE_EXACT_NAME;

        return $this;
    }


    /**
     * @param  string  $name_startsWith
     *
     * @return \WPGeonames\Query\Query
     */
    public function setNameStartsWith( string $name_startsWith ): Query\Query
    {

        $this->searchTerm = $name_startsWith;
        $this->searchType = self::SEARCH_TYPE_START_OF_NAME;

        return $this;
    }


    /**
     * @param  float  $north
     *
     * @return \WPGeonames\Query\Query
     */
    public function setNorth( float $north ): Query\Query
    {

        $this->north = $north;

        return $this;
    }


    public function setPaged( $page ): Query
    {

        // reset page status
        $this->page = null;

        if ( $page === null || ! is_numeric( $page ) )
        {
            // ignore invalid values
            return $this;
        }

        $page = (int) $page;

        if ( $page <= 0 )
        {
            // return as much as we can
            return $this->setStartRow( 0 )
                        ->setMaxRows( self::MAX_ROWS )
                ;
        }

        // calculate the first to-be-returned row
        $start = ( $page - 1 ) * $this->getMaxRows();

        $this->setStartRow( $start );

        // remember the page, also to avoid overwriting the $startRow
        $this->page = $page;

        return $this;
    }


    /**
     * @param  string  $q
     *
     * @return \WPGeonames\Query\Query
     */
    public function setQ( string $q ): Query\Query
    {

        $this->searchTerm = $q;
        $this->searchType = self::SEARCH_TYPE_Q;

        return $this;
    }


    /**
     * @param  float  $south
     *
     * @return \WPGeonames\Query\Query
     */
    public function setSouth( float $south ): Query\Query
    {

        $this->south = $south;

        return $this;
    }


    /**
     * @param  float  $west
     *
     * @return \WPGeonames\Query\Query
     */
    public function setWest( float $west ): Query\Query
    {

        $this->west = $west;

        return $this;
    }


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
    ): array {

        $array = $this->_QueryTrait__cleanArray(
            wp_parse_args(
                $array ?? $this->toArray( - 1 ),
                [ 'operator' => 'AND' ]
            )
        );

        switch ( $array['operator'] )
        {
        case 'OR':
            break;

        case 'AND':
            $searchKeys = [
                Query\Query::SEARCH_NAME_EXACT_NAME,
                Query\Query::SEARCH_NAME_START_OF_NAME,
                Query\Query::SEARCH_NAME_NAME,
                Query\Query::SEARCH_NAME_Q,
            ];

            while ( $searchKey = array_shift( $searchKeys ) )
            {

                if ( array_key_exists( $searchKey, $array ) )
                {

                    foreach ( $searchKeys as $search_key )
                    {
                        unset ( $array[ $search_key ] );
                    }

                    break;

                }

            }

            unset ( $searchKeys );

            break;

        default:
            throw new ErrorException( __( 'Unknown query operator: ' ) . $array['operator'] );
        }

        if ( $unset !== null )
        {

            if ( ! is_array( $unset ) )
            {
                $unset = [
                    'maxRows',
                    'startRow',
                    'fuzzy',
                    'charset',
                    'formatted',
                    'type',
                    'isNameRequired',
                    'inclBbox',
                    Query\Query::SEARCH_NAME_EXACT_NAME,
                    Query\Query::SEARCH_NAME_START_OF_NAME,
                    Query\Query::SEARCH_NAME_NAME,
                    Query\Query::SEARCH_NAME_Q,
                ];
            }

            foreach ( $unset as $param )
            {

                unset( $array[ $param ] );

            }

        }

        return $array;

    }


    /**
     * @param  int|null  $searchType
     *
     * @return \WPGeonames\Query\ChildQueryInterface
     */
    public function createSubQuery( ?int $searchType = null ): ChildQueryInterface
    {

        /**
         * @var \WPGeonames\Query\Executor $executor
         */

        $executor = $this->_useCache
            ? static::$_dbExecutorType
            : static::$_apiExecutorType;

        return new $executor( $this );
    }


    public function query( $output = Location::class ): Status
    {

        return $this->createSubQuery()
                    ->query( $output )
            ;

    }


    /**
     * @param  int|null  $searchType
     *
     * @return array|false|string[]
     * @noinspection UnsetConstructsCanBeMergedInspection
     */
    public function toArray( ?int $searchType = null ): array
    {

        $params = $this->_QueryTrait__toArray();

        $params['operator'] = strtolower( $params['operator'] );

        if ( $searchType === - 1 )
        {
            return $params;
        }

        $searchParameter = null;

        unset( $params['searchType'] );
        unset( $params['searchTerm'] );

        switch ( $searchType ?? $this->searchType )
        {

        case self::SEARCH_TYPE_Q:
            $searchParameter = self::SEARCH_NAME_Q;
            break;

        case self::SEARCH_TYPE_START_OF_NAME:
            $searchParameter = self::SEARCH_NAME_START_OF_NAME;
            unset( $params['fuzzy'] );
            break;

            /** @noinspection PhpMissingBreakStatementInspection */
        case self::SEARCH_TYPE_NAME:
            unset( $params['fuzzy'] );
            // continue with next

        case self::SEARCH_TYPE_FUZZY_NAME:
            $searchParameter = self::SEARCH_NAME_NAME;
            break;

        case self::SEARCH_TYPE_EXACT_NAME:
            $searchParameter = self::SEARCH_NAME_EXACT_NAME;
            unset( $params['fuzzy'] );
            break;

        }

        if ( $searchParameter === null )
        {
            return false;
        }

        return [ $searchParameter => $this->searchTerm ] + $params;

    }


    /**
     * @return string[]
     */
    public static function getAliases(): array
    {

        return self::$_aliases;
    }

}
