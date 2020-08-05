<?php

/** @noinspection HtmlUnknownTarget */

namespace WPGeonames;

use ErrorException;
use GeoNames\Client as GeoNamesClient;
use StdClass;
use ZipArchive;

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Core {

	// constants
	// version
	const geoVersion = "2.0.1";

	// tables constants
	const tblPrefix = 'geonames_';
	const tblCountries = self::tblPrefix . 'countries';
	const tblLocations = self::tblPrefix . 'locations';
	const tblLocationsCache = self::tblLocations . '_cache';
	const tblLocationsQueries = self::tblLocations . '_queries';
	const tblLocationsResults = self::tblLocations . '_results';
	const tblPostCodes = self::tblPrefix . 'postal';
	const tblTimeZones = self::tblPrefix . 'timezones';

	// urls
	const urlCountries = self::urlLocations . 'countryInfo.txt';
	const urlLocations = 'http://download.geonames.org/export/dump/';
	const urlNoCountries = self::urlLocations . 'no-country.zip';
	const urlPostal = 'http://download.geonames.org/export/zip/';
	const urlTimeZones = self::urlLocations . 'timeZones.txt';

	//  public properties
	public static $wpdb = null;

	// protected properties
	static protected $geoNameClient = null;
	static protected $featureClasses;
	static protected $featureCodes; // countries
	static protected $countryCodes; // postal codes
	static protected $timeZones;
	static protected $enums;

	// table vars
	protected $tblLocations;
	protected $tblCacheLocations;
	protected $tblCacheQueries;
	protected $tblCacheResults;
	protected $tblCountries;
	protected $tblPostCodes;
	protected $tblTimeZones;

	// private properties
	/** @var Core */
	static private $instance = null;

	// other
	/** @var string plugin main file */
	private $plugin_file;
	private $inActivation = false;

	/**
	 * Core constructor.
	 *
	 * @param $file
	 */
	public function __construct( $file ) {

		self::$wpdb = new WpDb();

		$this->plugin_file       = $file;
		$this->tblCountries      = self::$wpdb->base_prefix . self::tblCountries;
		$this->tblLocations      = self::$wpdb->base_prefix . self::tblLocations;
		$this->tblCacheLocations = self::$wpdb->base_prefix . self::tblLocationsCache;
		$this->tblCacheQueries   = self::$wpdb->base_prefix . self::tblLocationsQueries;
		$this->tblCacheResults   = self::$wpdb->base_prefix . self::tblLocationsResults;
		$this->tblPostCodes      = self::$wpdb->base_prefix . self::tblPostCodes;
		$this->tblTimeZones      = self::$wpdb->base_prefix . self::tblTimeZones;

		register_activation_hook( $this->plugin_file, [ $this, 'creation_table' ] );

		add_shortcode( 'wp-geonames', [ $this, 'shortcode' ] );
		add_action( 'wp_ajax_nopriv_geoDataRegion', [ $this, 'ajax_geoDataRegion' ] );
		add_action( 'wp_ajax_geoDataRegion', [ $this, 'ajax_geoDataRegion' ] );
		add_action( 'wp_ajax_nopriv_geoDataCity', [ $this, 'ajax_geoDataCity' ] );
		add_action( 'wp_ajax_geoDataCity', [ $this, 'ajax_geoDataCity' ] );
		add_action( 'wp_ajax_wpgeonameGetCity', [ $this, 'ajax_get_city_by_country_region' ] );

		add_filter( 'geonames/api/params', [ $this, 'checkSearchParams' ], 10, 2 );
		add_filter( 'geonames/api/params', [ $this, 'checkSearchParamsMinRequirements' ], 5, 2 );
		add_filter( 'geonames/api/result', [ $this, 'cacheSearchResult' ], 10, 2 );

		if ( is_admin() ) {
			load_plugin_textdomain( 'wpGeonames', false, dirname( $this->getPluginFileRelative() ) . '/lang/' ); // language
			add_action( 'wp_ajax_wpgeonamesAjax', 'wpgeonamesAjax' );
			add_action( 'wp_ajax_wpgeonameGetCity', [ $this, 'ajax_get_city_by_country_region' ] );
			add_action( 'wp_ajax_wpGeonamesAddCountry', [ $this, 'ajax_wpGeonamesAddLocation' ] );
			add_action( 'wp_ajax_wpGeonamesAddPostal', [ $this, 'ajax_wpGeonamesAddPostCode' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_leaflet' ] );
			add_action( 'admin_menu', [ $this, 'addAdminMenu' ] );
			add_filter( 'plugin_action_links_' . $this->getPluginFileRelative(), [ $this, 'settings_link' ] );
			add_filter( 'option_wpGeonames_dataList', [ $this, 'check_options' ], 10, 2 );
			add_filter( 'default_option_wpGeonames_dataList', [ $this, 'check_options' ], 10, 2 );
			if ( file_exists( $this->getPluginDir() . '/patch.php' ) ) {
				/** @noinspection PhpIncludeInspection */
				include( $this->getPluginDir() . '/patch.php' );
			}
		}
	}

	/**
	 * @return string full plugin file path
	 */
	public function getPluginDir() {
		return plugin_dir_path( $this->getPluginFileFull() );
	}

	/**
	 * @return string full plugin file path
	 */
	public function getPluginFileFull() {
		return $this->plugin_file;
	}

	/**
	 * @return string Path to the main plugin file from plugins directory
	 */
	public function getPluginFileRelative() {
		return plugin_basename( $this->getPluginFileFull() );
	}

	/**
	 * @return string plugin slug
	 */
	public function getPluginSlug() {
		return basename( $this->getPluginDir() );
	}

	public function get_all_region() {
		//
		global $wpdb;
		$out = "";
		$q   = $wpdb->get_results(
			"SELECT
			country_code,
			cc2,
			name
		FROM
			" . $this->tblLocations . "
		WHERE
			feature_code='ADM1'
			and (feature_class='A' or feature_code='PCLD')
		ORDER BY cc2,country_code,name
		"
		);
		foreach ( $q as $k => $v ) {
			if ( $v->cc2 != '' ) {
				$q[ $k ]->country_code = $v->cc2;
			}
		}
		usort( $q, "wpGeonames_sortCountry2" );
		$a = [];
		foreach ( $q as $r ) {
			if ( ! isset( $a[ $r->country_code . $r->name ] ) ) {
				$out                              .= "('r', '" . $r->name . "', '" . $r->country_code . "', ''),\r\n";
				$a[ $r->country_code . $r->name ] = 1;
			}
		}
		file_put_contents( $this->getPluginDir() . ' / liste_region . txt', $out );
	}

	public function get_country( $postal = 0 ) {
		global $wpdb;
		// OUTPUT Object : country_code, name
		// country list : http://www.nationsonline.org/oneworld/country_code_list.htm
		// country list : https://en.wikipedia.org/wiki/ISO_3166-1 - Only Indenpendent*
		$liste = require( './includes/country_codes.php' );

		if ( ! $postal ) {
			$q = $wpdb->get_results(
				"SELECT DISTINCT country_code FROM " . $this->tblLocations . " ORDER BY country_code"
			);
		} else {
			$q = $wpdb->get_results(
				"SELECT DISTINCT country_code FROM " . $this->tblPostCodes . " ORDER BY country_code"
			);
		}
		$result = [];
		foreach ( $q as $r ) {
			if ( isset( $liste[ $r->country_code ] ) ) {
				$a               = new StdClass();
				$a->country_code = $r->country_code;
				$a->name         = $liste[ $r->country_code ];
				$result[]        = $a;
			}
		}
		usort( $result, "wpGeonames_sortCountry" );

		return $result;
	}

	public function get_postalCheck(
		$iso,
		$postal
	) {

		global $wpdb;

		if ( strlen( $postal ) < 3 ) {
			return false;
		}

		$o = '';
		$q = $wpdb->get_results(
			"SELECT *
		FROM
			" . $wpdb->base_prefix . "geonamesPostal
		WHERE
			country_code='" . $iso . "'
			and postal_code LIKE ' % " . $postal . " % ' 
		ORDER BY postal_code
		LIMIT 200
		"
		);
		if ( $q ) {
			$c = 0;
			$o .= '<table class="widefat">';
			foreach ( $q as $r ) {
				if ( ! $c ) {
					$o .= '<thead><tr>';
					foreach ( $r as $k => $v ) {
						$o .= '<th>' . str_replace( '_', '<br>', $k ) . ' </th>';
					}
					$o .= '</tr></thead>';
				}
				$o .= '<tr>';
				foreach ( $r as $k => $v ) {
					$o .= '<td>' . $v . '</td>';
				}
				$o .= '</tr>';
				++ $c;
			}
			$o .= '</table>';
		}

		return $o;
	}

	public function get_region_by_country( $iso = '' ) {
		//
		global $wpdb;
		$result = [];
		if ( $iso ) {
			$a = "admin1_code";
			$b = "ADM1";
			if ( $this->regionCode2( $iso ) ) {
				$a = "admin2_code";
				$b = "ADM2";
			}
			$q = $wpdb->get_results(
				"SELECT
				geoname_id,
				name,
				country_code,
				" . $a . "
			FROM
				" . $this->tblLocations . "
			WHERE
				feature_class='A' and
				((feature_code='" . $b . "' and (country_code='" . $iso . "' or cc2='" . $iso . "'))
					or
				(feature_code='PCLD' and cc2='" . $iso . "'))
			ORDER BY name
			"
			);
			$c = [];
			foreach ( $q as $r ) {
				if ( $r->$a == '00' ) {
					$r->$a = $r->country_code;
				}
				if ( ! isset( $c[ $r->name ] ) ) {
					$result[]      = $r;
					$c[ $r->name ] = 1;
				}
			}
		}

		return $result;
	}


	public function addAdminMenu() {

		$this->verifyAdmin();

		add_options_page(
			'WP GeoNames Options',
			'WP GeoNames',
			'manage_options',
			'wpGeonames-options',
			[
				$this,
				'adminMenu',
			]
		);
	}

	public function addCountries() {

		$this->verifyAdmin();

		$source = $this->downloadZip( 'general', self::urlCountries );

		$fields = [
			'iso2'                 => (object) [ 'save' => true, 'format' => 's', 'regex' => '[A-Z]{2}' ],
			'iso3'                 => (object) [ 'save' => true, 'format' => 's', 'regex' => '[A-Z]{3}' ],
			'isoN'                 => (object) [ 'save' => true, 'format' => 'd', 'regex' => '\d{3}' ],
			'fips'                 => (object) [ 'save' => true, 'format' => 's', 'regex' => '(?:[A-Z]{2})?' ],
			'country'              => (object) [ 'save' => true, 'format' => 's', 'regex' => '[^\t]*' ],
			'capital'              => (object) [ 'save' => true, 'format' => 's', 'regex' => '[^\t]*' ],
			'area'                 => (object) [ 'save' => true, 'format' => 'd', 'regex' => '\d*' ],
			'population'           => (object) [ 'save' => true, 'format' => 'd', 'regex' => '\d*' ],
			'continent'            => (object) [ 'save' => true, 'format' => 's', 'regex' => '[A-Z]{2}' ],
			'tld'                  => (object) [ 'save' => true, 'format' => 's', 'regex' => '.[\w.]{2,}' ],
			'currency_code'        => (object) [ 'save' => true, 'format' => 's', 'regex' => '[A-Z]{3}' ],
			'currency_name'        => (object) [ 'save' => true, 'format' => 's', 'regex' => '[^\t]*' ],
			'phone'                => (object) [ 'save' => true, 'format' => 's', 'regex' => '[^\t]*' ],
			'postal_code_format'   => (object) [ 'save' => true, 'format' => 's', 'regex' => '[^\t]*' ],
			'postal_code_regex'    => (object) [ 'save' => true, 'format' => 's', 'regex' => '[^\t]*' ],
			'languages'            => (object) [ 'save' => true, 'format' => 's', 'regex' => '[^\t]*' ],
			'geoname_id'           => (object) [ 'save' => true, 'format' => 'd', 'regex' => '\d+' ],
			'neighbours'           => (object) [ 'save' => true, 'format' => 's', 'regex' => '[^\t]*' ],
			'equivalent_fips_code' => (object) [ 'save' => true, 'format' => 'd', 'regex' => '\d*' ],
		];

		return $this->loadFileIntoDb(
			$source,
			$this->tblCountries,
			$fields,
			1,
			static function ( &$row ) {
				return self::$instance->checkCountry( $row['iso2'], $row['country'] );
			}
		);
	}

	public function addLocations(
		$mode,
		$url,
		$filename = null,
		$force = false,
		$features
		= [
			'A' => [ 'ADM1', 'ADM2', 'ADM3', 'ADM4', 'PCL', 'PCLD', 'PCLF', 'PCLI', 'PCLIX', 'PCLS' ],
			'L' => [ 'AREA', 'CONT', 'TERR' ],
			'P' => [ 'PPL', 'PPLA', 'PPLA2', 'PPLA3', 'PPLA4', 'PPLC', ],
		],
		$fieldNames = [ '*', '-alternate_names', ],
		$deleteSource = false
	) {

		$this->verifyAdmin();

		if ( $this->verifyToka() ) {
			return false;
		}

		if ( key( $features ) === 0 ) {
			$features = array_fill_keys( $features, true );
		}

		if ( key( $fieldNames ) === 0 ) {
			$fieldNames = array_flip( $fieldNames );
		}

		$source        = $this->downloadZip( 'names', $url, $filename, $force );
		$feature_class = array_keys( $features );
		$rxClass       = join( '|', $feature_class );
		$saveField     = static function ( $fieldName ) use (
			&
			$fieldNames
		) {
			if ( array_key_exists( '*', $fieldNames ) && ! array_key_exists( "-$fieldName", $fieldNames ) ) {
				return true;
			}

			return isset( $fieldNames[ $fieldName ] );
		};

		$fields = [
			'geoname_id'        => (object) [
				'save'   => true,
				'format' => 'd',
				'regex'  => '\d+',
			],
			'name'              => (object) [
				'save'   => $saveField( 'name' ),
				'format' => 's',
				'regex'  => '[^\t]*',
			],
			'ascii_name'        => (object) [
				'save'   => $saveField( 'ascii_name' ),
				'format' => 's',
				'regex'  => '[^\t]*',
			],
			'alternate_names'   => (object) [
				'save'   => $saveField( 'alternate_names' ),
				'format' => 's',
				'regex'  => '[^\t]*',
			],
			'latitude'          => (object) [
				'save'   => $saveField( 'latitude' ),
				'format' => 'f',
				'regex'  => '[^\t]*',
			],
			'longitude'         => (object) [
				'save'   => $saveField( 'longitude' ),
				'format' => 'f',
				'regex'  => '[^\t]*',
			],
			'feature_class'     => (object) [
				'save'   => $saveField( 'feature_class' ),
				'format' => 's',
				'regex'  => $rxClass,
			],
			'feature_code'      => (object) [
				'save'   => $saveField( 'feature_code' ),
				'format' => 's',
				'regex'  => '\w+',
			],
			'country_code'      => (object) [
				'save'   => $saveField( 'country_code' ),
				'format' => 's',
				'regex'  => '\w*',
			],
			'cc2'               => (object) [
				'save'   => $saveField( 'ascii_name' ),
				'format' => 's',
				'regex'  => '[^\t]*',
			],
			'admin1_code'       => (object) [
				'save'   => $saveField( 'admin1_code' ),
				'format' => 's',
				'regex'  => '\w*',
			],
			'admin2_code'       => (object) [
				'save'   => $saveField( 'admin2_code' ),
				'format' => 's',
				'regex'  => '\w*',
			],
			'admin3_code'       => (object) [
				'save'   => $saveField( 'admin3_code' ),
				'format' => 's',
				'regex'  => '\w*',
			],
			'admin4_code'       => (object) [
				'save'   => $saveField( 'admin4_code' ),
				'format' => 'd',
				'regex'  => '\w*',
			],
			'population'        => (object) [
				'save'   => $saveField( 'population' ),
				'format' => 'd',
				'regex'  => '\d*',
			],
			'elevation'         => (object) [
				'save'   => $saveField( 'elevation' ),
				'format' => 'd',
				'regex'  => '-?\d*',
			],
			'dem'               => (object) [
				'save'   => $saveField( 'dem' ),
				'format' => 'd',
				'regex'  => '-?\d*',
			],
			'timezone'          => (object) [
				'save'   => $saveField( 'timezone' ),
				'format' => 's',
				'regex'  => '[^\t]*',
			],
			'modification_date' => (object) [
				'save'   => $saveField( 'modification_date' ),
				'format' => 's',
				'regex'  => '[^\t\n]*',
			],
		];

		if ( $this->loadFileIntoDb(
				$source,
				$this->tblLocations,
				$fields,
				$mode,
				static function ( $row ) use (
					&
					$features
				) {
					if ( ! array_key_exists( $row['feature_class'], $features )
					     || ! ( $features[ $row['feature_class'] ] === true
					            || in_array( $row['feature_code'], $features[ $row['feature_class'] ] )
						)
					) {
						return false;
					}

					return true;
				}
			)

		     && $deleteSource ) {
			@unlink( $source );
		}

		$this->update_options();

		return __( 'Done, data are in base.', 'wpGeonames' );
	}

	public function addLocationsFromForm(
		$mode,
		$url,
		$f
	) {
		$fe = [];

		if ( ! empty( $f['wpGeoA'] ) ) {
			$fe["A"] = true;
		}
		if ( ! empty( $f['wpGeoH'] ) ) {
			$fe["H"] = true;
		}
		if ( ! empty( $f['wpGeoL'] ) ) {
			$fe["L"] = true;
		}
		if ( ! empty( $f['wpGeoP'] ) ) {
			$fe["P"] = empty( $f['wpGeoCity'] )
				? true
				: [
					'PPL',
					'PPLA',
					'PPLA2',
					'PPLA3',
					'PPLA4',
					'PPLC',
				];
		}
		if ( ! empty( $f['wpGeoR'] ) ) {
			$fe["R"] = true;
		}
		if ( ! empty( $f['wpGeoS'] ) ) {
			$fe["S"] = true;
		}
		if ( ! empty( $f['wpGeoT'] ) ) {
			$fe["T"] = true;
		}
		if ( ! empty( $f['wpGeoU'] ) ) {
			$fe["U"] = true;
		}
		if ( ! empty( $f['wpGeoV'] ) ) {
			$fe["V"] = true;
		}

		return $this->addLocations(
			$mode,
			$url,
			$f['wpGeonamesAdd'],
			isset( $f['wpGeoForce'] ),
			$fe,
			[ '*', '-alternate_names', ],
			isset( $fieldNames['wpGeoDeleteFiles'] )
		);
	}

	public function addNoCountries() {
		return $this->addLocations(
			1,
			self::urlNoCountries
		);
	}

	public function addTimezones() {

		$this->verifyAdmin();

		$source = $this->downloadZip( 'general', self::urlTimeZones );

		$regexTZ = '-?\d+\.\d{1,2}';

		$fields = [
			'country_code' => (object) [ 'save' => true, 'format' => 's', 'regex' => '[[A-Z]{2}' ],
			'time_zone_id' => (object) [ 'save' => true, 'format' => 's', 'regex' => '[-\w_/]+/(?<city>[-\w_]+)' ],
			'offsetJan'    => (object) [ 'save' => true, 'format' => 'd', 'regex' => $regexTZ ],
			'offsetJul'    => (object) [ 'save' => true, 'format' => 'd', 'regex' => $regexTZ ],
			'offsetRaw'    => (object) [ 'save' => true, 'format' => 'd', 'regex' => $regexTZ ],
			'city'         => (object) [ 'save' => true, 'format' => 's', 'regex' => null ],
			'caption'      => (object) [ 'save' => true, 'format' => 's', 'regex' => null ],
		];

		return $this->loadFileIntoDb(
			$source,
			$this->tblTimeZones,
			$fields,
			1,
			static function ( &$row ) {
				$row['city']    = str_replace( '_', ' ', $row['city'] );
				$row['caption'] = str_replace( '_', ' ', $row['time_zone_id'] );

				return self::$instance->checkTimeZone(
						$row['time_zone_id'],
						$row['country_code']
					) && self::$instance->checkCountry( $row['country_code'] );
			}
		);
	}

	public function adminMenu() {

		$this->verifyAdmin();

		$wpGeoList = empty( $_GET['checkData'] )
			? get_option( 'wpGeonames_dataList' )
			: $this->update_options( true );

		if ( ! empty( $wpGeoList['date'] ) ) {
			[ $year, $month, $day ] = explode( '-', $wpGeoList['date'] );
			$old = mktime( 0, 0, 0, $month, $day, $year );
			if ( time() - $old > 31536000 ) { // 1 year : 31536000 -
				?>

                <div class="notice notice-warning is-dismissible">
                    <p>
                        <strong><?php
							_e(
								'Data is very old. You should Clear this table and Add new datas.',
								'wpGeonames'
							); ?></strong>
                    </p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
				<?php
			}
		}

		$geoTab = $_GET['geotab']
			?: false;
		?>

        <div class='wrap'>
            <h2 class="nav-tab-wrapper">
                <a href="options-general.php?page=wpGeonames-options"
                   class="nav-tab<?php
				   if ( empty( $geoTab ) ) {
					   echo ' nav-tab-active';
				   } ?>"><?php
					_e( 'General', 'wpGeonames' ); ?></a>
                <a href="options-general.php?page=wpGeonames-options&geotab=check"
                   class="nav-tab<?php
				   if ( $geoTab === 'check' ) {
					   echo ' nav-tab-active';
				   } ?>"><?php
					_e( 'Check Data', 'wpGeonames' ); ?></a>
                <a href="options-general.php?page=wpGeonames-options&geotab=edit"
                   class="nav-tab<?php
				   if ( $geoTab === 'edit' ) {
					   echo ' nav-tab-active';
				   } ?>"><?php
					_e( 'Edit Data', 'wpGeonames' ); ?></a>
                <a href="options-general.php?page=wpGeonames-options&geotab=help"
                   class="nav-tab<?php
				   if ( $geoTab === 'help' ) {
					   echo ' nav-tab-active';
				   } ?>"><?php
					_e( 'Help', 'wpGeonames' ); ?></a>
            </h2>
			<?php

			switch ( $_GET['geotab']
				?: null ) {
				case 'check':
					$this->admin_check();
					break;
				case 'edit':
					$this->admin_edit();
					break;
				case 'help':
					$this->admin_help();
					break;
				default:
					$this->admin_general( $wpGeoList );
			}

			?>
        </div>
        <div style="clear:both;"></div>
		<?php
	}

	public function admin_check() {
		global $wpdb;
		$country       = $this->get_country();
		$postalCountry = $this->get_country( 1 );
		$Gcountry      = ( ! empty( $_GET['country'] )
			? sanitize_text_field( $_GET['country'] )
			: '' );
		$Gregion       = ( ! empty( $_GET['region'] )
			? sanitize_text_field( $_GET['region'] )
			: '' );
		$Gcityid       = ( ! empty( $_GET['cityid'] )
			? sanitize_text_field( $_GET['cityid'] )
			: '' );
		$Gpostal       = ( ! empty( $_GET['postal'] )
			? sanitize_text_field( $_GET['postal'] )
			: '' );
		//
		$geoToka = wp_create_nonce( 'geoToka' );
		if ( $Gcountry ) {
			if ( isset( $_GET['cityid'] ) ) {
				$region = $this->get_region_by_country( $Gcountry );
			} elseif ( isset( $_GET['postal'] ) ) {
				$outPostal = $this->get_postalCheck( $Gcountry, $Gpostal );
			}
		}
		?>
        <style>
            .wpgeoCity span {
                color: #555;
                font-weight: 400;
                width: auto;
            }

            .wpgeoCity span:hover {
                color: #000;
                font-weight: 700;
            }
        </style>
        <h2><?php
			_e( 'Check your Geonames data', 'wpGeonames' ) ?> - <?php
			_e( 'Countries', 'wpGeonames' ); ?></h2>
        <form name="geoCheck" action="" method="GET">
            <input type="hidden" name="page" value="wpGeonames-options"/>
            <input type="hidden" name="geotab" value="check"/>
            <input type="hidden" name="region" value=""/>
            <input type="hidden" name="country" value=""/>
            <input type="hidden" name="cityid" value=""/>
            <input type="hidden" name="geoToka" value="<?php
			echo $geoToka; ?>"/>
        </form>
        <div style="float:left;width:48%;overflow:hidden;">
            <label for="geoCheckCountry"><?php
				_e( 'Country', 'wpGeonames' ) ?></label><br/>
            <select id="geoCheckCountry" name="geoCheckCountry"
                    onchange="document.forms['geoCheck'].elements['country'].value=this.options[this.selectedIndex].value;document.forms['geoCheck'].submit();">
                <option value=""> -</option>
				<?php
				foreach ( $country as $r ) {
					echo '<option value="' . $r->country_code . '" ' . ( ( $Gcountry == $r->country_code )
							? 'selected'
							: '' ) . '>' . $r->name . '</option>';
				} ?>

            </select>
        </div>
        <div style="float:left;width:48%;overflow:hidden;">
            <label for="geoCheckRegion"><?php
				_e( 'Region', 'wpGeonames' ) ?></label><br/>
            <select id="geoCheckRegion" name="geoCheckRegion" <?php
			if ( empty( $region ) ) {
				echo 'style="display:none;"';
			} ?>
                    onchange="document.forms['geoCheck'].elements['country'].value=document.getElementById('geoCheckCountry').options[document.getElementById('geoCheckCountry').selectedIndex].value;document.forms['geoCheck'].elements['region'].value=this.options[this.selectedIndex].value;document.forms['geoCheck'].submit();">
                <option value=""> -</option>
				<?php
				if ( ! empty( $region ) ) {
					foreach ( $region as $r ) {
						echo '<option value="' . $r->admin1_code . '" ' . ( ( $Gregion == $r->admin1_code )
								? 'selected'
								: '' ) . '>' . $r->name . '</option>';
					}
				} ?>

            </select>
        </div>
        <div style="clear:both;margin-bottom:40px;"></div>
        <div style="float:left;width:48%;overflow:hidden;">
            <label for="geoCheckCity"><?php
				_e( 'City', 'wpGeonames' ) ?></label><br/>
            <input type="text" id="geoCheckCity" name="geoCheckCity"
                   onkeyup="wpGeonameListCity(this.value,'<?php
			       echo $Gcountry; ?>','<?php
			       echo $Gregion; ?>');" <?php
			if ( ! $Gregion ) {
				echo 'style="display:none;"';
			} ?> />
            <div class="geoListCity" id="geoListCity"></div>
        </div>
        <div style="float:left;width:48%;overflow:hidden;">
            <div id="geomap" style="height:300px;max-width:400px;display:none;"></div>
        </div>
        <div style="clear:both;margin-bottom:20px;"></div>
        <hr/>
        <h2><?php
			_e( 'Check your Geonames datas', 'wpGeonames' ) ?>
            - <?php
			_e( 'Postal codes', 'wpGeonames' ); ?></h2>
        <form name="geoCheckPostal" action="" method="GET">
            <input type="hidden" name="page" value="wpGeonames-options"/>
            <input type="hidden" name="geotab" value="check"/>
            <input type="hidden" name="geoToka" value="<?php
			echo $geoToka; ?>"/>
            <div style="float:left;width:48%;overflow:hidden;">
                <label><?php
					_e( 'Country', 'wpGeonames' ) ?><br/>
                    <select name="country" id="wpGeonamesPostalCountry">
                        <option value=""> -</option>
						<?php
						foreach ( $postalCountry as $r ) {
							echo '<option value="' . $r->country_code . '" ' . ( ( $Gcountry == $r->country_code )
									? 'selected'
									: '' ) . '>' . $r->name . '</option>';
						} ?>

                    </select></label>
            </div>
            <div style="float:left;width:48%;overflow:hidden;">
                <label><?php
					_e( 'Postal codes', 'wpGeonames' ); ?><br/>
                    <input type="text" name="postal"/></label>
            </div>
            <div class="submit" style="clear:both;margin-top:10px;">
                <input type="submit" class="button-primary" value="<?php
				_e( 'Search', 'wpGeonames' ) ?>"/>
            </div>
        </form>
        <div><?php
			if ( ! empty( $outPostal ) ) {
				echo $outPostal;
			} ?></div>
        <!--suppress JSPotentiallyInvalidConstructorUsage -->
        <script>
            let wpgeoajx;

            function wpGeonameListCity(ci, iso, re) {
                jQuery(document).ready(function () {
                    if (ci.length > 2) {
                        wpgeoajx = null;
                        wpgeoajx = jQuery.post('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
                            'action': 'wpgeonameGetCity',
                            'city': ci,
                            'iso': iso,
                            'region': re,
                            'geoToka': '<?php echo $geoToka; ?>'
                        }, function (data) {
                            const r = jQuery.parseJSON(data.substring(0, data.length - 1));
                            jQuery('#geoListCity').empty();
                            jQuery.each(r, function (k, v) {
                                jQuery('#geoListCity').append(
                                    '<div class="wpgeoCity"><span onClick="'
                                    + "document.forms['geoCheck'].elements['country'].value='<?php echo $_GET['country']; ?>';"
                                    + "document.forms['geoCheck'].elements['region'].value='<?php echo $_GET['region']; ?>';"
                                    + "document.forms['geoCheck'].elements['cityid'].value='" + v.geoname_id + "';"
                                    + "document.forms['geoCheck'].submit();"
                                    + '">' + v.name + ' (' + v.feature_code + ')</span></div>');
                            });
                        });
                    }
                });
            }
			<?php // https://switch2osm.org/fr/utilisation-des-tuiles/debuter-avec-leaflet/ ?>
            function wpGeonameCityMap(ci, lat, lon) {
                document.getElementById('geomap').style.display = 'block';
                const wpgeomap = new L.map('geomap').setView([lat, lon], 9);
                const wpgeodata = new L.TileLayer('//{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    minZoom: 5,
                    maxZoom: 14,
                    attribution: 'Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors'
                });
                wpgeomap.addLayer(wpgeodata);
                const wpgeomark = L.marker([lat, lon]).addTo(wpgeomap);
                wpgeomark.bindPopup("<b>" + ci + "</b>").openPopup();
            }
			<?php if ( $Gcityid ) {
				$q = $wpdb->get_row(
					"SELECT
				*
			FROM
				" . $this->tblLocations . "
			WHERE
				geoname_id='" . $Gcityid . "'
			LIMIT 1
			"
				);
				$a = '';
				foreach ( $q as $k => $v ) {
					if ( ! empty( $v ) ) {
						$a .= '<div><strong>' . $k . '</strong> : ' . $v . '</div>';
					}
				}
				echo "document.getElementById('geoListCity').innerHTML='" . $a . "';";
				echo "document.getElementById('geoCheckCity').value='" . $q->name . "';";
				echo "wpGeonameCityMap('" . $q->name . "','" . $q->latitude . "','" . $q->longitude . "');";
			} ?>

        </script>
		<?php
	}

	public function admin_edit() {
		global $wpdb;
		$GgeoType = ( ! empty( $_GET['geoType'] )
			? preg_replace( "/[^a-zA-Z0-9_,-]/", "", $_GET['geoType'] )
			: '' );
		$geoToka  = wp_create_nonce( 'geoToka' );
		$o        = '';
		if ( ! empty( $_GET['geoid'] ) && ! empty( $_GET['geodata'] ) ) {
			$a  = stripslashes( strip_tags( $_GET['geodata'] ) );
			$id = intval( $_GET['geoid'] );
			$wpdb->update( $wpdb->base_prefix . self::urlLocations, [ 'name' => $a ], [ 'geoname_id' => $id ] );
			echo '<script>window.location.replace("options-general.php?page=wpGeonames-options&geotab=edit");</script>';
			exit;
		} elseif ( ! empty( $_GET['geoSearch'] ) ) {
			$a = strip_tags( $_GET['geoSearch'] );
			$o = '<hr />';
			$w = '';
			if ( $GgeoType == 'region' ) {
				$w = "and feature_class='A' and feature_code IN ('ADM1','ADM2','PCLD')";
			} elseif ( $GgeoType == 'city' ) {
				$w = "and feature_class='P'";
			}
			$q = $wpdb->get_results(
				"SELECT
				geoname_id,
				name,
				country_code
			FROM
				" . $wpdb->base_prefix . self::urlLocations . "
			WHERE
				name LIKE '%" . $a . "%'
				" . $w
			);
			if ( ! empty( $q ) ) {
				foreach ( $q as $v ) {
					$o .= '<div>' . $v->geoname_id;
					$o .= '<input type="text" name="geadata' . $v->geoname_id . '" value="' . $v->name . '" style="margin: 0 10px;width:360px;" />';
					$o .= '<strong style="margin-right:10px;">' . $v->country_code . '</strong>';
					$o .= '<input type="button" class="button-primary" value="' . __(
							'Change',
							'wpGeonames'
						) . '" onClick="document.forms[\'geoEdit\'].elements[\'geodata\'].value=document.forms[\'geoEdit\'].elements[\'geadata' . $v->geoname_id . '\'].value;document.forms[\'geoEdit\'].elements[\'geoid\'].value=' . $v->geoname_id . ';document.forms[\'geoEdit\'].submit();" />';
					$o .= '</div>';
				}
			}
		}
		?>
        <h2><?php
			_e( 'Edit Datas', 'wpGeonames' ); ?></h2>
        <form name="geoEdit" action="" method="GET">
            <input type="hidden" name="page" value="wpGeonames-options"/>
            <input type="hidden" name="geotab" value="edit"/>
            <input type="hidden" name="geoid" value=""/>
            <input type="hidden" name="geodata" value=""/>
            <input type="hidden" name="geoToka" value="<?php
			echo $geoToka; ?>"/>
            <div style="float:left;margin-right:20px;overflow:hidden;">
                <label><?php
					_e( 'Type of data', 'wpGeonames' ) ?><br/>
                    <select id="geoType" name="geoType">
                        <option value="region" <?php
						if ( $GgeoType == 'region' ) {
							echo 'selected';
						} ?>><?php
							_e( 'Region', 'wpGeonames' ) ?></option>
                        <option value="city" <?php
						if ( $GgeoType == 'city' ) {
							echo 'selected';
						} ?>><?php
							_e( 'City', 'wpGeonames' ) ?></option>
                    </select></label>
            </div>
            <div style="float:left;margin-right:20px;overflow:hidden;">
                <label><?php
					_e( 'Data', 'wpGeonames' ) ?><br/>
                    <input type="text" name="geoSearch" value="<?php
					if ( ! empty( $_GET['geoSearch'] ) ) {
						echo $_GET['geoSearch'];
					} ?>"/></label>
            </div>
            <div class="submit">
                <input type="submit" class="button-primary"
                       onClick="document.forms['geoEdit'].elements['geodata'].value='';document.forms['geoEdit'].elements['geoid'].value='';"
                       value="<?php
				       _e( 'Search', 'wpGeonames' ) ?>"/>
            </div>

			<?php
			echo $o; ?>
        </form>

		<?php
	}

	public function admin_general( $wpGeoList ) {

		global $wpdb;
		global $geoManual;
		global $geoVersion;

		$zip     = '';
		$geoToka = wp_create_nonce( 'geoToka' );

		if ( isset( $_POST['wpGeonamesClear'] ) ) {
			$zip = '<p style="font-weight:700;color:#D54E21;">' . $this->clearLocations() . '</p>';
		} elseif ( isset( $_POST['wpGeonamesPostalAdd'] ) ) {
			$zip = '<p style="font-weight:700;color:#D54E21;">' . $this->postalAddZip( self::urlPostal, $_POST ) . '</p>';
		} elseif ( isset( $_POST['wpGeonamesPostalClear'] ) ) {
			$zip = '<p style="font-weight:700;color:#D54E21;">' . $this->clearPostCodes() . '</p>';
		}

		echo $zip;

		?>

        <link rel="stylesheet" href="<?php
		echo plugins_url(); ?>/wp-geonames/sumoselect/sumoselect.css"
              type="text/css"
              media="all"/>
        <div class='icon32' id='icon-options-general'><br/></div>
        <div>
            <a style="float:right;margin:20px;" href="http://www.geonames.org/"><img
                        src="<?php
						echo plugins_url( 'wp-geonames/images/geonames.png' ); ?>" alt="GeoNames"
                        title="GeoNames"/></a>
        </div>
        <h2>WP GeoNames&nbsp;<span style='font-size:80%;'>v<?php
				echo $geoVersion; ?></span></h2>
        <p>
			<?php
			_e(
				'This plugin allows to insert into the database the millions of places available free of charge on the GeoNames website.',
				'wpGeonames'
			); ?>
        </p>
        <div id="wpGeonamesAddStatus"><img alt="loading" id="wpGeonameAddImg"
                                           src="<?php
		                                   echo plugins_url( 'wp-geonames/images/loading.gif' ); ?>"
                                           style="display:none;"/></div>
        <div style="clear:both;"></div>
        <hr/>
        <h2><?php
			_e( 'Countries', 'wpGeonames' ); ?></h2>
		<?php

		$cc = '';
		if ( $wpGeoList['countries'] ) {
			foreach ( $wpGeoList['countries'] as $country => $count ) {
				$cc .= $country . ' (<span style="color:#D54E21;">' . $count . '</span>)&nbsp;&nbsp;';
			}
		}

		echo '<p>' . __(
				'Number of data in this database',
				'wpGeonames'
			) . ' : <span style="font-weight:700;color:#D54E21;">' . $wpdb->get_var(
				"SELECT COUNT(*) FROM " . $wpdb->base_prefix . "geonames"
			) . '</span><a style="margin-left:10px;" href="options-general.php?page=wpGeonames-options&checkData=1"><img alt="reload" src="' . plugins_url(
			     'wp-geonames/images/reload.png'
		     ) . '" style="vertical-align:middle;" /></a></p>';
		echo '<p>' . __(
				'List of countries in this database',
				'wpGeonames'
			) . ' : <span style="font-weight:700;font-size:11px;">' . $cc . '</span></p>';

		unset ( $country, $count, $cc );
		?>

        <form method="post" id="wpGeonames_options1" name="wpGeonames_options1"
              action="options-general.php?page=wpGeonames-options&geoToka=<?php
		      echo $geoToka; ?>">
            <table class="form-table" style="width: auto;">
                <tr style="vertical-align: top;">
                    <th scope="row"><label
                                for="wpGeonamesAdd"><?php
							_e( 'Add data to WordPress', 'wpGeonames' ); ?></label>
                    </th>
                    <td>
						<?php
						if ( ! empty( $wpGeoList['filenames']['countries'] ) || ! empty( $geoManual ) ) { ?>
                            <select name="wpGeonamesAdd" id="wpGeonamesAdd" multiple="multiple">
								<?php

								$ignoreFiles = [
									'allCountries.zip',
									'alternateNames.zip',
									'alternateNamesV2.zip',
									'hierarchy.zip',
									'adminCode5.zip',
									'userTags.zip',
									'shapes_all_low.zip',
									'shapes_simplified_low.json.zip',
								];

								if ( empty( $geoManual ) ) {

									foreach ( $wpGeoList['filenames']['countries'] as $country => $info ) {
										if ( ! isset( $ignoreFiles[ $country ] ) ) {
											echo '<option value="' . $country . '">' . $country . " ({$info['size']} - {$info['date']})" . '</option>';
										}
									}
								} else {
									echo '<option value="geoManual">local : ' . $geoManual . '</option>';
								} ?>

                            </select>
							<?php
						} else {
							echo '<span style="font-weight:700;color:#D54E21;">' . __(
									'No connection available or issue with PHP file_get_contents(url)',
									'wpGeonames'
								) . '</span>';
						} ?>

                    </td>
                    <td><a href="https://en.wikipedia.org/wiki/ISO_3166-1"
                           target="_blank"><?php
							_e( 'Official Country List', 'wpGeonames' ); ?></a></td>
                    <td>
                    </td>
                </tr>
                <tr style="vertical-align: top;">
                    <th scope="row"><label><?php
							_e( 'Choose columns to insert', 'wpGeonames' ); ?></label></th>
                    <td style="width:250px;">
                        <label>
                            <input type="checkbox" name="wpGeo0" value="1" checked disabled/>
                            <span style="color:#bb2;">
                                <?php
                                _e( 'ID', 'wpGeonames' ); ?></span></label><br>
                        <label>
                            <input type="checkbox" name="feature_class" value="1" checked disabled/>
                            <span style="color:#bb2;">
                            <?php
                            _e( 'Feature Class', 'wpGeonames' ); ?></span></label><br>
                        <label>
                            <input type="checkbox" name="feature_code" value="1" checked disabled/>
                            <span style="color:#bb2;">
                            <?php
                            _e( 'Feature Code', 'wpGeonames' ); ?></span></label><br>
                        <label>
                            <input type="checkbox" name="wpGeo1" value="1" checked disabled/>
                            <span style="color:#bb2;">
                            <?php
                            _e( 'Name', 'wpGeonames' ); ?></span></label><br>
                        <label>
                            <input type="checkbox" name="ascii_name" value="1"/>
							<?php
							_e( 'Ascii Name', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="alternate_names" value="1"/>
							<?php
							_e( 'Alternate Names', 'wpGeonames' ); ?></label><br>
                    </td>
                    <td>
                        <label>
                            <input type="checkbox" name="country_code" value="1" checked disabled/>
                            <span style="color:#bb2;">
                            <?php
                            _e( 'Country Code', 'wpGeonames' ); ?></span></label><br>
                        <label>
                            <input type="checkbox" name="cc2" value="1" checked/>
							<?php
							_e( 'Country Code2', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="admin1_code" value="1" checked/>
							<?php
							_e( 'Admin1 Code', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="admin2_code" value="1" checked/>
							<?php
							_e( 'Admin2 Code', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="admin3_code" value="1"/>
							<?php
							_e( 'Admin3 Code', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="admin4_code" value="1"/>
							<?php
							_e( 'Admin4 Code', 'wpGeonames' ); ?></label><br>
                    </td>
                    <td>
                        <label>
                            <input type="checkbox" name="population" value="1"/>
							<?php
							_e( 'Population', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="elevation" value="1"/>
							<?php
							_e( 'Elevation', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="dem" value="1"/>
							<?php
							_e( 'Digital Elevation Model', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="latitude" value="1" checked/>
							<?php
							_e( 'Latitude', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="longitude" value="1" checked/>
							<?php
							_e( 'Longitude', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="timezone" value="1"/>
							<?php
							_e( 'Timezone', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="modification_date" value="1" checked disabled/>
                            <span style="color:#bb2;">
                            <?php
                            _e( 'Modification Date', 'wpGeonames' ); ?></span></label><br>
                    </td>
                </tr>
                <tr style="vertical-align: top;">
                    <th scope="row"><label><?php
							_e( 'Choose type of data to insert', 'wpGeonames' ); ?></label>
                    </th>
                    <td style="width:250px;">
                        <label>
                            <input type="checkbox" name="wpGeoA" value="1" checked/>
							<?php
							_e( 'A : country, state, region', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="wpGeoH" value="1"/>
							<?php
							_e( 'H : stream, lake', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="wpGeoL" value="1"/>
							<?php
							_e( 'L : parks,area', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="wpGeoR" value="1"/>
							<?php
							_e( 'R : road, railroad', 'wpGeonames' ); ?></label><br>
                    </td>
                    <td>
                        <label>
                            <input type="checkbox" name="wpGeoP" value="1" checked/>
							<?php
							_e( 'P : city, village', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="wpGeoCity" value="1" checked/>
							<?php
							_e( 'P* : just city', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="wpGeoS" value="1"/>
							<?php
							_e( 'S : spot, building, farm', 'wpGeonames' ); ?></label><br>
                    </td>
                    <td>
                        <label>
                            <input type="checkbox" name="wpGeoT" value="1"/>
							<?php
							_e( 'T : mountain,hill,rock', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="wpGeoU" value="1"/>
							<?php
							_e( 'U : undersea', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="wpGeoV" value="1"/>
							<?php
							_e( 'V : forest,heath', 'wpGeonames' ); ?></label><br>
                    </td>
                </tr>
                <tr style="vertical-align: top;">
                    <th scope="row"><label><?php
							_e( 'Other options', 'wpGeonames' ); ?></label></th>
                    <td style="width:250px;">
                        <label>
                            <input type="checkbox" name="wpGeoForce" value="1"/>
							<?php
							_e( 'Force reload from geonames.org', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="wpGeoDeleteFiles" value="1"/>
							<?php
							_e( 'Keep files', 'wpGeonames' ); ?></label><br>
                    </td>
                    <td>
                    </td>
                    <td>
                    </td>
                </tr>
				<?php
				if ( ! empty( $wpGeoList['filenames']['countries'] ) || ! empty( $geoManual ) ) { ?>
                    <tr style="vertical-align: top;">
                        <td>
                            <div class="button-primary" style="width: 150px;" onclick="wpGeonames_addCountries(0);">
								<?php
								_e( 'Add', 'wpGeonames' ) ?></div>
                            <br>
							<?php
							_e( 'Skip existing', 'wpGeonames' ) ?>
                        </td>
                        <td>
                            <div class="button-primary" style="width: 150px;" onclick="wpGeonames_addCountries(1);">
								<?php
								_e( 'Add and Update', 'wpGeonames' ) ?></div>
                            <br>
							<?php
							_e( 'Only replace selected fields, keep others', 'wpGeonames' ) ?>
                        </td>
                        <td>
                            <div class="button-primary" style="width: 150px;" onclick="wpGeonames_addCountries(2);">
								<?php
								_e( 'Add and Replace', 'wpGeonames' ) ?></div>
                            <br>
							<?php
							_e( 'Replace all fields (clearing existing ones)', 'wpGeonames' ) ?>
                        </td>
                        <td>
                            <div class="button-primary" style="width: 150px;"
                                 onclick="wpGeonames_addCountries(-1);">
								<?php
								_e( 'Update', 'wpGeonames' ) ?></div>
                            <br>
							<?php
							_e( 'Only update selected fields of existing records', 'wpGeonames' ) ?>
                        </td>
                    </tr>
					<?php
				} ?>
            </table>
        </form>

        <form method="post" name="wpGeonames_options2"
              action="options-general.php?page=wpGeonames-options&geoToka=<?php
		      echo $geoToka; ?>">
            <input type="hidden" name="wpGeonamesClear" value="1"/>
            <p class="submit">
                <input type="submit" class="button-primary"
                       value="<?php
				       _e( 'Clear this table (TRUNCATE)', 'wpGeonames' ) ?>"/>
            </p>
        </form>
        <hr/>
        <div id="wpGeonamesPostalAddStatus"><img alt="add" id="wpGeonamePostalAddImg"
                                                 src="<?php
		                                         echo plugins_url( 'wp-geonames/images/loading.gif' ); ?>"
                                                 style="display:none;"/></div>
        <h2><?php
			_e( 'Postal codes', 'wpGeonames' ); ?></h2>
		<?php
		$cc = '';
		if ( $wpGeoList ) {
			foreach ( $wpGeoList as $country => $count ) {
				if ( strlen( $country ) == 3 ) {
					$cc .= substr( $country, 1 ) . ' (<span style="color:#D54E21;">' . $count . '</span>)&nbsp;&nbsp;';
				}
			}
		}
		echo '<p>' . __(
				'Number of data in this database',
				'wpGeonames'
			) . ' : <span style="font-weight:700;color:#D54E21;">'
		     . $wpdb->get_var( "SELECT COUNT(*) FROM " . $wpdb->base_prefix . "geonamesPostal" )
		     . '</span><a style="margin-left:10px;" href="options-general.php?page=wpGeonames-options&checkData=1"><img alt="reload" src="' . plugins_url(
			     'wp-geonames/images/reload.png'
		     ) . '" style="vertical-align:middle;" /></a></p>';
		echo '<p>' . __(
				'List of countries in this database',
				'wpGeonames'
			) . ' : <span style="font-weight:700;font-size:11px;">' . $cc . '</span></p>';
		?>

        <form method="post" id="wpGeonames_options3" name="wpGeonames_options3"
              action="options-general.php?page=wpGeonames-options&geoToka=<?php
		      echo $geoToka; ?>">
            <table class="form-table">
                <tr style="vertical-align: top;">
                    <th scope="row"><label
                                for="wpGeonamesPostalAdd"><?php
							_e( 'Add data to WordPress', 'wpGeonames' ); ?></label>
                    </th>
                    <td>
						<?php
						if ( ! empty( $wpGeoList['filenames']['postal'] ) ) { ?>
                            <select name="wpGeonamesPostalAdd" id="wpGeonamesPostalAdd" multiple="multiple">
								<?php
								foreach ( $wpGeoList['filenames']['postal'] as $country ) {
									if ( strlen( $country ) == 6 && substr( $country, 2 ) == '.zip' ) {
										echo '<option value="' . $country . '">' . $country . '</option>';
									}
								} ?>

                            </select>
							<?php
						} else {
							echo '<span style="font-weight:700;color:#D54E21;">' . __(
									'No connection available or issue with PHP file_get_contents(url)',
									'wpGeonames'
								) . '</span>';
						} ?>

                    </td>
                </tr>
                <tr style="vertical-align: top;">
                    <th scope="row"><label><?php
							_e( 'Choose columns to insert', 'wpGeonames' ); ?></label></th>
                    <td style="width:250px;">
                        <label>
                            <input type="checkbox" name="wpGeoPostal0" value="1" checked disabled/>
                            <span style="color:#bb2;">
                            <?php
                            _e( 'Country Code', 'wpGeonames' ); ?></span></label><br>
                        <label>
                            <input type="checkbox" name="wpGeoPostal1" value="1" checked disabled/>
                            <span style="color:#bb2;">
                            <?php
                            _e( 'Postal Code', 'wpGeonames' ); ?></span></label><br>
                        <label>
                            <input type="checkbox" name="wpGeoPostal2" value="1" checked disabled/>
                            <span style="color:#bb2;">
                            <?php
                            _e( 'Name', 'wpGeonames' ); ?></span></label><br>
                        <label>
                            <input type="checkbox" name="wpGeoPostal3" value="1"/>
							<?php
							_e( 'Admin1 Name', 'wpGeonames' ); ?></label><br>
                    </td>
                    <td>
                        <label>
                            <input type="checkbox" name="wpGeoPostal4" value="1"/>
							<?php
							_e( 'Admin1 Code', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="wpGeoPostal5" value="1"/>
							<?php
							_e( 'Admin2 Name', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="wpGeoPostal6" value="1"/>
							<?php
							_e( 'Admin2 Code', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="wpGeoPostal7" value="1"/>
							<?php
							_e( 'Admin3 Name', 'wpGeonames' ); ?></label><br>
                    </td>
                    <td>
                        <label>
                            <input type="checkbox" name="wpGeoPostal8" value="1"/>
							<?php
							_e( 'Admin3 Code', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="wpGeoPostal9" value="1"/>
							<?php
							_e( 'Latitude', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="wpGeoPostal10" value="1"/>
							<?php
							_e( 'Longitude', 'wpGeonames' ); ?></label><br>
                        <label>
                            <input type="checkbox" name="wpGeoPostal11" value="1"/>
							<?php
							_e( 'Accuracy', 'wpGeonames' ); ?></label><br>
                    </td>
                </tr>
            </table>
            <div class="button-primary" onclick="wpGeonames_addPostal();"><?php
				_e( 'Add', 'wpGeonames' ) ?></div>
        </form>
        <form method="post" name="wpGeonames_options4"
              action="options-general.php?page=wpGeonames-options&geoToka=<?php
		      echo $geoToka; ?>">
            <input type="hidden" name="wpGeonamesPostalClear" value="1"/>
            <p class="submit">
                <input type="submit" class="button-primary"
                       value="<?php
				       _e( 'Clear this table (TRUNCATE)', 'wpGeonames' ) ?>"/>
            </p>
        </form>
        <hr/>
		<?php
		_e( 'To know how to use the data, look at the readme.txt file.', 'wpGeonames' ); ?>

        <script type="text/javascript"
                src="<?php
		        echo plugins_url(); ?>/wp-geonames/sumoselect/jquery.sumoselect.min.js"></script>
        <script type="text/javascript">
            jQuery(document).ready(function () {
                jQuery('#wpGeonamesAdd').SumoSelect({
                    placeholder: '<?php _e( 'Country list', 'wpGeonames' ); ?>',
                    captionFormat: '{0} <?php _e( 'Selected', 'wpGeonames' ); ?>'
                });
                jQuery('#wpGeonamesPostalAdd').SumoSelect({
                    placeholder: '<?php _e( 'Postal list', 'wpGeonames' ); ?>',
                    captionFormat: '{0} <?php _e( 'Selected', 'wpGeonames' ); ?>'
                });
            });

            function wpGeonames_addCountries(mode) {
                window.scrollTo(0, 0);
                let a = '', b = [];
                jQuery("#wpGeonames_options1 input[type=checkbox]").each(function () {
                    if (jQuery(this).is(":checked")) a += jQuery(this).attr('name') + ',';
                });
                jQuery("#wpGeonamesAdd option:selected").each(function (i) {
                    b[i] = jQuery(this).val();
                });
                jQuery("#wpGeonameAddImg").show();
                wpGeonames_nextCountry(mode, 0, a, b);
            }

            function wpGeonames_nextCountry(mode, i, a, b) {
                if (i < b.length) wpGeonames_addCountry(mode, i, a, b);
                else {
                    jQuery('#wpGeonamesAdd')[0].sumo.unSelectAll();
                    jQuery("#wpGeonameAddImg").hide();
                    window.location.reload();
                }
            }

            function wpGeonames_addCountry(mode, i, a, b) {
                jQuery.post('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
                    'action': 'wpGeonamesAddCountry',
                    'mode': mode,
                    'frm': a,
                    'file': b[i],
                    'url': '<?php echo self::urlLocations; ?>',
                    'geoToka': '<?php echo $geoToka; ?>'
                }, function (r) {
                    jQuery("#wpGeonamesAddStatus").append(r.substring(0, r.length - 1));
                    wpGeonames_nextCountry(mode, i + 1, a, b);
                });
            }

            function wpGeonames_addPostal() {
                let a = '', b = [];
                jQuery("#wpGeonames_options3 input[type=checkbox]").each(function () {
                    if (jQuery(this).is(":checked")) a += jQuery(this).attr('name') + ',';
                });
                jQuery("#wpGeonamesPostalAdd option:selected").each(function (i) {
                    b[i] = jQuery(this).val();
                });
                jQuery("#wpGeonamePostalAddImg").show();
                wpGeonames_nextPostal(0, a, b);
            }

            function wpGeonames_nextPostal(i, a, b) {
                if (i < b.length) wpGeonames_addPost(i, a, b);
                else {
                    jQuery('#wpGeonamesPostalAdd')[0].sumo.unSelectAll();
                    jQuery("#wpGeonamePostalAddImg").hide();
                    window.location.reload();
                }
            }

            function wpGeonames_addPost(i, a, b) {
                jQuery.post('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
                    'action': 'wpGeonamesAddPostal',
                    'frm': a,
                    'file': b[i],
                    'url': '<?php echo self::urlPostal; ?>',
                    'geoToka': '<?php echo $geoToka; ?>'
                }, function (r) {
                    jQuery("#wpGeonamesPostalAddStatus").append(r.substring(0, r.length - 1));
                    wpGeonames_nextPostal(i + 1, a, b);
                });
            }
        </script>
		<?php
	}

	public function admin_help() {
		?>
        <h2><?php
			_e( 'Location Taxonomy Form', 'wpGeonames' ); ?></h2>
        <p><?php
			_e(
				'You can create a simple location taxonomy Form with the shortcode <b>[wp-geonames]</b>. The options are as follows :',
				'wpGeonames'
			); ?></p>
        <ul style="margin-left:30px;list-style:disc;">
            <li><?php
				_e( 'Name and ID of the select Country field (default=geoCountry) : id1=country', 'wpGeonames' ); ?></li>
            <li><?php
				_e( 'Name and ID of the select Region field (default=geoRegion) : id2=state', 'wpGeonames' ); ?></li>
            <li><?php
				_e( 'Name and ID of the input City field (default=geoCity) : id3=city', 'wpGeonames' ); ?></li>
            <li><?php
				_e( 'Name of the JSON output var (default=geoRow) : out=citydata', 'wpGeonames' ); ?></li>
            <li><?php
				_e( 'Max number of proposal city (default=10) : nbcity=5', 'wpGeonames' ); ?></li>
            <li><?php
				_e( 'Display the OpenStreetMap (default=0) : map=1', 'wpGeonames' ); ?></li>
            <li><?php
				_e( 'OpenStreetMap initial zoom (default=9) : zoom=10', 'wpGeonames' ); ?></li>
        </ul>
        <p><?php
			_e( 'Example : <b>[wp-geonames zoom=12 map=1 id1=ctr id2=reg id3=cit]</b>.', 'wpGeonames' ); ?></p>
        <p><?php
			_e(
				'You can also adapt the form to your style by changing the <u>templates/wp-geonames_location_taxonomy.php</u> file and moving it to your theme.',
				'wpGeonames'
			); ?></p>
        <p>Enjoy ! <?php
			echo convert_smilies( ';-)' ); ?></p>
		<?php
	}

	public function ajax_geoDataCity() {
		// AJAX Templates
		global $wpdb;
		$Piso = preg_replace( "/[^a-zA-Z0-9_,-]/", "", $_POST['country'] );
		$Preg = sanitize_text_field( $_POST['region'] );
		$Pcit = sanitize_text_field( $_POST['city'] );
		$Pnb  = ( ! empty( $_POST['nbcity'] )
			? intval( $_POST['nbcity'] )
			: 10 );
		//
		$result = [];
		if ( $Piso ) {
			$a = "admin1_code";
			if ( $this->regionCode2( $Piso ) ) {
				$a = "admin2_code";
			}
			$result = $wpdb->get_results(
				"SELECT
				geoname_id,
				name,
				latitude,
				longitude
			FROM
				" . $this->tblLocations . "
			WHERE
				feature_class='P'
				and ((country_code='" . $Piso . "' and " . $a . "='" . $Preg . "') or country_code='" . $Preg . "')
				and name LIKE '" . $Pcit . " % '
			ORDER BY name
			LIMIT " . $Pnb
			);
		}
		echo json_encode( $result );
	}

	public function ajax_geoDataRegion() {
		// AJAX Templates
		global $wpdb;
		$Piso = preg_replace( "/[^a-zA-Z0-9_,-]/", "", $_POST['country'] );
		//
		$result = [];
		if ( $Piso ) {
			$a = "admin1_code";
			$b = "ADM1";
			if ( $this->regionCode2( $Piso ) ) {
				$a = "admin2_code";
				$b = "ADM2";
			}
			$q = $wpdb->get_results(
				"SELECT
				geoname_id,
				name,
				" . $a . " AS regionid
			FROM
				" . $this->tblLocations . "
			WHERE
				feature_class='A' and feature_code='" . $b . "' and (country_code='" . $Piso . "' or cc2='" . $Piso . "')
					or
				feature_class='A' and feature_code='PCLD' and cc2='" . $Piso . "'
			ORDER BY name
			"
			);
			$c = [];
			foreach ( $q as $r ) {
				if ( $r->regionid == '00' ) {
					$r->regionid = $r->country_code;
				}
				if ( ! isset( $c[ $r->name ] ) ) {
					$result[]      = $r;
					$c[ $r->name ] = 1;
				}
			}
		}
		echo json_encode( $result );
	}

	public function ajax_get_city_by_country_region() {
		// AJAX Admin
		// input : $_POST iso, region, city
		if ( $this->verifyToka() ) {
			return;
		}

		global $wpdb;

		$Piso        = preg_replace( "/[^a-zA-Z0-9_,-]/", "", $_POST['iso'] );
		$Pregion     = sanitize_text_field( $_POST['region'] );
		$Pcity       = sanitize_text_field( $_POST['city'] );
		$adminColumn = $this->regionCode2( $Piso )
			? "admin2_code"
			: "admin1_code";

		$result = $wpdb->get_results(
			<<<SQL
        SELECT
			geoname_id,
			name,
			latitude,
			longitude,
			feature_code
		FROM
			{$this->tblLocations}
		WHERE
			((country_code='$Piso' and $adminColumn='$Pregion')
			or country_code='$Pregion')
			and feature_class IN ('A', 'P')
			and name LIKE '$Pcity%'
		ORDER BY name
		LIMIT 10;
SQL
		);
		echo json_encode( $result );
	}

	public function ajax_wpGeonamesAddLocation() {
		// AJAX Admin
		if ( $this->verifyToka() ) {
			return;
		}

		$postMode = strip_tags( $_POST['mode'] );
		$postFile = sanitize_text_field( $_POST['file'] );
		$postForm = strip_tags( $_POST['frm'] );
		$postUrl  = strip_tags( stripslashes( filter_var( $_POST['url'], FILTER_SANITIZE_URL ) ) );

		$array    = explode( ',', $postForm );
		$postForm = array_fill_keys( array_filter( $array ), 1 );

		$postForm['wpGeonamesAdd'] = $postFile;

		$this->addLocationsFromForm( $postMode, $postUrl, $postForm );

		echo ' <span style = "color:green;font-weight:700;margin:0 4px;">' . substr( $postFile, 0, - 4 ) . '</span>';
	}

	function ajax_wpGeonamesAddPostCode() {
		// AJAX Admin
		if ( $this->verifyToka() ) {
			return;
		}
		$Pfil = sanitize_text_field( $_POST['file'] );
		$Pfrm = strip_tags( $_POST['frm'] );
		$Purl = strip_tags( stripslashes( filter_var( $_POST['url'], FILTER_SANITIZE_URL ) ) );
		//
		$a = explode( ',', $Pfrm );
		$b = [];
		foreach ( $a as $r ) {
			if ( $r ) {
				$b[ $r ] = 1;
			}
		}
		$b['wpGeonamesPostalAdd'] = $Pfil;
		$this->postalAddZip( $Purl, $b );
		echo '<span style = "color:green;font-weight:700;margin:0 4px;">' . substr( $Pfil, 0, - 4 ) . '</span>';
	}

	/**
	 * @param $apiResult
	 *
	 * @return mixed
	 * @throws ErrorException
	 */
	public function cacheSearchResult( ApiQueryStatus $apiResult ) {

		$searchTerm  = $apiResult->query->getSearchTerm();
		$country     = $apiResult->query->getSingleCountry();
		$search_type = ApiQuery::translateSearchType( $apiResult->type );

		if ( empty( $search_type ) ) {
			throw new ErrorException( 'Search type not supported' );
		}

		if ( false === Core::$wpdb->insert(
				self::$instance->tblCacheQueries,
				[
					'search_term'    => $searchTerm,
					'search_type'    => $search_type,
					'search_country' => $country,
					'search_params'  => serialize( $apiResult->query->cleanArray( $apiResult->params, true ) ),
					'result_count'   => count( $apiResult->result ),
					'result_total'   => $apiResult->total,
				],
				[
					'%s', // search_term
					'%s', // search_type
					'%s', // search_country
					'%s', // search_params
					'%d', // result_count
					'%d', // result_total
				]
			) ) {
			die( Core::$wpdb->last_error );
		}
		$cachedQuery = (object) [ 'query_id' => Core::$wpdb->insert_id ];

		$recordsToCache = array_diff_key( $apiResult->result, $apiResult->results );

		array_walk(
			$recordsToCache,
			static function ( wpGeonamesLocation $location ) {

				if ( false === Core::$wpdb->replace(
						self::$instance->tblCacheLocations,
						[
							'geoname_id'    => $location->geonameId,
							'name'          => $location->name,
							'feature_class' => $location->featureClass,
							'feature_code'  => $location->featureCode,
							'country_code'  => $location->countryCode,
							'latitude'      => $location->latitude,
							'longitude'     => $location->longitude,
							'population'    => $location->population,
							'admin1_code'   => $location->getAdminCode1(),
							'admin2_code'   => $location->getAdminCode2(),
							'admin3_code'   => $location->getAdminCode3(),
							'admin4_code'   => $location->getAdminCode4(),
						],
						[
							'%d', // geoname_id
							'%s', // name
							'%s', // feature_class
							'%s', // feature_code
							'%s', // country_code
							'%f', // latitude
							'%f', // longitude
							'%d', // population
							'%s', // admin1_code
							'%s', // admin2_code
							'%s', // admin3_code
							'%s', // admin4_code
						]
					) ) {
					throw new ErrorException( Core::$wpdb->last_error );
				}
			}
		);

		unset ( $recordsToCache );

		array_walk(
			$apiResult->result,
			static function (
				wpGeonamesLocation $location,
				$i
			)
			use (
				&
				$cachedQuery,
				&
				$self
			) {

				if ( false === Core::$wpdb->insert(
						self::$instance->tblCacheResults,
						[
							'query_id'     => $cachedQuery->query_id,
							'geoname_id'   => $location->geonameId,
							'order'        => $i,
							'country_code' => $location->countryCode,
						],
						[
							'%d', // query_id
							'%d', // geoname_id
							'%d', // order
							'%s', // country_code
						]
					) ) {
					throw new ErrorException( Core::$wpdb->last_error );
				}
			}
		);

		return $apiResult;
	}

	public function checkArray(
		$name,
		$key,
		$value = null
	) {

		$getter = 'get' . ucfirst( $name );
		$array  = $this->$getter();
		$new    = $value
			?: $key;

		if ( key( $array ) === 0 ) {
			$array = array_fill_keys( $array, null );
		}

		if ( array_key_exists( $key, $array )
		     && $array [ $key ] === $new
		) {
			return true;
		}

		$array[ $key ] = $new;

		self::saveArray( $name, $array );

		return true;
	}

	public function checkCountry(
		$country_code,
		$country_name = null
	) {
		return $this->checkArray( 'countryCodes', $country_code, $country_name );
	}

	public function checkSearchParams(
		?array $params,
		ApiQueryStatus $apiResult
	): ?array {

		if ( $params === null ) {
			return null;
		}

		if ( $apiResult->query->getStartRow() + $apiResult->query->getmaxRows() <= $apiResult->processRecords ) {
			return null;
		}

		/** @var Core $self */
		$self = self::$instance;

		$singleCountry = $apiResult->query->getSingleCountry();
		$searchCountry = $singleCountry
			? self::$wpdb->prepare( 'OR search_country = %s', $singleCountry )
			: '';

		$sql = <<<SQL
SELECT
    *
FROM
    {$self->tblCacheQueries}
WHERE
       search_term = %s
   AND search_type = %s
   AND (search_country IS NULL $searchCountry)
ORDER BY
    search_country IS NULL
;
SQL;

		$sql = self::$wpdb->prepare(
			$sql,
			$apiResult->query->getSearchTerm(),
			ApiQuery::translateSearchType( $apiResult->type )
		);

		$myParams        = $apiResult->query->cleanArray( $params, true );
		$serializedQuery = serialize( $myParams );
		$cachedQuery     = null;
		$cachedQueries   = self::$wpdb->get_results( $sql );

		foreach ( $cachedQueries as $i => &$cachedQuery ) {

			// check if exact same query
			if ( $cachedQuery->search_params === $serializedQuery ) {
				break;
			}

			$searchCountry = $apiResult->query->getCountryAsArray();

			// bail if we search all countries, but current query uses a specific country
			if ( empty( $searchCountry ) && $cachedQuery->search_country ) {
				$cachedQuery = null;
				continue;
			}

			$cachedQuery->search_params = new ApiQuery( unserialize( $cachedQuery->search_params ) );
			$cachedCountry              = $cachedQuery->search_params->getCountryAsArray();

			// use cached query if countries match
			if ( $searchCountry === $cachedCountry ) {
				break;
			}

			// bail if we search all countries, but current query uses specific countries
			if ( empty( $searchCountry ) && ! empty( $cachedCountry ) ) {
				$cachedQuery = null;
				continue;
			}

			// ignore incomplete caches
			if ( $cachedQuery->result_count === $cachedQuery->search_params->getMaxStartRow()
			     || $cachedQuery->result_count < $cachedQuery->result_total ?? 0
			) {
				$cachedQuery = null;
				continue;
			}

			// bail early, if not all the countries are in the cache
			if ( ! empty( $cachedCountry ) && $searchCountry !== array_intersect( $searchCountry, $cachedCountry ) ) {

				// only disregard if not one single country is matching
				if ( count( array_intersect( $searchCountry, $cachedCountry ) ) === 0 ) {
					$cachedQuery = null;
				}

				continue;
			}

			// cache does include all the searched countries
			break;
		}

		if ( $cachedQuery === null ) {

			$cachedQueries = array_filter( $cachedQueries );

			if ( empty( $cachedQueries ) ) {
				return $params;
			}

			$x = $searchCountry;

			foreach ( $cachedQueries as $cachedQuery ) {
				$x = array_diff( $x, $cachedQuery->search_params->getCountryAsArray() );

				if ( empty( $x ) ) {
					break;
				}
			}

			if ( ! empty( $x ) ) {
				return $params;
			}

			unset( $x );
			$cachedQuery = null;
		} else {
			$cachedQueries = [ $cachedQuery ];
		}

		// count the sum of records of all queries
		$cached = array_sum(
			array_map(
				static function ( $cachedQuery ) {
					return $cachedQuery->result_count;
				},
				$cachedQueries
			)
		);

		// ignore cache and current searchType if no records
		if ( $cached === 0 ) {
			return null;
		}

		if ( $cachedQuery === null ) {

			$inCountryCode = "'" . join( "','", $searchCountry ) . "'";
			$inQueryId     = join(
				",",
				array_map(
					static function ( $cachedQuery ) {
						return $cachedQuery->query_id;
					},
					$cachedQueries
				)
			);

			$sql = <<<SQL
SELECT
    country_code, count(DISTINCT geoname_id) as count
FROM
    {$self->tblCacheResults}
WHERE
       query_id IN ($inQueryId)
   AND country_code IN ($inCountryCode)
GROUP BY 
    country_code
ORDER BY
    country_code
;
SQL;

			$countryRecordCount = self::$wpdb->get_results( $sql );

			$cached = array_sum(
				array_map(
					static function ( $c ) {
						return $c->count;
					},
					$countryRecordCount
				)
			);
		}

		$start = $apiResult->query->getStartRow();

		if ( $start > $cached ) {
			$apiResult->processRecords += $cached;

			return null;
		}

		$start -= $apiResult->processRecords;
		$limit = $apiResult->query->getMaxRows() - count( $apiResult->result );

		$get = [
			'searchCountry' => $searchCountry,
			'queries'       => $cachedQueries,
		];

		$result = self::getCachedQuery( $get, $start, $limit );

		$apiResult->result += wpGeonamesLocation::parseArray( $result );

		return null;
	}

	public function checkSearchParamsMinRequirements(
		?array $params,
		ApiQueryStatus $apiResult
	) {

		if ( $params === null ) {
			return null;
		}

		$searchType = $apiResult->type;
		$searchTerm = $apiResult->query->getSearchTerm();
		$len        = strlen( $searchTerm );

		$minAllCountries = $minMultipleCountries = $minSingleCountry = 1;

		switch ( $searchType ) {

			case ApiQuery::SEARCH_TYPE_Q:
				$minSingleCountry     = 3;
				$minMultipleCountries = 4;
				$minAllCountries      = 6;
				break;

			case ApiQuery::SEARCH_TYPE_START_OF_NAME:
				$minSingleCountry     = 3;
				$minMultipleCountries = 3;
				$minAllCountries      = 4;
				break;

			case ApiQuery::SEARCH_TYPE_NAME:
			case ApiQuery::SEARCH_TYPE_FUZZY_NAME:
				$minSingleCountry     = 2;
				$minMultipleCountries = 4;
				$minAllCountries      = 5;
				break;

			case ApiQuery::SEARCH_TYPE_EXACT_NAME:
				$minAllCountries = 1;
				break;
		}

		if ( $len < $minAllCountries ) {
			return null;
		}

		$country = $params['country'] ?? null;

		if ( empty( $country ) ) {
			return $len >= $minAllCountries
				? $params
				: null;
		}

		if ( is_array( $country ) && count( $country ) === 1 ) {
			$country = reset( $country );
		}

		if ( is_string( $country ) ) {
			return $len >= $minSingleCountry
				? $params
				: null;
		}

		return $len >= $minMultipleCountries
			? $params
			: null;
	}

	public function checkTimeZone(
		$time_zone_id,
		$country_code
	) {
		return $this->checkArray( 'timeZones', $time_zone_id, $country_code );
	}

	public function check_options( $options ) {

		if ( empty( $options )
		     || empty( $options['filenames']['countries'] )
		     || empty( $options['filenames']['postal'] )
		     || time() - ( $options['filenames']['lastUpdate']
				?: 0 ) > 60 * 60 * 24
		) {
			return $this->update_options( true );
		}

		return $options;
	}

	public function clear( $table ) {

		global $wpdb;

		$this->verifyAdmin();

		if ( $this->verifyToka() ) {
			return false;
		}

		$q = $wpdb->query( "TRUNCATE TABLE " . $wpdb->base_prefix . $table );

		if ( $table === self::tblLocations ) {
			// ******* Patch V1.4 - Add INDEX **************
			$a = $wpdb->get_results( "SHOW INDEX FROM " . $this->tblLocations . " WHERE Column_name = 'cc2'" );
			if ( empty( $a ) ) {
				$wpdb->query(
					"ALTER TABLE " . $this->tblLocations . " ADD INDEX `index1` ( `feature_class`,`feature_code`( 3 ),`country_code`,`cc2`( 2 ),`name`( 3 ))"
				);
			}
			// *********************************************
		}

		$this->update_options( true );

		if ( $q ) {
			return __( 'Done, table is empty.', 'wpGeonames' );
		} else {
			return __( 'Failed !', 'wpGeonames' );
		}
	}

	public function clearCountries() {

		return $this->clear( self::tblCountries );
	}

	public function clearLocations() {

		return $this->clear( self::tblLocations );
	}

	public function clearPostCodes() {

		return $this->clear( self::tblPostCodes );
	}

	public function clearTimeZones() {

		return $this->clear( self::tblTimeZones );
	}

	public function creation_table() {
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
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ); // dbDelta()

		$wpdb = self::$wpdb;

		$this->inActivation = true;

		//
		$charset_collate = '';

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}

		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}

		$feature_classes = self::getFeatureClasses();
		$feature_classes = "'" . join( "', '", array_keys( $feature_classes ) ) . "'";

		$feature_codes = self::getFeatureCodes();
		$feature_codes = "'" . join( "', '", array_keys( $feature_codes ) ) . "'";

		$country_codes = self::getCountryCodes();
		$country_codes = "'" . join( "', '", array_keys( $country_codes ) ) . "'";

		$time_zones = self::getTimeZones();
		$time_zones = "'" . join( "', '", $time_zones ) . "'";

		// countries
		$nom = $this->tblCountries;

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$nom'" ) != $nom ) {
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
			dbDelta( $sql );
		}

		// time zones
		$nom = $this->tblTimeZones;

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$nom'" ) != $nom ) {
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
			dbDelta( $sql );
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

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$nom'" ) != $nom ) {
			dbDelta( sprintf( $sql, $nom ) );
		}

		// locations cache
		$nom = $this->tblCacheLocations;

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$nom'" ) != $nom ) {
			dbDelta( sprintf( $sql, $nom ) );
		}

		// locations cache queries
		$nom = $this->tblCacheQueries;

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$nom'" ) != $nom ) {

			$searchTypes = join( "','", ApiQuery::SEARCH_TYPES );

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
			dbDelta( $sql );
		}

		// locations cache results
		$nom = $this->tblCacheResults;

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$nom'" ) != $nom ) {
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
			dbDelta( $sql );
		}

		// post codes
		$nom = $this->tblPostCodes;

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$nom'" ) != $nom ) {
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
			dbDelta( $sql );
		}

		// Data
		$this->addNoCountries();
		$this->addCountries();
		$this->addTimezones();

		$sql = <<<SQL
INSERT LOW_PRIORITY INTO
    `wp_geonames_locations`
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
    `wp_geonames_countries`

ON DUPLICATE KEY UPDATE 
    ascii_name = country
;
SQL;

		$wpdb->query( $sql );

		$this->inActivation = false;
	}

	public function downloadZip(
		$name,
		$url,
		$filename = null,
		$force = false
	) {

		$zipFile = ! empty( $filename )
			? strip_tags( stripslashes( filter_var( $filename, FILTER_SANITIZE_URL ) ) )
			: null;
		if ( ! preg_match(
			'@(?:^|[/\\\\])([^/\\\\]+?)(\.[^./\\\\]*)?$@',
			$zipFile
				?: $url,
			$matches
		) ) {
			return false;
		}

		$base    = $matches[1];
		$ext     = $matches[2];
		$zipFile = "$base$ext";
		$txtFile = "$base.txt";

		if ( ! empty( $filename ) ) {
			$url .= $zipFile;
		}

		set_time_limit( 300 ); // default is 30

		$upl = wp_upload_dir();

		if ( $zipFile == 'geoManual' ) {
			global $geoManual;
			$upl = $upl['basedir'] . rtrim( $geoManual, DIRECTORY_SEPARATOR . '/' );
		} else {
			$upl = $upl['basedir'] . "/wp-geonames/$name";
		}

		if ( ! is_dir( $upl ) && ( ! mkdir( $upl, true ) || ! is_dir( $upl ) ) ) {
			_e( 'Could not create download directory.', 'wpGeonames' );
		}

		if ( $force || ! is_file( "$upl/$txtFile" ) ) {

			// 1. Get ZIP from URL - Copy to uploads/wp-geonames/$name/ folder
			if ( ! copy( $url, "$upl/$zipFile" ) ) {
				//$errors = error_get_last();
				_e( 'Failure in the download of the zip.', 'wpGeonames' );
				die();
			}

			switch ( $ext ) {
				case '.txt':
					break;

				case '.zip':
					// 2. Extract ZIP
					$zip = new ZipArchive();
					if ( $zip->open( "$upl/$zipFile" ) === true ) {
						$zip->extractTo( $upl );
						$zip->close();
						@unlink( "$upl/$zipFile" );
					} else {
						_e( 'Failure in the extraction of the zip.', 'wpGeonames' );
						die();
					}
					break;

				default:
					_e( 'Unknown file type' );
					die();
			}
		}

		// 3. Return filename
		return "$upl/$txtFile";
	}

	public function enqueue_leaflet() {
		wp_register_style( 'leaflet', plugins_url( 'wp-geonames/leaflet/leaflet.css' ) );
		wp_enqueue_style( 'leaflet' );
		wp_register_script( 'leaflet', plugins_url( 'wp-geonames/leaflet/leaflet.js' ), [], false, false );
		wp_enqueue_script( 'leaflet' );
	}

	public function loadFileIntoDb(
		$source,
		$table,
		$fields,
		$mode,
		$callback = null,
		$regexDelimiter = '@'
	) {

		$wpdb   = self::$wpdb;
		$handle = @fopen( $source, 'r' );

		if ( ! $handle ) {
			_e( 'Error reading file.', 'wpGeonames' );

			return false;
		}

		$regex  = join(
			'\t',
			array_filter(
				array_map(
					function (
						$field,
						$fieldName
					) {
						return $field->regex
							? ( $field->save
								? "(?<$fieldName>{$field->regex})"
								: $field->regex
							)
							: null;
					},
					$fields,
					array_keys( $fields )
				)
			)
		);
		$regex  = "${regexDelimiter}^$regex${regexDelimiter}x";
		$count  = 0;
		$fields = array_filter(
			$fields,
			function ( $field ) {
				return $field->save;
			}
		);

		while ( ( $line = fgets( $handle, 8192 ) ) !== false ) {

			if ( ! preg_match( $regex, $line, $matches ) ) {
				continue;
			}

			if ( $callback && ! $callback( $matches, $fields ) ) {
				continue;
			}

			++ $count;
			$fieldValues = [];

			array_walk(
				$fields,
				function (
					$field,
					$fieldName
				) use (
					&
					$matches,
					&
					$fieldValues,
					&
					$wpdb
				) {
					$fieldValues[] = "$fieldName = "
					                 . ( $matches[ $fieldName ] === ''
							? 'NULL'
							: $wpdb->prepare( "%{$field->format}", $matches[ $fieldName ] ) );
				}
			);

			$sqlValues = join( ', ', $fieldValues );

			switch ( $mode ) {
				case - 1:
					$sql = "UPDATE LOW_PRIORITY $table SET $sqlValues WHERE geoname_id = {$matches['geoname_id']};";
					break;

				case 0:
					$sql = "INSERT LOW_PRIORITY IGNORE $table SET $sqlValues;";
					break;

				case 1:
					$sql = "INSERT LOW_PRIORITY $table SET $sqlValues ON DUPLICATE KEY UPDATE $sqlValues;";
					break;

				case 2:
					$sql = "REPLACE LOW_PRIORITY $table SET $sqlValues;";
					break;

				default:
					$sql = '';
			}

			unset( $matches, $fieldValues, $sqlValues );

			if ( $sql && $wpdb->query( $sql ) === false ) {
				_e( "Error while updating data", 'wpGeonames' );
				echo "\n$wpdb->last_error\n";
				die( $sql );
			}
			unset( $sql );
		}

		fclose( $handle );

		return true;
	}

	public function postalAddDb( $g ) {
		if ( ! current_user_can( "administrator" ) ) {
			die;
		}
		global $wpdb;
		$wpdb->query(
			"INSERT IGNORE INTO " . $wpdb->base_prefix . "geonamesPostal
		(country_code,
		postal_code,
		place_name,
		admin1_name,
		admin1_code,
		admin2_name,
		admin2_code,
		admin3_name,
		admin3_code,
		latitude,
		longitude,
		accuracy) 
		VALUES" . substr( $g, 0, strlen( $g ) - 1 )
		);
	}

	public function postalAddZip(
		$url,
		$f
	) {
		$this->verifyAdmin();

		if ( $this->verifyToka() ) {
			return false;
		}

		$PwpGeonamesPostalAdd = ( ! empty( $f['wpGeonamesPostalAdd'] )
			? strip_tags( stripslashes( filter_var( $f['wpGeonamesPostalAdd'], FILTER_SANITIZE_URL ) ) )
			: '' );

		set_time_limit( 300 ); // default is 30

		$upl = wp_upload_dir();
		$upl = $upl['basedir'] . '/wp-geonames/zip/';

		if ( ! is_dir( $upl ) ) {
			mkdir( $upl, true );
		}

		// 1. Get ZIP from URL - Copy to uploads/wp-geonames/zip/ folder
		if ( ! copy( $url . $PwpGeonamesPostalAdd, $upl . $PwpGeonamesPostalAdd ) ) {
			_e( 'Failure in the download of the zip.', 'wpGeonames' );
			die();
		}

		// 2. Extract ZIP in uploads/zip/
		$zip = new ZipArchive();
		if ( $zip->open( $upl . $PwpGeonamesPostalAdd ) === true ) {
			$zip->extractTo( $upl );
			$zip->close();
			@unlink( $upl . $PwpGeonamesPostalAdd );
		} else {
			_e( 'Failure in the extraction of the zip.', 'wpGeonames' );
			die();
		}

		// 3. Read file and put data in array
		$handle = @fopen( $upl . substr( $PwpGeonamesPostalAdd, 0, strlen( $PwpGeonamesPostalAdd ) - 4 ) . '.txt', 'r' );
		//
		$g = '';
		$c = 0;
		if ( $handle ) {
			while ( ( $v = fgets( $handle, 8192 ) ) !== false ) {
				$v = str_replace( '"', ' ', $v );
				$e = explode( "\t", $v );
				if ( ! empty( $e[0] ) && isset( $e[11] ) ) {
					++ $c;
					$g .= '( "' . $e[0] .
					      '","' . $e[1] .
					      '","' . $e[2] .
					      '","' . ( isset( $f['wpGeoPostal3'] )
							? $e[3]
							: '' ) .
					      '","' . ( isset( $f['wpGeoPostal4'] )
							? $e[4]
							: '' ) .
					      '","' . ( isset( $f['wpGeoPostal5'] )
							? $e[5]
							: '' ) .
					      '","' . ( isset( $f['wpGeoPostal6'] )
							? $e[6]
							: '' ) .
					      '","' . ( isset( $f['wpGeoPostal7'] )
							? $e[7]
							: '' ) .
					      '","' . ( isset( $f['wpGeoPostal8'] )
							? $e[8]
							: '' ) .
					      '","' . ( isset( $f['wpGeoPostal9'] )
							? $e[9]
							: '' ) .
					      '","' . ( isset( $f['wpGeoPostal10'] )
							? $e[10]
							: '' ) .
					      '","' . ( isset( $f['wpGeoPostal11'] )
							? $e[11]
							: '' ) .
					      '"),';
				}
				if ( $c > 5000 ) {
					$this->postalAddDb( $g );
					$c = 0;
					$g = '';
				}
			}
			$this->postalAddDb( $g );
			fclose( $handle );
		}
		@unlink( $upl . substr( $PwpGeonamesPostalAdd, 0, strlen( $PwpGeonamesPostalAdd ) - 4 ) . ' . txt' );
		$this->update_options();

		return __( 'Done, data are in base . ', 'wpGeonames' );
	}

	public function regionCode2( $iso = 'ZZZ' ) {
		$a = ',BE,';

		return strpos( $a, $iso ) !== false;
	}

	public function settings_link( $links ) {
		$links[] = '<a href = "options-general.php?page=wpGeonames-options">' . __( 'Settings', 'wpGeonames' ) . ' </a> ';

		return $links;
	}

	public function shortcode( $a ) {
		$shortcode = shortcode_atts(
			[
				'id1'    => 'geoCountry',
				'id2'    => 'geoRegion',
				'id3'    => 'geoCity',
				'out'    => 'geoRow',
				'map'    => '0',
				'zoom'   => '9',
				'nbcity' => '10',
				'data'   => '',
			],
			$a
		);
		if ( $shortcode['map'] ) {
			$this->enqueue_leaflet();
		}
		$out                      = '';
		$geoData                  = [];
		$geoData['selectCountry'] = '';
		$country                  = $this->get_country();
		foreach ( $country as $r ) {
			$geoData['selectCountry'] .= '<option value = "' . $r->country_code . '">' . $r->name . '</option>';
		}
		$geoData['onChangeCountry'] = 'onchange = "geoDataRegion();"';
		$geoData['onChangeRegion']  = 'onchange = "geoDataCity();"';
		$geoData['onKeyCity']       = 'onClick = "wpGeonameCityMap(v.name,v.latitude,v.longitude);"';

		// ****** TEMPLATE ********
		if ( has_filter( 'wpGeonames_location_taxonomy_tpl' ) ) {
			$inc = apply_filters( 'wpGeonames_location_taxonomy_tpl', 0 );
		} elseif ( file_exists( get_stylesheet_directory() . '/templates/wp-geonames_location_taxonomy.php' ) ) {
			$inc = get_stylesheet_directory() . '/templates/wp-geonames_location_taxonomy.php';
		} else {
			$inc = $this->getPluginDir() . '/templates/wp-geonames_location_taxonomy.php';
		}
		ob_start();
		/** @noinspection PhpIncludeInspection */
		include( $inc );
		$out .= ob_get_clean();

		// ************************
		return $out;
	}

	public function sortCountry(
		$a,
		$b
	) {
		return strcmp( $a->name, $b->name );
	}

	public function sortCountry2(
		$a,
		$b
	) {
		if ( $a->country_code == $b->country_code ) {
			return strcmp( $a->name, $b->name );
		}

		return strcmp( $a->country_code, $b->country_code );
	}

	public function update_options( $force = false ) {

		static $isUpdating = false;

		if ( $isUpdating ) {
			return null;
		}

		$this->verifyAdmin();

		$wpdb = self::$wpdb;

		if ( $force ) {

			$options = [];

			foreach (
				[
					'countries' => self::urlLocations,
					'postal'    => self::urlPostal,
				] as $name => $url
			) {

				if ( function_exists( 'curl_version' ) ) {
					$h = curl_init( $url );
					curl_setopt( $h, CURLOPT_RETURNTRANSFER, true );
					curl_setopt( $h, CURLOPT_CONNECTTIMEOUT, 5 );
					$page = curl_exec( $h );
					curl_close( $h );
				} else {
					$page = @file_get_contents( $url );
				}

				$options['filenames'][ $name ] = [];

				if ( $page && preg_match_all(
						"@\shref=\"(?<url>[-.\w]+.zip)\">[^<]+</a>\s+(?<date>[-\d]*?)\s(?:[\d:]+)\s+(?<size>[\w.]+)\s+$@im",
						$page,
						$matches,
						PREG_SET_ORDER
					) ) {
					array_walk(
						$matches,
						function ( $match ) use (
							$name,
							&
							$options
						) {
							unset( $match[0], $match[1], $match[2] );
							$options['filenames'][ $name ][ $match['url'] ] = $match;
						}
					);
				}

				unset( $matches, $name, $url );
			}

			$options['filenames']['lastUpdate'] = time();
		} else // only keep the 'filenames', if exist
		{
			$options = get_option( 'wpGeonames_dataList' );
		}

		$options['countries'] = [];
		$count                = self::$wpdb->get_results(
			"SELECT COUNT(*) c, country_code FROM {$this->tblLocations} GROUP BY country_code"
		);  // benchmark allCountries : 7.633 sec
		foreach ( $count as $r ) {
			if ( $r->country_code ) {
				$options['countries'][ $r->country_code ] = $r->c;
			}
		}

		$options['postal'] = [];
		$postal            = self::$wpdb->get_results(
			"SELECT COUNT(*) c, country_code FROM {$wpdb->base_prefix}geonamesPostal GROUP BY country_code"
		);
		foreach ( $postal as $r ) {
			if ( $r->country_code ) {
				$options['postal'][ $r->country_code ] = $r->c;
			}
		}

		$old = self::$wpdb->get_var(
			"SELECT MAX(modification_date) FROM {$this->tblLocations} LIMIT 1"
		); // benchmark allCountries : 5.172 sec
		if ( $old ) {
			$options['date'] = $old;
		}

		$isUpdating = true; // avoid infinite loop by get_option()-call in update_option()
		update_option( 'wpGeonames_dataList', $options, false );
		$isUpdating = false;

		return $options;
	}

	public function verifyAdmin() {
		if ( ! current_user_can( "administrator" ) ) {
			die;
		}
	}

	public function verifyToka() {
		if ( $this->inActivation ) {
			return false;
		}

		return (
			empty( $_REQUEST['geoToka'] )
			|| ! wp_verify_nonce( $_REQUEST['geoToka'], 'geoToka' )
		);
	}

	public static function Factory( $file ) {
		return self::$instance
			?: self::$instance = new self( $file );
	}

	protected static function createWhereFilterIn(
		$field,
		$values,
		$relation = 'AND',
		$delim = '"'
	) {

		$base = "\t$relation $field %s\n";

		if ( $values === null ) {
			return sprintf( $base, "IS NULL" );
		}

		return empty( $values )
			? ''
			: sprintf(
				$base,
				"IN ($delim"
				. join( "$delim, $delim", $values )
				. "$delim)"
			);
	}

	public static function getCachedQuery(
		$query,
		$offset = 0,
		$limit = - 1,
		$output = 'wpGeonamesLocation',
		$key = 'geonameId',
		$prefix = '_'
	) {

		/** @var Core $self */
		$self = self::$instance;

		if ( is_array( $query ) ) {

			$inCountryCode = $query['searchCountry']
				? " AND r.country_code IN ('" . join( "','", $query['searchCountry'] ) . "')"
				: '';
			$inQueryId     = " AND r.query_id IN (" . join(
					",",
					array_map(
						static function ( $query ) {
							return is_object( $query )
								? $query->query_id
								: $query;
						},
						$query['queries']
					)
				) . ")";
		} else {
			$inCountryCode = '';
			$inQueryId     = ' AND r.query_id = ' . is_object( $query )
				? $query->query_id
				: $query;
		}

		$sqlLimit = '';

		if ( $offset >= 0 && $limit < 0 ) {
			$limit = '18446744073709551615';
		}

		if ( $limit >= 0 ) {
			$sqlLimit = "LIMIT $limit\n";

			if ( $offset > 0 ) {
				$sqlLimit .= "OFFSET $offset\n";
			}
		}

		$sql = <<<SQL
SELECT
    *
FROM
        {$self->tblCacheResults} r
    LEFT JOIN
        {$self->tblCacheLocations} l on l.geoname_id = r.geoname_id
WHERE
    1
    $inQueryId
    $inCountryCode
ORDER BY
      r.order
    , r.query_id
    , l.geoname_id
$sqlLimit
;
SQL;

		$result = self::$wpdb->get_results( $sql );

		return WpDb::formatOutput( $result, $output, $key, $prefix );
	}

	/**
	 * @return array
	 */
	public static function &getCountryCodes() {
		if ( self::$countryCodes === null ) {
			self::loadArray( 'countryCodes' );
		}

		return self::$countryCodes;
	}

	/**
	 * @param array $countryCodes
	 */
	public static function setCountryCodes( &$countryCodes ) {
		self::$countryCodes = self::saveArray( 'countryCodes', $countryCodes );
	}

	/**
	 * @return object
	 */
	public static function getEnums() {

		$self = self::Factory();

		return self::$enums
			?: self::$enums = (object) [
				'featureClasses' => new HashTable\Definition(
					'featureClasses',
					__DIR__ . '/includes/feature_classes.php',
					[
						new HashTable\Field( $self->tblLocations, 'feature_class' ),
					]
				),
				'featureCodes'   => new HashTable\Definition(
					'featureCodes',
					__DIR__ . '/includes/feature_codes.php',
					[
						new HashTable\Field( $self->tblLocations, 'feature_code' ),
					]
				),
				'countryCodes'   => new HashTable\Definition(
					'countryCodes',
					__DIR__ . '/includes/country_codes.php',
					[
						new HashTable\Field( $self->tblLocations, 'country_code' ),
						new HashTable\Field( $self->tblTimeZones, 'country_code' ),
						new HashTable\Field( $self->tblCacheQueries, 'query_country' ),
					]
				),
				'timeZones'      => new HashTable\Definition(
					'timeZones',
					__DIR__ . '/includes/time_zones.php',
					[
						new HashTable\Field( $self->tblLocations, 'timezone' ),
						new HashTable\Field( $self->tblCacheQueries, 'timezone' ),
					]
				),
			];
	}

	/**
	 * @return array
	 */
	public static function &getFeatureClasses() {
		if ( self::$featureClasses === null ) {
			self::loadArray( 'featureClasses' );
		}

		return self::$featureClasses;
	}

	/**
	 * @param array $featureClasses
	 */
	public static function setFeatureClasses( &$featureClasses ) {
		self::$featureClasses = self::saveArray( 'featureClasses', $featureClasses );
	}

	/**
	 * @return array
	 */
	public static function &getFeatureCodes() {
		if ( self::$featureCodes === null ) {
			self::loadArray( 'featureCodes' );
		}

		return self::$featureCodes;
	}

	/**
	 * @param array $featureCodes
	 */
	public static function setFeatureCodes( &$featureCodes ) {
		self::$featureCodes = self::saveArray( 'featureCodes', $featureCodes );
	}

	/**
	 * @return mixed
	 */
	public static function getGeoNameClient() {
		return self::$geoNameClient
			?: self::$geoNameClient = new GeoNamesClient(
				get_option( 'wpGeonames_username' )
					?: 'thebrightpath'
			);
	}

	/**
	 * @param        $query
	 * @param string $output Optional. Any of ARRAY_A | ARRAY_N | OBJECT | OBJECT_K constants.
	 *                       With one of the first three, return an array of rows indexed from 0 by SQL result row
	 *                       number. Each row is an associative array (column => value, ...), a numerically indexed
	 *                       array (0 => value, ...), or an object. ( ->column = value ), respectively. With OBJECT_K,
	 *                       return an associative array of row objects keyed by the value of each row's first column's
	 *                       value. Duplicate keys are discarded.
	 *
	 * @return array|object|null Database query results
	 *
	 * @return array
	 *
	 * @see https://www.geonames.org/export/geonames-search.html
	 */
	public static function &getLiveSearch(
		$query,
		$output = OBJECT
	) {

		//self::$instance->creation_table();

		if ( ! $query instanceof ApiQuery ) {

			$query = new ApiQuery(
				$query, [
					'maxRows'        => 20,
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
			);
		}

		$apiResult = $query->query();

		return WpDb::formatOutput( $apiResult, $output );
	}

	/**
	 * @param array $args
	 * @param string $output Optional. Any of ARRAY_A | ARRAY_N | OBJECT | OBJECT_K constants.
	 *                       With one of the first three, return an array of rows indexed from 0 by SQL result row
	 *                       number. Each row is an associative array (column => value, ...), a numerically indexed
	 *                       array (0 => value, ...), or an object. ( ->column = value ), respectively. With OBJECT_K,
	 *                       return an associative array of row objects keyed by the value of each row's first column's
	 *                       value. Duplicate keys are discarded.
	 *
	 * @return array|object|null Database query results
	 */
	public static function getLocations(
		$args = [],
		$output = OBJECT
	) {

		$self   = self::$instance;
		$where  = '';
		$limits = '';

		$args = wp_parse_args(
			$args,
			[
				'page_size'     => 20,
				'feature_class' => [],
				'feature_code'  => [],
				'country_code'  => [],
				'admin1_code'   => [],
				'admin2_code'   => [],
				'admin3_code'   => [],
				'admin4_code'   => [],
				'population'    => [],
				'timezone'      => [],
			]
		);

		if ( $args['location__in'] ) {

			$where .= self::createWhereFilterIn( 'geoname_id', $args['location__in'], 'AND', '' );
		} else {
			foreach (
				[
					'feature_class',
					'feature_code',
					'country_code',
					'admin1_code',
					'admin2_code',
					'admin3_code',
					'admin4_code',
					'timezone',
				] as $field
			) {
				$where .= self::createWhereFilterIn( $field, $args[ $field ] );
			}
		}

		// Paging.
		if ( empty( $args['no_paging'] ) ) {
			$page = absint(
				$args['paged']
					?: 1
			);
			if ( ! $page ) {
				$page = 1;
			}

			// If 'offset' is provided, it takes precedence over 'paged'.
			if ( isset( $args['offset'] ) && is_numeric( $args['offset'] ) ) {
				$args['offset'] = absint( $args['offset'] );
				$limitStart     = $args['offset'] . ', ';
			} else {
				$limitStart = absint( ( $page - 1 ) * $args['page_size'] ) . ', ';
			}
			$limits = 'LIMIT ' . $limitStart . $args['page_size'];
		}

		$sql = <<<SQL
SELECT
       *
FROM
    {$self->tblCacheLocations}
WHERE
    1
$where
$limits
;
SQL;

		//echo '<pre>'; print_r( $sql ); echo '</pre>';

		return self::$wpdb->get_results( $sql, $output );
	}

	/**
	 * @return array
	 */
	public static function &getTimeZones() {
		if ( self::$timeZones === null ) {
			self::loadArray( 'timeZones' );
		}

		return self::$timeZones;
	}

	/**
	 * @param array $timeZones
	 */
	public static function setTimeZones( &$timeZones ) {
		self::$timeZones = self::saveArray( 'timeZones', $timeZones );
	}

	public static function &loadArray( $name ) {
		/** @noinspection PhpIncludeInspection */
		self::$$name = require( self::getEnums()->$name->file );

		return self::$$name;
	}

	/**
	 * @param string $name
	 * @param array $array
	 *
	 * @return array
	 */
	public static function &saveArray(
		$name,
		&$array
	) {

		if ( empty( $array ) ) {
			return $array;
		}

		if ( ! is_array( $array ) ) {
			return $array;
		}

		ksort( $array );

		$dump = var_export( $array, true );

		file_put_contents(
			self::getEnums()->$name->file,
			<<<EOF
<?php
/** @noinspection SpellCheckingInspection */

return $dump;

EOF
		);

		$keys = "'" . join( "', '", array_keys( $array ) ) . "'";

		foreach ( self::getEnums()->$name->fields as $field ) {
			$sql = <<<SQL
ALTER TABLE `{$field->table}`
CHANGE `{$field->field}` `{$field->field}`
enum($keys)
DEFAULT NULL;

SQL;

			self::$wpdb->query( $sql ) || die( $sql );
		}

		self::$$name = &$array;

		return $array;
	}
}
