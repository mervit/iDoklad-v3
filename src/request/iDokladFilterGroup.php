<?php
/**
 * Class entity that represents many filter group
 *
 * @author mervit Vítězslav Mergl
 */

namespace mervit\iDoklad\request;

use mervit\iDoklad\iDokladException;
use mervit\iDoklad\request\iDokladFilter;
use mervit\iDoklad\request\iDokladRequest;

class iDokladFilterGroup implements iDokladFilterInterface {

    /**
     * Array of filters in group
     * @var array
     */
    private $filters;

    /**
     * Filter type allowed `and` and `or`
     * @var string
     */
    private $filtertype;

    /**
     * Adds data filter
     * @param iDokladFilterInterface $filter
     * @return iDokladFilterGroup
     */
    public function addFilter(iDokladFilterInterface $filter): iDokladFilterGroup
    {
        $this->filters[] = $filter;
        return $this;
    }

    /**
     * Sets filter type allowed 'and' and 'or'
     * @param string $type
     * @return iDokladFilterGroup
     * @throws iDokladException
     */
    public function setFilterType(string $type): iDokladFilterGroup
    {
        if($type != 'and' && $type != 'or'){
            throw new iDokladException('Filter type must be \'and\' or \'or\'');
        }
        $this->filtertype = $type;
        return $this;
    }

    /**
     * Optionally initialize filter group.
     * @param string $filterType
     * @throws iDokladException
     */
    public function __construct(string $filterType = 'and') {
        $this->setFilterType($filterType);
    }

    public function buildQuery(): string
    {

        $filterString = '';
        foreach($this->filters as $filter){
            $filterString .= $filterString ? ( '~' . $this->filtertype . '~(' . $filter->buildQuery() . ')') : ('(' . $filter->buildQuery() . ')');
        }

        return $filterString;

    }
}