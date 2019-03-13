<?php
/**
 * Created by PhpStorm.
 * User: Jurgen
 * Date: 13/03/2019
 * Time: 10:58
 */

namespace BumbalGeocode;


class LatLonResult {

    protected $latitude;
    protected $longitude;
    protected $precision;

    /**
     * LatLonResult constructor.
     * @param array $data
     */
    public function __construct(array $data = []){
        foreach($data as $key => $value){
            if(property_exists($this, $key)){
                $this->$key = $value;
            }
        }
    }

    /**
     * @param float $latitude
     */
    public function setLatitude(float $latitude){
        $this->latitude = $latitude;
    }

    /**
     * @return float
     */
    public function getLatitude(){
        return $this->latitude;
    }

    /**
     * @param float $longitude
     */
    public function setLongitude(float $longitude){
        $this->longitude = $longitude;
    }

    /**
     * @return float
     */
    public function getLongitude(){
        return $this->longitude;
    }

    /**
     * @param float $precision
     */
    public function setPrecision(float $precision){
        $this->precision = $precision;
    }

    /**
     * @return float
     */
    public function getPrecision(){
        return $this->precision;
    }
}