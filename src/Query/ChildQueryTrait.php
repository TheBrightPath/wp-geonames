<?php

namespace WPGeonames\Query;

use ErrorException;

trait ChildQueryTrait
{

// protected properties

    /** @var \WPGeonames\Query\ParentQueryInterface|\WPGeonames\Query\Query */
    protected $parent;


    /**
     * ApiSubQuery constructor.
     *
     * @param  \WPGeonames\Query\ParentQueryInterface|array  $parent
     * @param  null                                          $defaultsWillBeIgnored
     *
     * @throws ErrorException
     */
    public function __construct(
        $parent,
        $defaultsWillBeIgnored = null
    ) {

        // since this is a sub-query, the parent needs to be provided
        if ( ! ( $parent instanceof ParentQueryInterface
            || ( is_array( $parent )
                && ( $parent = $parent['parent'] ?? $defaultsWillBeIgnored['parent'] ?? null )
            ) instanceof ParentQueryInterface ) )
        {
            throw new ErrorException(
                sprintf(
                    '%s requires "parent" property to be an instance of %s',
                    static::class,
                    ParentQueryInterface::class
                )
            );
        }

        $this->parent = $parent;

        if ( property_exists( $this, 'queryId' ) && method_exists( $parent, 'getQueryId' ) )
        {
            $this->queryId = $parent->getQueryId();

            if ( $this->queryId === 0 )
            {
                $this->queryId = null;
            }
        }

    }


    /**
     * @return Query
     */
    public function getParent(): Query
    {

        return $this->parent;
    }


    /**
     * @param  Query  $parent
     *
     * @return ChildQueryTrait
     */
    public function setParent( Query $parent ): ChildQueryTrait
    {

        $this->parent = $parent;

        return $this;
    }


    public function getSearchType(): int
    {

        return $this->parent->getSearchType();
    }


    /**
     * @param $searchType
     *
     * @return \WPGeonames\Query\Query
     * @throws \ErrorException
     * @noinspection PhpUnusedParameterInspection
     */
    public function setSearchType( $searchType ): Query
    {

        /** @noinspection GetClassUsageInspection */
        throw new ErrorException(
            sprintf( '%s cannot be used. Set value on parent %s', __METHOD__, get_class( $this->parent ) )
        );
    }

}