<?php

namespace BumbalGeocode\Model;

use BumbalGeocode\Model\LatLngResult;


class LatLngResultList extends ResultList {

    /**
     * LatLngResultList constructor.
     * @param array $lat_lng_results
     */
    public function __construct(/*array*/ $lat_lng_results = []) {
        $this->items = $lat_lng_results;
    }

    /**
     * Merges a LatLngResultList into this one.
     * @param LatLngResultList $lat_lng_result_list
     */
    public function merge(LatLngResultList $lat_lng_result_list){
        $this->items = array_merge($this->items, $lat_lng_result_list->getLatLngResults());
        $this->log = array_merge($this->log, $lat_lng_result_list->getLog());
        $this->errors = array_merge($this->errors, $lat_lng_result_list->getErrors());
        $this->orderLatLngResults();
    }

    /**
     * @param LatLngResult $lat_lng_result
     */
    public function setLatLngResult(LatLngResult $lat_lng_result){
        $this->items[] = $lat_lng_result;
        $this->orderLatLngResults();
    }

    /**
     * @param array $lat_lng_results
     */
    public function setLatLngResults(/*array*/ $lat_lng_results){
        $this->items = $lat_lng_results;
        $this->orderLatLngResults();
    }

    /**
     * @return array
     */
    public function getLatLngResults(){
        return $this->items;
    }

    /**
     * Order results based on accuracy
     */
    private function orderLatLngResults(){
        usort($this->items, function(LatLngResult $a, LatLngResult $b){
            $a_accuracy = $a->getAccuracy();
            $b_accuracy = $b->getAccuracy();
            if($a_accuracy == $b_accuracy){
                return 0;
            }
            return ($a_accuracy < $b_accuracy) ? 1 : -1;
        });
    }
}
