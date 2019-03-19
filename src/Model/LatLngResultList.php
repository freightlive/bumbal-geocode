<?php

namespace BumbalGeocode\Model;

use BumbalGeocode\Model\LatLngResult;
use \Countable;
use \Iterator;
use \ArrayAccess;

class LatLngResultList implements Iterator, Countable, ArrayAccess {

    /**
     * @var array
     */
    protected $log = [];

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var array
     */
    protected $lat_lng_results = [];

    /**
     * LatLngResultList constructor.
     * @param array $lat_lng_results
     */
    public function __construct(array $lat_lng_results = []) {
        $this->lat_lng_results = $lat_lng_results;
    }

    /**
     * Merges a LatLngResultList into this one.
     * @param LatLngResultList $lat_lng_result_list
     */
    public function merge(LatLngResultList $lat_lng_result_list){
        $this->lat_lng_results = array_merge($this->lat_lng_results, $lat_lng_result_list->getLatLngResults());
        $this->log = array_merge($this->log, $lat_lng_result_list->getLog());
        $this->errors = array_merge($this->errors, $lat_lng_result_list->getErrors());
        $this->orderLatLngResults();
    }

    /**
     * @param LatLngResult $lat_lng_result
     */
    public function setLatLngResult(LatLngResult $lat_lng_result){
        $this->lat_lng_results[] = $lat_lng_result;
        $this->orderLatLngResults();
    }

    /**
     * @param array $lat_lng_results
     */
    public function setLatLngResults(array $lat_lng_results){
        $this->lat_lng_results = $lat_lng_results;
        $this->orderLatLngResults();
    }

    /**
     * @return array
     */
    public function getLatLngResults(){
        return $this->lat_lng_results;
    }

    /**
     * @return array
     */
    public function getLog(){
        return $this->log;
    }

    /**
     * @param string $message
     */
    public function setLogMessage(string $message){
        $this->log[] = $message;
    }

    /**
     * @param array $messages
     */
    public function setLog(array $messages){
        $this->log = $messages;
    }


    /**
     * @return array
     */
    public function getErrors(){
        return $this->errors;
    }

    /**
     * @param string $message
     */
    public function setError(string $message){
        $this->errors[] = $message;
    }

    /**
     * @param array $messages
     */
    public function setErrors(array $messages){
        $this->errors = $messages;
    }

    /**
     * @return bool
     */
    public function hasErrors(){
        return count($this->errors) > 0;
    }

    /**
     * Order results based on precision
     */
    private function orderLatLngResults(){
        usort($this->lat_lng_results, function(LatLngResult $a, LatLngResult $b){
            return $a->getPrecision() > $b->getPrecision();
        });
    }

    function count(){
        return count($this->lat_lng_results);
    }

    function rewind() {
        return reset($this->lat_lng_results);
    }

    function current() {
        return current($this->lat_lng_results);
    }

    function key() {
        return key($this->lat_lng_results);
    }

    function next() {
        return next($this->lat_lng_results);
    }

    function valid() {
        return key($this->lat_lng_results) !== null;
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->lat_lng_results[] = $value;
        } else {
            $this->lat_lng_results[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->lat_lng_results[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->lat_lng_results[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->lat_lng_results[$offset]) ? $this->lat_lng_results[$offset] : null;
    }
}