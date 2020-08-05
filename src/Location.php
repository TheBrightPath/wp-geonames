<?php

namespace WPGeonames;

/**
 * Class Location
 *
 * @property int $geonameId
 * @property string $name
 * @property string $featureClass
 * @property string $featureCode
 * @property string $countryCode
 * @property string $adminCode1
 * @property string $adminCode2
 * @property string $adminCode3
 * @property string $adminCode4
 * @property float $latitude
 * @property float $longitude
 * @property string $alternateNames
 * @property int $countryId
 * @property int $population
 */
class Location
	extends FlexibleObject {

// protected properties
	protected static $aliases = [
		'geoname_id'      => 'geonameId',
		'alternate_names' => 'alternateNames',
		'feature_class'   => 'featureClass',
		'fcl'             => 'featureClass',
		'feature_code'    => 'featureCode',
		'fcode'           => 'featureCode',
		'country_code'    => 'countryCode',
		'admin1_code'     => 'adminCode1',
		'admin2_code'     => 'adminCode2',
		'admin3_code'     => 'adminCode3',
		'admin4_code'     => 'adminCode4',
		'lng'             => 'longitude',
		'lat'             => 'latitude',
	];
	protected $geonameId = null;
	protected $name = null;
	protected $featureClass = null;
	protected $featureCode = null;
	protected $countryCode = null;
	protected $adminCode1 = null;
	protected $adminCode2 = null;
	protected $adminCode3 = null;
	protected $adminCode4 = null;
	protected $longitude = null;
	protected $latitude = null;
	protected $alternateNames = null;
	protected $countryId = null;
	protected $population = null;


	/**
	 * @param int|string $x
	 * @param string $format
	 *
	 * @return string|array|null
	 */
	protected function getAdminCode( $x, $format ) {

		if ( is_numeric( $x ) ) {
			$x = "adminCode$x";
		}

		if ( $this->$x === null ) {
			return null;
		}

		return $format
			? ( $this->$x )[ $format ]
			: $this->$x;
	}


	/**
	 * @param string $format
	 *
	 * @return string|array|null
	 */
	public function getAdminCode1( $format = 'ISO3166_2' ) {

		return $this->getAdminCode( 1, $format );
	}


	/**
	 * @param string|array $adminCode
	 *
	 * @return Location
	 */
	public function setAdminCode1( $adminCode ) {

		return $this->setAdminCode( 1, $adminCode );
	}


	/**
	 * @param string $format
	 *
	 * @return string|array|null
	 */
	public function getAdminCode2( $format = 'ISO3166_2' ) {

		return $this->getAdminCode( 2, $format );
	}


	/**
	 * @param string|array $adminCode
	 *
	 * @return Location
	 */
	public function setAdminCode2( $adminCode ) {

		return $this->setAdminCode( 2, $adminCode );
	}


	/**
	 * @param string $format
	 *
	 * @return string|array|null
	 */
	public function getAdminCode3( $format = 'ISO3166_2' ) {

		return $this->getAdminCode( 3, $format );
	}


	/**
	 * @param string|array $adminCode
	 *
	 * @return Location
	 */
	public function setAdminCode3( $adminCode ) {

		return $this->setAdminCode( 3, $adminCode );
	}


	/**
	 * @param string $format
	 *
	 * @return string|array|null
	 */
	public function getAdminCode4( $format = 'ISO3166_2' ) {

		return $this->getAdminCode( 4, $format );
	}


	/**
	 * @param string|array $adminCode
	 *
	 * @return Location
	 */
	public function setAdminCode4( $adminCode ) {

		return $this->setAdminCode( 4, $adminCode );
	}


	/**
	 * @return string
	 */
	public function getAlternateNames(): string {

		return $this->alternateNames;
	}


	/**
	 * @param null $alternateNames
	 *
	 * @return Location
	 */
	public function setAlternateNames( $alternateNames ) {

		$this->alternateNames = $alternateNames;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getCountryCode(): string {

		return $this->countryCode;
	}


	/**
	 * @param null $countryCode
	 *
	 * @return Location
	 */
	public function setCountryCode( $countryCode ) {

		$this->countryCode = $countryCode;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getCountryId(): int {

		return $this->countryId;
	}


	/**
	 * @param null $countryId
	 *
	 * @return Location
	 */
	public function setCountryId( $countryId ) {

		$this->countryId = $countryId;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getFeatureClass(): string {

		return $this->featureClass;
	}


	/**
	 * @param null $featureClass
	 *
	 * @return Location
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
	 * @param null $featureCode
	 *
	 * @return Location
	 */
	public function setFeatureCode( $featureCode ) {

		$this->featureCode = $featureCode;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getGeonameId(): int {

		return $this->geonameId;
	}


	/**
	 * @param null $geonameId
	 *
	 * @return Location
	 */
	public function setGeonameId( $geonameId ) {

		$this->geonameId = $geonameId;

		return $this;
	}


	/**
	 * @return float
	 */
	public function getLatitude(): float {

		return $this->latitude;
	}


	/**
	 * @param null $latitude
	 *
	 * @return Location
	 */
	public function setLatitude( $latitude ) {

		$this->latitude = $latitude;

		return $this;
	}


	/**
	 * @return float
	 */
	public function getLongitude(): float {

		return $this->longitude;
	}


	/**
	 * @param null $longitude
	 *
	 * @return Location
	 */
	public function setLongitude( $longitude ) {

		$this->longitude = $longitude;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getName(): string {

		return $this->name;
	}


	/**
	 * @param null $name
	 *
	 * @return Location
	 */
	public function setName( $name ) {

		$this->name = $name;

		return $this;
	}


	/**
	 * @return int
	 */
	public function getPopulation(): int {

		return $this->population;
	}


	/**
	 * @param null $population
	 *
	 * @return Location
	 */
	public function setPopulation( $population ) {

		$this->population = $population;

		return $this;
	}


	/**
	 * @param $x
	 * @param $adminCode
	 *
	 * @return Location
	 */
	protected function setAdminCode( $x, $adminCode ) {

		if ( is_numeric( $x ) ) {
			$x = "adminCode$x";
		}

		if ( ! is_array( $adminCode ) ) {
			$adminCode = [
				'ISO3166_2' => $adminCode,
			];
		}

		$this->$x = $adminCode;

		return $this;
	}


	static public function parseArray( &$array, $key = 'geoname_id', $prefix = '_' ) {

		return parent::parseArray( $array, $key, $prefix );

	}

}