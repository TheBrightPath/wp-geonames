<?php

namespace WPGeonames;

class Update
{
    /**
     * @throws \ErrorException
     * @noinspection SpellCheckingInspection
     */
    public function creation_table(): void
    {

        /*
		****** http://download.geonames.org/export/dump/readme.txt *********
		0  geonameid		: integer id of record in geonames database
		1  name			: name of geographical point (utf8) varchar(200)
		2  asciiname			: name of geographical point in plain ascii characters, varchar(200)
		3  alternatenames	: alternatenames, comma separated, ascii names automatically transliterated, convenience attribute from alternatename table, varchar(10000)
		4  latitude			: latitude in decimal degrees (wgs84)
		5  longitude			: longitude in decimal degrees (wgs84)
		6  feature class		: see http://www.geonames.org/export/codes.html, char(1)
		7  feature code		: see http://www.geonames.org/export/codes.html, varchar(10)
		8  country code		: ISO-3166 2-letter country code, 2 characters
		9  cc2				: alternate country codes, comma separated, ISO-3166 2-letter country code, 60 characters
		10 admin1 code		: fipscode (subject to change to iso code), see exceptions below, see file admin1Codes.txt for display names of this code; varchar(20)
		11 admin2 code		: code for the second administrative division, a county in the US, see file admin2Codes.txt; varchar(80)
		12 admin3 code		: code for third level administrative division, varchar(20)
		13 admin4 code		: code for fourth level administrative division, varchar(20)
		14 population		: bigint (8 byte int)
		15 elevation			: in meters, integer
		16 dem				: digital elevation model, srtm3 or gtopo30, average elevation of 3''x3'' (ca 90mx90m) or 30''x30'' (ca 900mx900m) area in meters, integer. srtm processed by cgiar/ciat.
		17 timezone			: the timezone id (see file timeZone.txt) varchar(40)
		18 modification date	: date of last modification in yyyy-MM-dd format
		*/
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); // dbDelta()

        $wpdb = self::$wpdb;

        $this->inActivation = true;

        //
        $charset_collate = '';

        if (!empty($wpdb->charset))
        {
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        }

        if (!empty($wpdb->collate))
        {
            $charset_collate .= " COLLATE $wpdb->collate";
        }

        $feature_classes = self::getFeatureClasses();
        $feature_classes = "'" . implode("', '", array_keys($feature_classes)) . "'";

        $feature_codes = self::getFeatureCodes();
        $feature_codes = "'" . implode("', '", array_keys($feature_codes)) . "'";

        $country_codes = self::getCountryCodes();
        $country_codes = "'" . implode("', '", array_keys($country_codes)) . "'";

        $time_zones = self::getTimeZones();
        $time_zones = "'" . implode("', '", $time_zones) . "'";

        // countries
        $nom = $this->tblCountries;

        if ($wpdb->get_var("SHOW TABLES LIKE '$nom'") !== $nom)
        {
            $sql = <<<SQL
                CREATE TABLE $nom (
                    iso2 CHAR(2) NOT NULL,
                    iso3 CHAR(3) NOT NULL,
                    isoN SMALLINT(3) NOT NULL,
                    fips CHAR(2) DEFAULT NULL,
                    country varchar(200) NOT NULL,
                    capital varchar(200) DEFAULT NULL,
                    area int(11) DEFAULT NULL COMMENT '(in sq km)',
                    population int(11) DEFAULT NULL,
                    continent enum('af','an','as','eu','na','oc','sa') NOT NULL,
                    tld CHAR(5) DEFAULT NULL,
                    currency_code char(3) DEFAULT NULL,
                    currency_name varchar(50) NOT NULL,
                    phone smallint unsigned ,
                    postal_code_format varchar(50) DEFAULT NULL,
                    postal_code_regex varchar(200) DEFAULT NULL,
                    languages varchar(50) DEFAULT NULL,
                    geoname_id int(11) unsigned NOT NULL,
                    neighbours varchar(100) DEFAULT NULL, 
                    equivalent_fips_code MEDIUMINT DEFAULT NULL,
                PRIMARY KEY (`geoname_id`),
                UNIQUE KEY `idxIso2` (`iso2`),
                UNIQUE KEY `idxIso3` (`iso3`),
                UNIQUE KEY `idxIsoN` (`isoN`),
                UNIQUE KEY `idxCountry` (country)
			    ) $charset_collate;
SQL;
            dbDelta($sql);
        }

        // time zones
        $nom = $this->tblTimeZones;

        if ($wpdb->get_var("SHOW TABLES LIKE '$nom'") !== $nom)
        {
            $sql = <<<SQL
                CREATE TABLE $nom (
                    country_code enum($country_codes) DEFAULT NULL,
                    time_zone_id VARCHAR(40) NOT NULL,
                    city VARCHAR(40) NOT NULL,
                    caption VARCHAR(40) NOT NULL,
                    php VARCHAR(40) NOT NULL,
                    offsetJan DECIMAL (3,1) COMMENT '(GMT offset 1. Jan 2020)',
                    offsetJul DECIMAL (3,1) COMMENT '(DST offset 1. Jul 2020)',
                    offsetRaw DECIMAL (3,1) COMMENT '(GMT offset independant of DST)',
                PRIMARY KEY (`time_zone_id`),
                KEY `idxCountry` (country_code, city)
			    ) $charset_collate;
SQL;
            dbDelta($sql);
        }

        $sql = <<<SQL
            CREATE TABLE %s (
                `geoname_id` int(11) NOT NULL,
                `name` varchar(200) NOT NULL,
                `ascii_name` varchar(200) NOT NULL,
                `alternate_names` text DEFAULT NULL,
                `latitude` decimal(10,5) DEFAULT NULL,
                `longitude` decimal(10,5) DEFAULT NULL,
                `feature_class` enum($feature_classes) NOT NULL,
                `feature_code` enum($feature_codes) NOT NULL,
                `country_code` enum($country_codes) DEFAULT NULL,
                `cc2` varchar(60) DEFAULT NULL,
                `admin1_code` varchar(20) DEFAULT NULL,
                `admin2_code` varchar(80) DEFAULT NULL,
                `admin3_code` varchar(20) DEFAULT NULL,
                `admin4_code` varchar(20) DEFAULT NULL,
                `population` int(20) unsigned DEFAULT NULL,
                `elevation` smallint(6) DEFAULT NULL,
                `dem` smallint(6) DEFAULT NULL,
                `timezone` enum($time_zones) DEFAULT NULL,
                `modification_date` date DEFAULT NULL,
                PRIMARY KEY (`geoname_id`),
            KEY `index1` (`feature_class`,`feature_code`,`country_code`,`cc2`(2),`name`(3)),
            KEY `country_code_admin` (`country_code`,`admin1_code`,`admin2_code`,`admin3_code`,`admin4_code`,`name`(3))
			) $charset_collate;
SQL;

        // locations
        $nom = $this->tblLocations;

        if ($wpdb->get_var("SHOW TABLES LIKE '$nom'") !== $nom)
        {
            dbDelta(sprintf($sql, $nom));
        }

        // locations cache
        $nom = $this->tblCacheLocations;

        if ($wpdb->get_var("SHOW TABLES LIKE '$nom'") !== $nom)
        {
            dbDelta(sprintf($sql, $nom));
        }

        // locations cache queries
        $nom = $this->tblCacheQueries;

        if ($wpdb->get_var("SHOW TABLES LIKE '$nom'") !== $nom)
        {

            $searchTypes = implode("','", ApiQuery::SEARCH_TYPES);

            $sql = <<<SQL
                CREATE TABLE $nom  (
                    `query_id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    `query_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `query_updated` timestamp NOT NULL ON UPDATE CURRENT_TIMESTAMP,
                    `search_term` varchar(200) NOT NULL,
                    `search_type` enum('$searchTypes') NOT NULL,
                    `search_country` enum($country_codes) DEFAULT NULL,
                    `search_params` varchar(500) NOT NULL,
                    `result_count` smallint NOT NULL,
                    `result_total` smallint DEFAULT NULL, 
                INDEX `idx_search` (`search_term`(10), `search_type`, `search_country`)
			) $charset_collate;
SQL;
            dbDelta($sql);
        }

        // locations cache results
        $nom = $this->tblCacheResults;

        if ($wpdb->get_var("SHOW TABLES LIKE '$nom'") !== $nom)
        {
            $sql = <<<SQL
                CREATE TABLE $nom (
                    `query_id` int NOT NULL AUTO_INCREMENT,
                    `geoname_id` int(11) NOT NULL,
                    `order` SMALLINT(3) unsigned NOT NULL,
                    `country_code` enum($country_codes) DEFAULT NULL,
                    PRIMARY KEY (`query_id`, `geoname_id`),
                INDEX `idx_result` (`query_id`, `order`)
			) $charset_collate;
SQL;
            dbDelta($sql);
        }

        // post codes
        $nom = $this->tblPostCodes;

        if ($wpdb->get_var("SHOW TABLES LIKE '$nom'") !== $nom)
        {
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
			) $charset_collate;";
            dbDelta($sql);
        }

        // Data
        $this->addNoCountries();
        $this->addCountries();
        $this->addTimezones();

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

        foreach ([$this->tblLocations, $this->tblCacheLocations] as $nom)
        {

            $wpdb->query(sprintf($sql, $nom, $this->tblCountries));

        }

        $this->inActivation = false;
    }



}