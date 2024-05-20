<?php
/**
 * Class represents entity for one api request. Collects data and then returns data to provide request.
 *
 * @author Jan Malcánek
 */

namespace mervit\iDoklad\request;

include_once __DIR__.'/iDokladFilter.php';
include_once __DIR__.'/iDokladSort.php';

use mervit\iDoklad\iDokladException;
use mervit\iDoklad\request\iDokladFilter;
use mervit\iDoklad\request\iDokladSort;

class iDokladRequest {
    
    /**
     * Holds method (IssuedInvoices)
     * @var string
     */
    private $method;
    
    /**
     * Holds post parameters
     * @var array
     */
    private $postParams = array();
    
    /**
     * Holds get parameters
     * @var array
     */
    private $getParams = array();
    
    /**
     * Holds methodType (e.g. GET, POST)
     * @var string
     */
    private $methodType = 'GET';
    
    /**
     * Holds filters
     * @var array
     */
    private $filters = array();
    
    /**
     * Holds sorts
     * @var array
     */
    private $sorts = array();
    
    /**
     * Holds request lang if set
     * @var string
     */
    private $lang = null;
    
    /**
     * Holds file for cases when we want to send attachement
     * @var CURLFile
     */
    private $file = null;
    
    /**
     * Detects if attachement is being send
     * @var bool
     */
    private $sendAttachement = false;
    
    /**
     * Indicates that response will be binary Base64 encoded file
     * @var boolean
     */
    private $isBinary = false;

    /**
     * Sets type of operator between filters. Possible values are `and` and `or`
     * @var string
     */
    private $filtertype;

    /**
     * Restrict returned data only for specific fields
     * @var array
     */
    private $select;

    /**
     * Include extra entities to response
     * @var array
     */
    private $include;

    /**
     * Optionally initializes request parameters
     * @param string $method
     * @param string $methodType
     * @param array $getParameters
     * @param array $postParameters
     * @throws iDokladException
     */
    public function __construct($method = null, $methodType = 'GET', $getParameters = array(), $postParameters = array(), $filtertype = 'and') {
        $this->method = $method;
        $this->methodType = $methodType;
        $this->getParams = $getParameters;
        $this->postParams = $postParameters;
        $this->setFilterType($filtertype);
    }
    
    /**
     * Sets api method (e.g. IssuedInvoices)
     * @param string $method
     * @return \mervit\iDoklad\request\iDokladRequest
     */
    public function setMethod($method){
        $this->method = $method;
        return $this;
    }
    
    /**
     * Sets api response language
     * @param string $lang
     * @return \mervit\iDoklad\request\iDokladRequest
     */
    public function setLang($lang){
        $this->lang = $lang;
        return $this;
    }

    /**
     * Adds request post parameters from array
     * @param array $params
     * @return \mervit\iDoklad\request\iDokladRequest
     */
    public function addPostParameters(array $params){
        $this->postParams = $params;
        return $this;
    }
    
    /**
     * Adds request post parameter by key and value
     * @param string $key
     * @param string $value
     * @return \mervit\iDoklad\request\iDokladRequest
     */
    public function addPostParameter($key, $value){
        $this->postParams[$key] = $value;
        return $this;
    }
    
    /**
     * Adds request get parameters
     * @param array $params
     * @return \mervit\iDoklad\request\iDokladRequest
     */
    public function addGetParameters(array $params){
        $this->getParams = $params;
        return $this;
    }
    
    /**
     * Adds reuqest get parameters by key and value
     * @param string $key
     * @param string $value
     * @return \mervit\iDoklad\request\iDokladRequest
     */
    public function addGetParameter($key, $value){
        $this->getParams[$key] = $value;
        return $this;
    }
    
    /**
     * Sets method type (e.g. GET, POST)
     * @param string $methodType
     * @return \mervit\iDoklad\request\iDokladRequest
     */
    public function addMethodType($methodType){
        $this->methodType = $methodType;
        return $this;
    }
    
    /**
     * Adds data filter
     * @param \mervit\iDoklad\request\iDokladFilterInterface $filter
     * @return \mervit\iDoklad\request\iDokladRequest
     */
    public function addFilter(iDokladFilterInterface $filter){
        $this->filters[] = $filter;
        return $this;
    }
    
    /**
     * Adds data sort
     * @param \mervit\iDoklad\request\iDokladSort $sort
     * @return \mervit\iDoklad\request\iDokladRequest
     */
    public function addSort(iDokladSort $sort){
        $this->sorts[] = $sort;
        return $this;
    }
    
    /**
     * 
     * @param CURLFile $file
     * @return \mervit\iDoklad\request\iDokladRequest
     */
    public function addFile(\CURLFile $file) {
        $this->file = $file;
        $this->sendAttachement = true;
        return $this;
    }
    
    /**
     * Declares page from pagination
     * @param int $page
     * @return \mervit\iDoklad\request\iDokladRequest
     */
    public function setPage($page){
        $this->getParams['page'] = $page;
        return $this;
    }
    
    /**
     * Declares number of returned items by request
     * @param int $pageSize
     * @return \mervit\iDoklad\request\iDokladRequest
     */
    public function setPageSize($pageSize){
        $this->getParams['pagesize'] = $pageSize;
        return $this;
    }
    
    /**
     * Sets filter type allowed `and` and `or`
     * @param string $type
     * @return \mervit\iDoklad\request\iDokladRequest
     * @throws iDokladException
     */
    public function setFilterType($type): iDokladRequest
    {
        if($type != 'and' && $type != 'or'){
            throw new iDokladException('Filter type must be \'and\' or \'or\'');
        }
        $this->filtertype = $type;
        return $this;
    }

    /**
     * Adds field to requested return data.
     * For nested variables use dot
     * Its possible to add multiple fields and separate them with comma
     */
    public function addSelect(string $field): iDokladRequest
    {

        if(strpos($field, ',')){
            $fields = explode(',', $field);
            foreach ($fields as $f){
                $this->addSelect($f);
            }
            return $this;
        }

        $pointer = &$this->select;
        $fieldParts = explode('.', $field);
        for ($i = 0; $i < count($fieldParts); ++$i){
            if(!isset($pointer[$fieldParts[$i]])) {
                $pointer[$fieldParts[$i]] = [];
            }
            $pointer = &$pointer[$fieldParts[$i]];

        }
        return $this;
    }

    private function buildSelect($fields){
        $selectStrings = [];
        foreach($fields as $selectName => $subFields){
            $selectString = $selectName;
            if(!empty($subFields)){
                $selectString .= '(' . $this->buildSelect($subFields) . ')';
            }
            $selectStrings[] = $selectString;
        }
        return implode(',', $selectStrings);

    }

    /**
     * Include another entities to returned data.
     * For nested variables use dot
     * Its possible to add multiple entities and separate them with comma
     */
    public function addInclude(string $field): iDokladRequest
    {

        if(strpos($field, ',')){
            $fields = explode(',', $field);
            foreach ($fields as $f){
                $this->addInclude($f);
            }
            return $this;
        }

        $pointer = &$this->include;
        $fieldParts = explode('.', $field);
        for ($i = 0; $i < count($fieldParts); ++$i){
            if(!isset($pointer[$fieldParts[$i]])) {
                $pointer[$fieldParts[$i]] = [];
            }
            $pointer = &$pointer[$fieldParts[$i]];

        }
        return $this;
    }

    private function buildInclude($fields){
        $includeStrings = [];
        foreach($fields as $includeName => $subFields){
            $includeString = $includeName;
            if(!empty($subFields)){
                $includeString .= '(' . $this->buildInclude($subFields) . ')';
            }
            $includeStrings[] = $includeString;
        }
        return implode(',', $includeStrings);

    }

    /**
     * Builds http query from get parameters. Auto adds filters and sorts to get query
     * @return string
     * @throws iDokladException
     */
    public function buildGetQuery(){
        $filterString = '';
        foreach($this->filters as $filter){
            $filterString .= $filterString ? ( '~' . $this->filtertype . '~(' . $filter->buildQuery() . ')') : ('(' . $filter->buildQuery() . ')');
        }
        if(!empty($filterString)){
            $this->addGetParameter("filter", $filterString);
        }
        if(!empty($this->select)) {
            $this->addGetParameter("select", $this->buildSelect($this->select));
        }
        if(!empty($this->include)) {
            $this->addGetParameter("include", $this->buildInclude($this->include));
        }
        $sortString = array();
        foreach($this->sorts as $sort){
            $sortString[] = $sort->buildQuery();
        }
        if(!empty($sortString)){
            $this->addGetParameter("sort", implode('|', $sortString));
        }
        if(is_array($this->getParams)){
            return http_build_query($this->getParams);
        } else {
            throw new iDokladException('Get parameters have to be array');
        }
    }
    
    /**
     * Builds http query from post parameters.
     * @return string
     * @throws iDokladException
     */
    public function buildPostQuery(){
        if(is_array($this->postParams)){
            return json_encode($this->postParams);
        } else {
            throw new iDokladException('Post parameters have to be array');
        }
    }
    
    /**
     * Returns setted method to request.
     * @return string
     */
    public function getMethod(){
        return trim($this->method, '/');
    }
    
    /**
     * Returns setted method type.
     * @return string
     */
    public function getMethodType(){
        return $this->methodType;
    }
    
    /**
     * Returns setted lang.
     * @return string
     */
    public function getLang(){
        return $this->lang;
    }
    
    /**
     * Sets request method type as get
     * @return \mervit\iDoklad\request\iDokladRequest
     */
    public function get(){
        $this->methodType = 'GET';
        return $this;
    }
    
    /**
     * Sets request method type as post
     * @return \mervit\iDoklad\request\iDokladRequest
     */
    public function post(){
        $this->methodType = 'POST';
        return $this;
    }
    
    /**
     * Sets request method type as put
     * @return \mervit\iDoklad\request\iDokladRequest
     */
    public function put(){
        $this->methodType = 'PUT';
        return $this;
    }
    
    /**
     * Sets request method type as delete
     * @return \mervit\iDoklad\request\iDokladRequest
     */
    public function delete(){
        $this->methodType = 'DELETE';
        return $this;
    }
    
    /**
     * Sets request method type as patch
     * @return \mervit\iDoklad\request\iDokladRequest
     */
    public function patch(){
        $this->methodType = 'PATCH';
        return $this;
    }
    
    /**
     * Returns post params
     * @return array
     */
    public function getPostParams(){
        return $this->postParams;
    }
    
    /**
     * Return file
     * @return CURLFile
     */
    public function getFile() {
        return $this->file;
    }
    
    /**
     * Returns if we are sending attachement
     * @return bool
     */
    public function isAttachement() {
        return $this->sendAttachement;
    }
    
    /**
     * Indicates that response will be binary Base64 encoded file
     */
    public function binary() {
        $this->isBinary = true;
        return $this;
    }
    
    /**
     * Returns if response is going to be binary
     * @return boolean
     */
    public function isBinary() {
        return $this->isBinary;
    }
}
