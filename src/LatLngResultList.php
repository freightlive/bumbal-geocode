<?php

namespace BumbalGeocode;

use BumbalGeocode\LatLngResult;
use \Countable;
use \Iterator;

class LatLngResultList implements Iterator, Countable {

    /**
     * @var array
     */
    protected $log = [];

    /**
     * @var array
     */
    protected $lat_lng_results = [];

    /**
     * LatLngResultList constructor.
     * @param array $lat_lng_results
     */
    public function __construct(array $lat_lng_results) {
        $this->lat_lng_results = $lat_lng_results;
    }

    /**
     * Merges a LatLngResultList into this one.
     * @param LatLngResultList $lat_lng_result_list
     */
    public function merge(LatLngResultList $lat_lng_result_list){
        $this->lat_lng_results = array_merge($this->lat_lng_results, $lat_lng_result_list->getLatLngResults());
        $this->log = array_merge($this->log, $lat_lng_result_list->getLog());
        $this->orderLatLngResults();
    }

    /**
     * @param \BumbalGeocode\LatLngResult $lat_lng_result
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
}