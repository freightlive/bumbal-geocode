<?php

namespace BumbalGeocode;

use BumbalGeocode\Model\Address;

class GeoPrecisionAnalyser {

    protected $weights = [];

    protected $precisionMethodNames = [];
    protected $precisionKeys = [];

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
    public function getPrecision(array $data, Address $address){

        $values = [];
        foreach($this->precisionMethodNames as $index => $method_name){
            $values[$this->precisionKeys[$index]] = $this->$method_name($data, $address);
        }

        $values_weighted = array_map(function($value, $weight) {
            return $value * $weight;
        }, $values, $this->weights);

        return array_sum($values_weighted)/count($values_weighted);
    }

    /**
     * @throws \Exception
     */
    private function init(){
        $this->precisionMethodNames = preg_grep('/^precision[A-Z]/', get_class_methods($this));

        if(empty($this->precisionMethodNames)){
            throw new \Exception('No precision methods implemented in '.get_class($this));
        }
        sort($this->precisionMethodNames);
        $this->precisionKeys = $this->precisionMethodNames;
        array_walk($this->precisionKeys, function(&$method_name, $key, $make_key_method){$method_name = $make_key_method($method_name);}, [$this, 'makeKeyFromMethodName']);
    }

    /**
     * sets weights and all uninitialized weights to 1
     * @param array $weights
     */
    public function setWeights(array $weights = []){

        foreach($this->precisionKeys as $key){
            if(isset($weights[$key])){
                $this->weights[$key] = $weights[$key];
            } else {
                $this->weights[$key] = 1.0;
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
        return $this->precisionKeys;
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
        return str_replace('precision_', '', strtolower( preg_replace( '/([A-Z])/', '_$1', lcfirst( $string ) ) ));
    }
}