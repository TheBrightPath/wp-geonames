<?php

namespace WPGeonames\Helpers;

use ErrorException;
use stdClass;
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

    protected $_isDirty   = false;
    protected $_isLoading = false;


    /**
     * @param  null         $ids
     * @param  object|null  $options
     *
     * @return array|\WPGeonames\Helpers\FlexibleDbObjectInterface|null
     * @throws \ErrorException
     */
    public static function load(
        $ids = null,
        object $options = null
    ) {

        $options = $options ?? new stdClass();
        $records = static::loadRecords( $ids, $options );

        if ( $records === null )
        {
            return null;
        }

        Core::$wpdb::formatOutput( $records, $options->output ?? static::$_returnFormat );

        return ( $ids === null || is_array( $ids ) )
            ? $records
            : ( reset( $records )
                ?: null );
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
        $options = null
    ): ?array {

        $options = $options ?? new stdClass();

        $sql = sprintf(
            static::$_sqlLoadRecords,
            $options->tableName ?? static::$_tblName,
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
