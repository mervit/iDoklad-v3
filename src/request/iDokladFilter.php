<?php
/**
 * Class entity that represents one request filter. Every request can have multiple filters.
 *
 * @author Jan Malcánek
 */

namespace mervit\iDoklad\request;

use mervit\iDoklad\iDokladException;
use mervit\iDoklad\request\iDokladFilterInterface;

class iDokladFilter implements iDokladFilterInterface {
    
    /**
     * Consists of allowed operators.
     * @var array
     */
    private $queryOperators = array('lt' => '<', 'lte' => '<=', 'gt' => '>', 'gte' => '>=', 'eq' => '==', '!eq' => '!=', 'ct' => 'contains', '!ct' => '!contains', 'between' => '<>');

    /**
     * Set code able string operators
     * @var array
     */
    private $codeableOperators = ['eq', '!eq', 'ct', '!ct'];

    /**
     *
     * @var string
     */
    private $querySepartor = '~';
    
    /**
     * Name of property to filter
     * @var string
     */
    private $propertyName;
    
    /**
     * Filter operator
     * @var string
     */
    private $operator;
    
    /**
     * Filter value
     * @var string|array
     */
    private $propertyValue;

    /**
     * Property value coding
     * @var bool
     */
    private $propertyValueCoded;

    /**
     * Optionally initialize whole filter.
     * @param string $propertyName
     * @param string $operator
     * @param string|array $propertyValue
     * @param bool $propertyValueCoded
     * @throws iDokladException
     */
    public function __construct(string $propertyName, string $operator, $propertyValue, bool $propertyValueCoded = false) {
        if(($operator == '<>' || $operator == 'between') && !is_array($propertyValue)){
            throw new iDokladException('propertyValue has to be array when using between operator');
        }
        $this->propertyName = $propertyName;
        if(!in_array($operator, $this->queryOperators) && !in_array($operator, array_keys($this->queryOperators))){
            throw new iDokladException('Invalid operator');
        } elseif(in_array($operator, $this->queryOperators)) {
            $this->operator = array_search($operator, $this->queryOperators);
        } else {
            $this->operator = $operator;
        }
        if($propertyValueCoded && !in_array($operator, $this->codeableOperators)){
            throw new iDokladException('Selected operator is not codeable');
        } else {
            $this->propertyValueCoded = $propertyValueCoded;
        }

        $this->propertyValue = $propertyValue;
    }
    
    /**
     * Adds property name of filter
     * @param string $propertyName
     * @return iDokladFilter
     */
    public function addPropertyName(string $propertyName): iDokladFilter
    {
        $this->propertyName = $propertyName;
        return $this;
    }
    
    /**
     * Adds filter operator
     * @param string $operator
     * @return iDokladFilter
     * @throws iDokladException
     */
    public function addOperator(string $operator): iDokladFilter
    {
        if(!in_array($operator, $this->queryOperators) && !in_array($operator, array_keys($this->queryOperators))){
            throw new iDokladException('Invalid operator');
        } elseif(in_array($operator, $this->queryOperators)) {
            $this->operator = array_search($operator, $this->queryOperators);
        } else {
            $this->operator = $operator;
        }
        return $this;
    }
    
    /**
     * Adds filter value, mostly string, in case of operator between array
     * @param string $propertyValue
     * @return iDokladFilter
     */
    public function addPropertyValue(string $propertyValue): iDokladFilter
    {
        $this->propertyValue = $propertyValue;
        return $this;
    }

    /**
     * Get coded value if it`s needed to be coded
     */
    private function getOperatorWithCoding(){

        if($this->propertyValueCoded){
            return $this->operator . ':base64';
        }
        return $this->operator;

    }

    /**
     * Builds filter string by its specification
     * @return string
     * @throws iDokladException
     */
    public function buildQuery(): string
    {
        if($this->propertyValue == ''){
            throw new iDokladException("Property value in filter cannot be empty");
        }
        if(empty($this->propertyName)){
            throw new iDokladException("Property name in filter cannot be empty");
        }
        if(empty($this->operator)){
            throw new iDokladException("Operator in filter cannot be empty");
        }
        
        if(($this->operator == '<>' || $this->operator == 'between') && !is_array($this->propertyValue)){
            throw new iDokladException('propertyValue has to be array when using between operator');
        } elseif($this->operator == '<>' || $this->operator == 'between') {
            return $this->propertyName.$this->querySepartor.'>'.array_shift($this->propertyValue).$this->querySepartor.'and'.$this->querySepartor.$this->propertyName.$this->querySepartor.'<'.array_shift($this->propertyValue);
        }
        
        if(!in_array($this->operator, array_keys($this->queryOperators))){
            throw new iDokladException('Invalid operator');
        } else {
            return $this->propertyName.$this->querySepartor.$this->getOperatorWithCoding().$this->querySepartor.$this->propertyValue;
        }
    }
}
