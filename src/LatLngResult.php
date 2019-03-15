<?php
/**
 * Created by PhpStorm.
 * User: Jurgen
 * Date: 13/03/2019
 * Time: 10:58
 */

namespace BumbalGeocode;


class LatLngResult {

    /**
     * @var float
     */
    protected $latitude;

    /**
     * @var float
     */
    protected $longitude;


    /**
     * ranges from:
     * not found -> 0
     * found -> (0..1]
     * @var float
     */
    protected $precision = 0.0;

    /**
     * @var bool
     */
    protected $valid = FALSE;

    /**
     * @var string
     */
    protected $error_message;

    /**
     * LatLngResult constructor.
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

    /**
     * @param bool $valid
     */
    public function setValid(bool $valid){
        $this->valid = $valid;
    }

    /**
     * @return bool
     */
    public function isValid(){
        return $this->valid === TRUE;
    }

    /**
     * @param string $error_message
     */
    public function setErrorMessage(string $error_message){
        $this->error_message = $error_message;
    }

    /**
     * @return string
     */
    public function getErrorMessage(){
        return $this->error_message;
    }
}