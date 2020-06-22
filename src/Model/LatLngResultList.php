<?php

namespace BumbalGeocode\Model;

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
    protected $reports = [];

    /**
     * @var array
     */
    protected $lat_lng_results = [];

    
    /**
     * LatLngResultList constructor.
     * @param array $lat_lng_results
     */
    public function __construct(/*array*/ $lat_lng_results = []) {
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
        $this->reports = array_merge($this->reports, $lat_lng_result_list->getReports());
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
    public function setLatLngResults(/*array*/ $lat_lng_results){
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
    public function setLogMessage(/*string*/ $message){
        $this->log[] = $message;
    }

    /**
     * @param array $messages
     */
    public function setLog(/*array*/ $messages){
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
    public function setError(/*string*/ $message){
        $this->errors[] = $message;
    }

    /**
     * @param array $messages
     */
    public function setErrors(/*array*/ $messages){
        $this->errors = $messages;
    }

    /**
     * @return bool
     */
    public function hasErrors(){
        return count($this->errors) > 0;
    }

    /**
     * @return bool
     */
    public function hasResults() {
        return count($this->lat_lng_results) != 0;
    }

    /**
     * @return array
     */
    public function getReports(){
        return $this->reports;
    }

    /**
     * @param Report $report
     */
    public function setReport(Report $report){
        $this->reports[] = $report;
    }

    /**
     * @param string $provider_name
     * @return LatLngResult|null
     */
    public function getBestResult(/*string*/ $provider_name = '') {
        foreach($this->lat_lng_results as $lat_lng_result) {
            /**
             * @var LatLngResult $lat_lng_result
             */
            if(empty($provider_name) || $lat_lng_result->getProviderName() == $provider_name) {
                return $lat_lng_result;
            }
        }

        return null;
    }

    /**
     * @param string $provider_name
     * @return LatLngResult|null
     */
    public function getBestResultFromReports(/*string*/ $provider_name) {
        $best_accuracy = 0.0;
        $result = null;
        foreach($this->reports as $report) {
            /**
             * @var Report $report
             */
            if($report->provider_name == $provider_name) {
                /**
                 * @var LatLngResult $report_result
                 */
                $report_result = $report->getBestResult();
                if(!$report_result) {
                    continue;
                }

                if($report_result->getAccuracy() > $best_accuracy) {
                    $result = $report_result;
                    $best_accuracy = $report_result->getAccuracy();
                }
            }
        }

        return $result;
    }


    /**
     * Order results based on accuracy
     */
    private function orderLatLngResults(){
        usort($this->lat_lng_results, function(LatLngResult $a, LatLngResult $b){
            $a_accuracy = $a->getAccuracy();
            $b_accuracy = $b->getAccuracy();
            if($a_accuracy == $b_accuracy){
                return 0;
            }
            return ($a_accuracy < $b_accuracy) ? 1 : -1;
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
