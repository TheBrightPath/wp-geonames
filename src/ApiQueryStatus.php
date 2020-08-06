<?php

namespace WPGeonames;

class ApiQueryStatus {

//  public properties
	/** @var int Current query type */
	public $type = 0;

	/** @var ApiQuery */
	public $query;

	/** @var array Current query params */
	public $params = [];

	/** @var array Current result */
	public $result = [];

	/** @var array Total result */
	public $results = [];

	/** @var int|null Total of found records in current request */
	public $total;

	public $processRecords = 0;


	/**
	 * wpGeonamesQueryStatus constructor.
	 *
	 * @param int $type
	 * @param ApiQuery $query
	 * @param array $params
	 * @param array $results
	 * @param int|null $processRecords
	 */
	public function __construct(
		int $type,
		ApiQuery $query,
		array $params,
		array &$results,
		?int $processRecords
	) {

		$this->type           = $type;
		$this->query          = $query;
		$this->params         = $params;
		$this->results        = &$results;
		$this->processRecords = $processRecords ?? $this->processRecords;
	}

}