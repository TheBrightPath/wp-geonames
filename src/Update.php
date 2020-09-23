<?php
/**
 * @noinspection AdditionOperationOnArraysInspection
 * @noinspection SqlResolve
 * @noinspection UnknownInspectionInspection
 */

namespace WPGeonames;

class Update
{

// protected properties

    /** @var \WPGeonames\Update */
    protected static $instance;

    /** @var \WPGeonames\WpDb */
    protected $wpdb;

    /** @var \WPGeonames\Core */
    protected $core;

    /** @var string */
    protected $charset_collate = '';

    /** @var string */
    protected $feature_classes;

    /** @var string */
    protected $feature_codes;

    /** @var string */
    protected $country_codes;

    /** @var string */
    protected $time_zones;

    /** @var string[] */
    protected $updateLog = [];


    public function __construct()
    {

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ); // dbDelta()

        $this->wpdb = Core::$wpdb;
        $this->core = Core::Factory();

        if ( ! empty( $this->wpdb->charset ) )
        {
            $this->charset_collate = "DEFAULT CHARACTER SET {$this->wpdb->charset}";
        }

        if ( ! empty( $this->wpdb->collate ) )
        {
            $this->charset_collate .= " COLLATE {$this->wpdb->collate}";
        }

        $feature_classes       = Core::getFeatureClasses();
        $this->feature_classes = "'" . implode( "','", array_keys( $feature_classes ) ) . "'";

        $feature_codes       = Core::getFeatureCodes();
        $this->feature_codes = "'" . implode( "','", array_keys( $feature_codes ) ) . "'";

        $country_codes       = Core::getCountryCodes();
        $this->country_codes = "'" . implode( "','", array_keys( $country_codes ) ) . "'";

        $time_zones       = Core::getTimeZones();
        $this->time_zones = "'" . implode( "','", array_keys( $time_zones ) ) . "'";

    }


    /**
     * @return array
     */
    public function getUpdateLog( bool $reset = true ): array
    {

        $log = $this->updateLog;

        if ( $reset )
        {
            $this->updateLog = [];
        }

        return $log;
    }


    /**
     * @throws \ErrorException
     */
    public function addData(): void
    {

        // Data
        $this->core->addNoCountries();
        $this->core->addCountries();
        $this->core->addTimezones();

        $sql = <<<SQL
INSERT LOW_PRIORITY INTO
    `%s`
(
    geoname_id,
    country_code,
    name,
    ascii_name,
    population,
    feature_class,
    feature_code
)

SELECT 
    `geoname_id`,
    `iso2`,
    `country`,
    `country`,
    `population`,
    "A",
    "PCL"
FROM
    `%s`

ON DUPLICATE KEY UPDATE 
    ascii_name = country
;
SQL;

        foreach (
            [
                $this->core->getTblLocations(),
                $this->core->getTblCacheLocations(),
            ] as $nom
        )
        {

            $this->wpdb->query( sprintf( $sql, $nom, $this->core->getTblCountries() ) );

        }

    }


    public function createTblCacheQueries(): void
    {

        // locations cache queries
        $nom = $this->core->getTblCacheQueries();

        $searchTypes = implode( "','", ApiQuery::SEARCH_TYPES );

        $sql = <<<SQL
                CREATE TABLE $nom  (
                    `query_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `query_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `query_updated` timestamp NOT NULL ON UPDATE CURRENT_TIMESTAMP,
                    `search_term` varchar(200) NOT NULL,
                    `search_type` enum('$searchTypes') NOT NULL,
                    `search_country` enum($this->country_codes) DEFAULT NULL,
                    `search_params` varchar(500) NOT NULL,
                    `result_count` smallint(6) NOT NULL,
                    `result_total` smallint(6) DEFAULT NULL, 
                INDEX `idx_search` (`search_term`(10), `search_type`, `search_country`)
			) {$this->charset_collate};
SQL;

        $this->updateLog += dbDelta( $sql );

    }


    public function createTblCacheResults(): void
    {

        // locations cache results
        $nom = $this->core->getTblCacheResults();

        $sql = <<<SQL
                CREATE TABLE $nom (
                    `query_id` int(11) NOT NULL AUTO_INCREMENT,
                    `geoname_id` int(11) NOT NULL,
                    `order` smallint(3) unsigned NOT NULL,
                    `score` float DEFAULT NULL,
                    `country_code` enum($this->country_codes) DEFAULT NULL,
                    PRIMARY KEY (`query_id`, `geoname_id`),
                UNIQUE `idx_result` (`query_id`, `order`),
                INDEX `query_id_country_code_order` (`query_id`, `country_code`, `order`)
			) {$this->charset_collate};
SQL;

        $this->updateLog += dbDelta( $sql );

    }


    public function createTblCountries(): void
    {

        // countries
        $nom = $this->core->getTblCountries();

        $sql = <<<SQL
                CREATE TABLE $nom (
                    iso2 char(2) NOT NULL,
                    iso3 char(3) NOT NULL,
                    isoN smallint(3) NOT NULL,
                    fips char(2) DEFAULT NULL,
                    country varchar(200) NOT NULL,
                    capital varchar(200) DEFAULT NULL,
                    area int(11) DEFAULT NULL COMMENT '(in sq km)',
                    population int(11) DEFAULT NULL,
                    continent enum('af','an','as','eu','na','oc','sa') NOT NULL,
                    tld char(5) DEFAULT NULL,
                    currency_code char(3) DEFAULT NULL,
                    currency_name varchar(50) NOT NULL,
                    phone smallint(5) unsigned ,
                    postal_code_format varchar(50) DEFAULT NULL,
                    postal_code_regex varchar(200) DEFAULT NULL,
                    languages varchar(50) DEFAULT NULL,
                    geoname_id int(11) unsigned NOT NULL,
                    neighbours varchar(100) DEFAULT NULL, 
                    equivalent_fips_code mediumint(9) DEFAULT NULL,
                PRIMARY KEY (`geoname_id`),
                UNIQUE KEY `idxIso2` (`iso2`),
                UNIQUE KEY `idxIso3` (`iso3`),
                UNIQUE KEY `idxIsoN` (`isoN`),
                UNIQUE KEY `idxCountry` (country)
			    ) {$this->charset_collate};
SQL;

        $this->updateLog += dbDelta( $sql );

    }


    /**
     * Table columns
     * 0  geonameid        :  integer id of record in geonames database
     * 1  name             :  name of geographical point (utf8) varchar(200)
     * 2  asciiname        :  name of geographical point in plain ascii characters, varchar(200)
     * 3  alternatenames   :  alternatenames, comma separated, ascii names automatically transliterated, convenience
     *                        attribute from alternatename table, varchar(10000)
     * 4  latitude         :  latitude in decimal degrees (wgs84)
     * 5  longitude        :  longitude in decimal degrees (wgs84)
     * 6  feature class    :  see http://www.geonames.org/export/codes.html, char(1)
     * 7  feature code     :  see http://www.geonames.org/export/codes.html, varchar(10)
     * 8  country code     :  ISO-3166 2-letter country code, 2 characters
     * 9  cc2              :  alternate country codes, comma separated, ISO-3166 2-letter country code, 60 characters
     * 10 admin1 code      :  fipscode (subject to change to iso code), see exceptions below, see file admin1Codes.txt
     *                        for display names of this code; varchar(20)
     * 11 admin2 code      :  code for the second administrative division, a county in the US,
     *                        see file admin2Codes.txt; varchar(80)
     * 12 admin3 code      :  code for third level administrative division, varchar(20)
     * 13 admin4 code      :  code for fourth level administrative division, varchar(20)
     * 14 population       :  bigint (8 byte int)
     * 15 elevation        :  in meters, integer
     * 16 dem              :  digital elevation model, srtm3 or gtopo30, average elevation of 3''x3'' (ca 90mx90m) or
     *                        30''x30'' (ca 900mx900m) area in meters, integer. srtm processed by cgiar/ciat.
     * 17 timezone         :  the timezone id (see file timeZone.txt) varchar(40)
     * 18 modification date:  date of last modification in yyyy-MM-dd format
     *
     * @see          http://download.geonames.org/export/dump/readme.txt
     *
     * @noinspection SpellCheckingInspection
     */
    public function createTblLocations(): void
    {

        $sql = <<<SQL
            CREATE TABLE %s (
                `geoname_id` int(11) NOT NULL,
                `name` varchar(200) NOT NULL,
                `ascii_name` varchar(200) NOT NULL,
                `alternate_names` json DEFAULT NULL,
                `latitude` decimal(10,5) DEFAULT NULL,
                `longitude` decimal(10,5) DEFAULT NULL,
                `bbox` json DEFAULT NULL,
                `feature_class` enum($this->feature_classes) NOT NULL,
                `feature_code` enum($this->feature_codes) NOT NULL,
                `country_code` enum($this->country_codes) DEFAULT NULL,
                `country_id` int(11) DEFAULT NULL,
                `cc2` varchar(60) DEFAULT NULL,
                `continent` enum('af','an','as','eu','na','oc','sa') DEFAULT NULL,
                `admin1_code` varchar(20) DEFAULT NULL,
                `admin1_id` int(11) DEFAULT NULL,
                `admin2_code` varchar(80) DEFAULT NULL,
                `admin2_id` int(11) DEFAULT NULL,
                `admin3_code` varchar(20) DEFAULT NULL,
                `admin3_id` int(11) DEFAULT NULL,
                `admin4_code` varchar(20) DEFAULT NULL,
                `admin4_id` int(11) DEFAULT NULL,
                `population` int(20) unsigned DEFAULT NULL,
                `elevation` smallint(6) DEFAULT NULL,
                `dem` smallint(6) DEFAULT NULL,
                `timezone` enum($this->time_zones) DEFAULT NULL,
                `children` json DEFAULT NULL,
                `modification_date` date DEFAULT NULL,
                `db_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`geoname_id`),
            KEY `index1` (`feature_class`,`feature_code`,`country_code`,`cc2`(2),`name`(3)),
            KEY `country_code_admin` (`country_code`,`admin1_code`,`admin2_code`,`admin3_code`,`admin4_code`,`name`(3)),
            KEY `country_id` (`country_id`,`name`(4))
            KEY `admin1_id` (`admin1_id`,`name`(4))
			) {$this->charset_collate};
SQL;

        // locations
        $nom             = $this->core->getTblLocations();
        $this->updateLog += dbDelta( sprintf( $sql, $nom ) );

        // locations cache
        $nom             = $this->core->getTblCacheLocations();
        $this->updateLog += dbDelta( sprintf( $sql, $nom ) );

    }


    public function createTblPostCodes(): void
    {

        // post codes
        $nom = $this->core->getTblPostCodes();

        $sql = "CREATE TABLE " . $nom . " (
			`geoname_id` int(11) unsigned NOT NULL,
			`country_code` varchar(2) NOT NULL,
			`postal_code` varchar(20) NOT NULL,
			`place_name` varchar(180) NOT NULL,
			`admin1_name` varchar(100) DEFAULT NULL,
			`admin1_code` varchar(20) DEFAULT NULL,
			`admin2_name` varchar(100) DEFAULT NULL,
			`admin2_code` varchar(20) DEFAULT NULL,
			`admin3_name` varchar(100) DEFAULT NULL,
			`admin3_code` varchar(20) DEFAULT NULL,
			`latitude` decimal(10,5) DEFAULT NULL,
			`longitude` decimal(10,5) DEFAULT NULL,
			`accuracy` tinyint(1) unsigned DEFAULT NULL,
			PRIMARY KEY (`geoname_id`),
			INDEX `index1` (`country_code`,`postal_code`,`place_name`(3))
			) {$this->charset_collate};";

        $this->updateLog += dbDelta( $sql );

    }


    public function createTblTimeZones(): void
    {

        // time zones
        $nom = $this->core->getTblTimeZones();

        $sql = <<<SQL
                CREATE TABLE $nom (
                    country_code enum($this->country_codes) DEFAULT NULL,
                    time_zone_id varchar(40) NOT NULL,
                    city varchar(40) NOT NULL,
                    caption varchar(40) NOT NULL,
                    php varchar(40) NOT NULL,
                    offsetJan decimal(3,1) COMMENT '(GMT offset 1. Jan 2020)',
                    offsetJul decimal(3,1) COMMENT '(DST offset 1. Jul 2020)',
                    offsetRaw decimal(3,1) COMMENT '(GMT offset independent of DST)',
                PRIMARY KEY (`time_zone_id`),
                KEY `idxCountry` (country_code, city)
			    ) {$this->charset_collate};
SQL;

        $this->updateLog += dbDelta( $sql );

    }


    /**
     * @throws \ErrorException
     */
    public static function Activate(): array
    {

        $result = [
            'success'  => true,
            'messages' => [],
        ];

        $update = self::Factory();

        $update->createTblCountries();
        $update->createTblTimeZones();
        $update->createTblLocations();
        $update->createTblCacheQueries();
        $update->createTblCacheResults();
        $update->createTblPostCodes();
        $update->addData();

        $result['messages'] = $update->getUpdateLog();

        return $result;
    }


    public static function Factory(): self
    {

        return self::$instance
            ?: self::$instance = new self();
    }

}