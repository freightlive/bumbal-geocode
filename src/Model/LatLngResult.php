<?php

namespace BumbalGeocode\Model;


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
    protected $accuracy = 0.0;

    /**
     * @var string
     */
    protected $provider_name;

    /**
     * textual description of this place
     * @var string
     */
    protected $description;
    /**
     * LatLngResult constructor.
     * @param array $data
     */
    public function __construct(/*array*/ $data = []){
        foreach($data as $key => $value){
            if(property_exists($this, $key)){
                $this->$key = $value;
            }
        }
    }

    /**
     * @param float $latitude
     */
    public function setLatitude(/*float*/ $latitude){
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
    public function setLongitude(/*float*/ $longitude){
        $this->longitude = $longitude;
    }

    /**
     * @return float
     */
    public function getLongitude(){
        return $this->longitude;
    }

    /**
     * @param float $accuracy
     */
    public function setAccuracy(/*float*/ $accuracy){
        $this->accuracy = $accuracy;
    }

    /**
     * @return float
     */
    public function getAccuracy(){
        return $this->accuracy;
    }

    /**
     * @param string $provider_name
     */
    public function setProviderName(/*string*/ $provider_name){
        $this->provider_name = $provider_name;
    }

    /**
     * @return string
     */
    public function getProviderName(){
        return $this->provider_name;
    }

    /**
     * @param string $description
     */
    public function setDescription(/*string*/ $description){
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription(){
        return $this->description;
    }

	/**
	 * @return array
	 */
	public function toArray() {
		$vars = get_object_vars($this);

		return $vars;
	}
}
