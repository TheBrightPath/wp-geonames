<?php

namespace WPGeonames\Query;

trait QueryableTrait
{

    /** @var \WPGeonames\Query\Status */
    protected $_status;


    /**
     * @return \WPGeonames\Query\Status|null
     */
    public function getStatus(): Status
    {

        return $this->_status;
    }

}