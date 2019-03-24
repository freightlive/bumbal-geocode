<?php

namespace BumbalGeocode;

use BumbalGeocode\Model\Address;

class GeoResponseAnalyser {

    protected $weights = [];

    protected $valueMethodNames = [];
    protected $valueKeys = [];

    /**
     * GeoPrecisionAnalyser constructor.
     * @param array $weights
     * @throws \Exception
     */
    public function __construct(array $weights = []) {
        $this->init();
        $this->setWeights($weights);
    }

    /**
     * @param array $data
     * @param Address $address
     * @return float
     */
    public function getValue(array $data, Address $address){

        $values = [];
        foreach($this->valueMethodNames as $index => $method_name){
            $key = $this->valueKeys[$index];

            //skip execution method if weight for the result is zero
            if($this->weights[$key] == 0.0){
                $values[$key] = 0.0;
            } else {
                $values[$key] = $this->$method_name($data, $address);
            }

        }

        $values_weighted = array_map(function($value, $weight) {
            return $value * $weight;
        }, $values, $this->weights);

        $count_values = count($values_weighted);
        if($count_values == 0){
            return 0.0;
        }
        return array_sum($values_weighted)/$count_values;
    }

    /**
     * @throws \Exception
     */
    private function init() {
        $this->valueMethodNames = preg_grep('/^getValue[A-Z]/', get_class_methods($this));

        //no getValue methods implemented?
        if (empty($this->valueMethodNames)) {
            $this->valueMethodNames = [];
        }

        sort($this->valueMethodNames);
        $this->valueKeys = $this->valueMethodNames;
        array_walk($this->valueKeys, function(&$method_name, $key, $make_key_method){$method_name = $make_key_method($method_name);}, [$this, 'makeKeyFromMethodName']);
    }

    /**
     * sets weights and all uninitialized weights to 0
     * @param array $weights
     */
    public function setWeights(array $weights = []){

        foreach($this->valueKeys as $key){
            if(isset($weights[$key])){
                $this->weights[$key] = $weights[$key];
            } else {
                $this->weights[$key] = 0.0;
            }
        }
        $this->normalizeWeights();
    }

    /**
     * @return array
     */
    public function getWeights(){
        return $this->weights;
    }

    /**
     * @return array
     */
    public function getKeys(){
        return $this->valueKeys;
    }

    private function normalizeWeights(){
        //make array_sum equal to count($array)
        $count = count($this->weights);
        if($count != 0) {
            $multiplier = $count / array_sum($this->weights);
            array_walk($this->weights, function (&$value, $key, $multiplier) {
                $value = $value * $multiplier;
            }, $multiplier);
        }
    }

    private function makeKeyFromMethodName($string) {
        return str_replace('get_value_', '', strtolower( preg_replace( '/([A-Z])/', '_$1', lcfirst( $string ) ) ));
    }
}