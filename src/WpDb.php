<?php

namespace WPGeonames;

use ErrorException;
use mysqli;
use mysqli_result;

if ( ! defined( 'ARRAY_K' ) )
{
    define( 'ARRAY_K', 'ARRAY_K' );
}


class WpDb
    extends
    \wpdb
{

// constants

    public const DEFAULT_TABLE_PREFIX = 'wp_';

//  public properties

    public $last_error_no = 0;

// protected properties

    protected $use_mysqli    = false;
    protected $has_connected = false;
    protected $time_start;


    /**
     * WpDb constructor.
     *
     * @param  \wpdb|null  $db
     *
     * @noinspection PhpMissingParentConstructorInspection
     * @noinspection MagicMethodsValidityInspection
     */
    public function __construct( \wpdb $db = null )
    {

        global $wpdb;

        $db = $db ?? $wpdb;

        $db->flush();

        $x = get_object_vars( $db );

        foreach ( $x as $property => &$value )
        {

            if ( property_exists( $this, $property ) )
            {
                $this->$property =& $value;
            }
            else
            {
                $this->$property = $value;
            }

        }

        unset ( $value );

        foreach (
            [
                'use_mysqli',
                'has_connected',
            ] as $property
        )
        {

            $this->$property = $wpdb->__get( $property );
            $this->__set( $property, $this->$property );

        }

        if ( $wpdb instanceof \wpdb )
        {
            $wpdb = $this;
        }

    }


    /**
     * Internal function to perform the mysql_query() call.
     *
     *
     * @param  string  $query  The query to run.
     * @param  bool    $is_multi_query
     */
    protected function _do_query(
        string $query,
        bool $is_multi_query = false
    ): void {

        if ( ! $this->dbh instanceof mysqli )
        {
            return;
        }

        if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES )
        {
            $this->timer_start();
        }

        if ( $is_multi_query )
        {
            $this->result = false;

            if ( $this->dbh->multi_query( $query ) )
            {
                /* store first result set */
                $this->result = $this->dbh->store_result();
            }
        }
        else
        {
            $this->result = $this->dbh->query( $query );
        }

        $this->num_queries ++;

        if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES )
        {
            $this->log_query(
                $query,
                $this->timer_stop(),
                $this->get_caller(),
                $this->time_start,
                []
            );
        }

    }


    public function deleteAndReplaceTablePrefix(
        $table,
        $where,
        $where_format = null
    ) {

        return $this->delete(
            static::replaceTablePrefix( $table ),
            $where,
            $where_format
        );
    }


    public function flush(): void
    {

        $this->last_error_no = 0;

        // Clear out any results from a multi-query.
        if ( $this->dbh instanceof mysqli )
        {
            while ( $this->dbh->more_results() )
            {
                $this->dbh->next_result();
            }
        }

        parent::flush();
    }


    public function insertAndReplaceTablePrefix(
        $table,
        $data,
        $format = null
    ) {

        return $this->insert( static::replaceTablePrefix( $table ), $data, $format );
    }


    /**
     * @param  string  $query
     * @param  mixed   ...$args
     *
     * @return string|null
     * @noinspection PhpMissingParamTypeInspection
     * @noinspection PhpUnusedParameterInspection
     */
    public function prepare(
        $query,
        ...$args
    ): ?string {

        if ( is_null( $query ) )
        {
            return null;
        }

        $checkScalar = static function (
            &$arg,
            $index,
            $checkScalar = null
        ) {

            if ( is_array( $arg ) && $checkScalar !== null )
            {
                array_walk( $arg, $checkScalar );
            }
            elseif ( ! is_scalar( $arg ) && ! is_null( $arg ) )
            {

                $arg = (string) $arg;
            }
        };

        array_walk(
            $args,
            $checkScalar,
            $checkScalar
        );

        return parent::prepare( $query, ...$args )
            ?: null;
    }


    public function prepareAndReplaceTablePrefix(
        $query,
        ...$args
    ): ?string {

        return $this->prepare( static::replaceTablePrefix( $query ), ...$args );

    }


    /**
     * @inheritDoc
     *
     * @param  string|array  $query
     *
     * @return bool|int
     */
    public function query( $query )
    {

        if ( ! $this->ready )
        {
            $this->check_current_query = true;

            return false;
        }

        if ( $is_multi_query = is_array( $query ) )
        {
            $query = implode( ";\n", $query );
        }

        /**
         * Filters the database query.
         *
         * Some queries are made before the plugins have been loaded,
         * and thus cannot be filtered with this method.
         *
         * @since 2.1.0
         *
         * @param  string  $query  Database query.
         */
        $query = apply_filters( 'query', $query );

        $this->flush();

        // Log how the function was called.
        $this->func_call = "\$db->query(\"$query\")";

        // If we're writing to the database, make sure the query will write safely.
        if ( $this->check_current_query && ! $this->check_ascii( $query ) )
        {
            $stripped_query = $this->strip_invalid_text_from_query( $query );
            // strip_invalid_text_from_query() can perform queries, so we need
            // to flush again, just to make sure everything is clear.
            $this->flush();
            if ( $stripped_query !== $query )
            {
                $this->insert_id = 0;

                return false;
            }
        }

        $this->check_current_query = true;

        // Keep track of the last query for debug.
        $this->last_query = $query;

        $this->_do_query( $query, $is_multi_query );

        // MySQL server has gone away, try to reconnect.
        if ( $this->dbh instanceof mysqli )
        {
            $mysql_errno = $this->dbh->errno;
        }
        else
        {
            // $dbh is defined, but isn't a real connection.
            // Something has gone horribly wrong, let's try a reconnect.
            $mysql_errno = 2006;
        }

        if ( 2006 === $mysql_errno )
        {
            if ( $this->check_connection() )
            {
                $this->_do_query( $query, $is_multi_query );
            }
            else
            {
                $this->insert_id = 0;

                return false;
            }
        }

        // If there is an error then take note of it.
        if ( $this->dbh instanceof mysqli )
        {
            $this->last_error    = $this->dbh->error;
            $this->last_error_no = $this->dbh->errno;
        }
        else
        {
            $this->last_error    = __( 'Unable to retrieve the error message from MySQL' );
            $this->last_error_no = - 1;
        }

        if ( $this->last_error )
        {
            // Clear insert_id on a subsequent failed insert.
            if ( $this->insert_id && preg_match( '/^\s*(insert|replace)\s/i', $query ) )
            {
                $this->insert_id = 0;
            }

            $this->print_error();

            return false;
        }

        if ( preg_match( '/^\s*(create|alter|truncate|drop)\s/i', $query ) )
        {
            $return_val = $this->result;
        }
        elseif ( preg_match( '/^\s*(insert|delete|update|replace)\s/i', $query ) )
        {
            $this->rows_affected = $this->dbh->affected_rows;

            // Take note of the insert_id.
            if ( preg_match( '/^\s*(insert|replace)\s/i', $query ) )
            {
                $this->insert_id = $this->dbh->insert_id;
            }

            // Return number of rows affected.
            $return_val = $this->rows_affected;
        }
        else
        {
            $num_rows = 0;

            if ( $this->result instanceof mysqli_result )
            {
                while ( $row = $this->result->fetch_object() )
                {
                    $this->last_result[ $num_rows ] = $row;
                    $num_rows ++;
                }
            }

            // Log and return the number of rows selected.
            $this->num_rows = $num_rows;
            $return_val     = $num_rows;
        }

        if ( $is_multi_query )
        {
            if ( $this->dbh instanceof mysqli )
            {

                // flush multi_queries
                // Clear out any results from a multi-query.
                while ( $this->dbh->more_results() )
                {
                    $this->dbh->next_result();
                }
            }
            else
            {
                // flush multi_queries
                while ( mysqli_more_results( $this->dbh ) )
                {
                    mysqli_next_result( $this->dbh );
                }
            }
        }

        return $return_val;

    }


    public function queryAndReplaceTablePrefix( $query )
    {

        return $this->query( static::replaceTablePrefix( $query ) );
    }


    public function replaceAndReplaceTablePrefix(
        $table,
        $data,
        $format = null
    ) {

        return $this->replace( static::replaceTablePrefix( $table ), $data, $format );
    }


    public function updateAndReplaceTablePrefix(
        $table,
        $data,
        $where,
        $format = null,
        $where_format = null
    ) {

        return $this->update( static::replaceTablePrefix( $table ), $data, $where, $format, $where_format );
    }


    public static function ensureClass(
        &$item,
        $targetClass,
        &$additionalInterfaces
    ): bool {

        $targetClass = $item->__CLASS__ ?? $targetClass;

        if ( ! $item instanceof $targetClass
            && ! array_reduce(
                $additionalInterfaces,
                static function (
                    $isA,
                    $class
                ) use
                (
                    &
                    $item
                )
                {

                    return $isA || $item instanceof $class;
                },
                false
            ) )
        {
            $item = new $targetClass( $item );

            return true;
        }

        return false;
    }


    /**
     * @param          $result
     * @param          $output
     * @param  string  $keyName
     * @param  string  $prefix
     * @param  array   $additionalInterfaces
     *
     * @return array|null
     * @throws \ErrorException
     */
    public static function &formatOutput(
        &$result,
        $output,
        $keyName = '',
        $prefix = '',
        array $additionalInterfaces = []
    ): ?array {

        if ( $result === null )
        {
            return $result;
        }

        $class = null;

        if (
            ! in_array(
                $output,
                [
                    OBJECT,
                    OBJECT_K,
                    ARRAY_K,
                    ARRAY_A,
                    ARRAY_N,
                ],
                true
            )
            && class_exists( $output )
        )
        {

            $class  = $output;
            $output = ! empty( $keyName )
                ? OBJECT_K
                : OBJECT;

        }

        switch ( strtoupper( $output ) )
        {
            // Back compat for OBJECT being previously case-insensitive.

        case OBJECT:
            // Return an integer-keyed array of row objects.
            if ( ! empty( $result ) )
            {
                if ( ! $keyName === null )
                {
                    $result = array_values( $result );
                }

                array_walk(
                    $result,
                    static function ( &$entry )
                    {

                        $entry = (object) $entry;
                    }
                );
            }
            break;

        case OBJECT_K:
        case ARRAY_K:
        case ARRAY_A:
        case ARRAY_N:
            $new_array = [];

            // Return an array of row objects with keys from column 1.
            // (Duplicates are discarded.)
            if ( $result )
            {

                array_walk(
                    $result,
                    static function (
                        $row,
                        $key
                    )
                    use
                    (
                        &
                        $new_array,
                        $output,
                        &
                        $keyName,
                        $prefix
                    )
                    {

                        $return = $object_vars = null;

                        // if output is desired as ARRAY, but is currently and object, convert to an array
                        if ( $output !== OBJECT_K && is_object( $row ) )
                        {
                            $return = static::toArray( $row );
                        }

                        switch ( $output )
                        {
                            /** @noinspection PhpMissingBreakStatementInspection */
                        case ARRAY_K:
                            $object_vars = &$return;
                            // continue with next

                        case OBJECT_K:
                            switch ( true )
                            {
                            case is_string( $key ):
                                // keep current key
                                break;

                            case empty( $keyName ):
                                $key = $return ?? static::toArray( $row );
                                $key = $prefix . reset( $key );
                                break;

                            default:
                                $keyNames = (array) $keyName;
                                $key      = null;

                                foreach ( $keyNames as $keyName )
                                {
                                    if ( is_object( $row ) )
                                    {
                                        if ( substr( $keyName, 0, 3 ) === 'get' && method_exists( $row, $keyName ) )
                                        {
                                            $key = $prefix . $row->$keyName();
                                            break;
                                        }

                                        if ( property_exists( $row, $keyName ) )
                                        {
                                            $key = $prefix . $row->$keyName;
                                            break;
                                        }

                                        if ( $object_vars == null )
                                        {
                                            $object_vars = static::toArray( $row );
                                        }
                                    }

                                    if ( array_key_exists( $keyName, $object_vars ) )
                                    {
                                        $key = $prefix . $object_vars[ $keyName ];
                                        break;
                                    }

                                }

                                if ( $key === null )
                                {
                                    throw new ErrorException(
                                        'Key not found in object: ' . implode( ', ', $keyNames )
                                    );
                                }
                            }

                            if ( ! isset( $new_array[ $key ] ) )
                            {
                                $new_array[ $key ] = $return ?? $row;
                            }
                            break;

                        case ARRAY_A:
                            // Return an integer-keyed array of...
                            // ...column name-keyed row arrays.
                            $new_array[] = $return;
                            break;

                        case ARRAY_N:
                            // Return an integer-keyed array of...
                            // ...integer-keyed row arrays.
                            $new_array[] = array_values( $return );
                            break;
                        }
                    }
                );

            }

            $result = $new_array;
            unset( $new_array );
            break;

        default:
            throw new ErrorException( "Unknown output format or class '$output'" );
        }

        if ( $class !== null || ! empty( array_filter( array_column( $result, '__CLASS__' ) ) ) )
        {

            array_walk(
                $result,
                static function ( &$item ) use
                (
                    $class,
                    &
                    $additionalInterfaces
                )
                {

                    static::ensureClass( $item, $class, $additionalInterfaces );
                }
            );

        }

        return $result;

    }


    /**
     *
     * Replaces all occurrences of "wp_" at the beginning of table names with the actual prefix used on this
     * installation.
     *
     * @see https://dev.mysql.com/doc/refman/8.0/en/identifiers.html
     *
     * @param  string  $sqlOrTableName  Full SQL string if quoted identifiers are used. Table name only, if no quotes
     *                                  are used.
     *
     * @return string                   SQL string with replaced prefixes.
     */
    public static function replaceTablePrefix( string $sqlOrTableName ): string
    {

        if ( Core::$wpdb->prefix === 'wp_' )
        {
            return $sqlOrTableName;
        }

        /**
         * Internally, identifiers are converted to and are stored as Unicode (UTF-8). The permissible Unicode characters in identifiers are those in the Basic Multilingual Plane (BMP). Supplementary characters are not permitted. Identifiers thus may contain these characters:
         *
         * Permitted characters in unquoted identifiers:
         * - ASCII:    [0-9,a-z,A-Z$_] (basic Latin letters, digits 0-9, dollar, underscore)
         * - Extended: U+0080 .. U+FFFF
         *
         * Permitted characters in quoted identifiers include the full Unicode Basic Multilingual Plane (BMP), except U+0000:
         * - ASCII:    U+0001 .. U+007F
         * - Extended: U+0080 .. U+FFFF
         *
         * ASCII NUL (U+0000) and supplementary characters (U+10000 and higher) are not permitted in quoted or unquoted identifiers.
         *
         * Identifiers may begin with a digit but unless quoted may not consist solely of digits.
         *
         * Database, table, and column names cannot end with space characters.
         *
         * The identifier quote character is the backtick (`)
         *
         * @TODO         Consider tables that are joined using the comma-syntax, rather then using the JOIN keyword
         * @TODO         Consider tables that are renamed, simple RENAME
         * @TODO         Consider tables that are renamed, multi RENAME
         * @TODO         Consider tables that are renamed, ALTER TABLE ... RENAME
         * @TODO         Consider database-qualified tables
         * @TODO         Consider table-qualified fields
         * @noinspection SpellCheckingInspection
         * @noinspection NotOptimalRegularExpressionsInspection
         */
        return preg_replace(
            <<<'PATTERN'
/
(?<pre>     (?# we capture everything befor the prefix, as variable lengts positive lookbehinds are not possible)
^\s*        (?# for unqoted identifiers, it must be the only string in the whole text. Hence it needs to start with whitespace only:)
|           (?# otherwise, it needs to be a quoted string, either at the beginning of the string or following
                FROM, JOIN, UPDATE, INSERT [INTO], REPLACE [INTO], DELETE, TRUNCATE, ANALIZE, CHECK, REPAIR, DROP
                -> case-insenitive! which is turned on and off with the i-flag
            )
    (?i)(?:
         \s(?:
             FROM
            |JOIN
        )
        |
        (?:^|;)(?:
             UPDATE     (?:\s+ LOW_PRIORITY)? (?:\s+ IGNORE)? 
            |INSERT     (?:\s+ (?: LOW_PRIORITY | DELAYED | HIGH_PRIORITY ))? (?:\s+ IGNORE)? (?:\s+ INTO)?
            |REPLACE    (?:\s+ (?: LOW_PRIORITY | DELAYED ))? (?:\s+ INTO)?
            |DELETE     (?:\s+ LOW_PRIORITY)? (?:\s+ QUICK)? (?:\s+ IGNORE)? \s+ FROM
            |CREATE     \s+ (?:TABLE|VIEW)
            |ALTER      \s+ (?:TABLE|VIEW)
            |DROP       \s+ (?:(?:TEMPORARY\s+)?TABLE|VIEW)(?:\s+IF\s+EXISTS)?
            |RENAME     \s+ TABLE
            |TRUNCATE   (?:\s+ TABLE)?
            |INTO       \s+ TABLE
            |ANALIZE
            |CHECK
            |REPAIR
        )
    )(?-i)\s+
    (`)     (?# the quote is captured, so we can use it as a condition later in the string)

)           (?# end of <pre>)

wp_         (?# the default prefix)

(?<post>    (?# for consistency, we also capture everything after the prefix, eventhough variable-length postive lookaheads are possible)

(?(2)       (?# Permitted characters in quoted identifiers)
            (?:[\x{0001}-\x{005F}\x{0061}-\x{007F}\x{0080}-\x{FFFF}]|``)+
            (?# exclude U+0060 - ` Grave accent, backtick - from single-character range and only allow double backticks 

|           (?# Permitted characters in unquoted identifiers)
            [0-9]?[a-zA-Z$_][0-9a-zA-Z$_]*
)

(?(2)       (?# a quoted string needs to end with a quote followed by a space or the end of text)
            `(?:\s|$)
|           (?# while an unquoted identifiyer may only be followed by whitespace)
            \s*$
)

)           (?# end of <post>)
/
x (?# eXtended: ignore whitespace)
u (?# unicode)
S (?# Study: extra analysis is performed )
PATTERN,
            Core::$wpdb->prefix,
            $sqlOrTableName
        );

    }


    public static function &toArray( $input ): ?array
    {

        if ( $input === null )
        {
            return $input;
        }

        if ( is_array( $input ) )
        {
            return $input;
        }

        if ( ! is_object( $input ) )
        {
            $input = [ $input ];

            return $input;
        }

        if ( method_exists( $input, 'serialize' ) )
        {
            $input = $input->serialize();
        }
        elseif ( method_exists( $input, 'toArray' ) )
        {
            $input = $input->toArray();
        }
        else
        {
            $input = get_object_vars( $input );
        }

        return $input;
    }

}
