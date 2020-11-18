<?php

namespace WPGeonames\Helpers;

use ErrorException;
use WPGeonames\Core;

trait DbObjectTrait
{

// protected properties

    /** @var string */
    protected static $_tblName;

    /** @var string */
    protected static $_returnFormat = OBJECT;

    protected static $_sqlLoadRecords
        = /** @lang text */
        <<<'SQL'
SELECT
    *
FROM
    %s
WHERE
    %s
SQL;

    protected $_isDirty = false;
    protected $_isLoading = false;


    /**
     * @param        $ids
     * @param  null  $output
     *
     * @return array|mixed|null
     * @throws \ErrorException
     */
    public static function load(
        $ids = null,
        $output = null
    ) {

        $records = static::loadRecords( $ids );

        if ( $records === null )
        {
            return null;
        }

        Core::$wpdb::formatOutput( $records, $output ?? static::$_returnFormat );

        return ( $ids === null || is_array( $ids ) )
            ? $records
            : reset( $records )
                ?: null;
    }


    /**
     * @param        $sqlWhere
     * @param  null  $tableName
     *
     * @return array|null
     * @throws \ErrorException
     */
    protected static function loadRecords(
        $sqlWhere,
        $tableName = null
    ): ?array {

        $sql = sprintf(
            static::$_sqlLoadRecords,
            $tableName ?? static::$_tblName,
            is_int( $sqlWhere )
                ? 'query_id = ' . $sqlWhere
                : $sqlWhere
        );

        if ( Core::$wpdb->query( $sql ) === false )
        {
            throw new ErrorException( Core::$wpdb->last_error );
        }

        return Core::$wpdb->last_result;

    }

}
