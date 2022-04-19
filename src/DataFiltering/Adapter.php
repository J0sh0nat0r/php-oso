<?php

namespace J0sh0nat0r\Oso\DataFiltering;

/** 
 * @template TQuery
 * @template TResource
 */
interface Adapter
{
    /**
     * @return TQuery
     */
    public function buildQuery(Filter $filter);

    /**
     * @param TQuery $query
     *
     * @return iterable<TResource>
     */
    public function executeQuery($query): iterable;
}
