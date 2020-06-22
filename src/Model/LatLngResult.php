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
     * @var string
     */
    protected $provider_id;

    /**
     * textual description of this place
     * @var string
     */
    protected $description;

    /**
     * @var Address
     */
    protected $address;

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
     * @param string $provider_id
     */
    public function setProviderId(/*string*/ $provider_id){
        $this->provider_id = $provider_id;
    }

    /**
     * @return string
     */
    public function getProviderId(){
        return $this->provider_id;
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
     * @param Address $address
     */
    public function setAddress(/*Address*/ $address){
        $this->address = $address;
    }

    /**
     * @return Address
     */
    public function getAddress(){
        return $this->address;
    }

    /**
     * @param LatLngResult $other
     * @return float
     */
    public function distance(LatLngResult $other) {
        return $this->vincentyGreatCircleDistance($this->getLatitude(), $this->getLongitude(), $other->getLatitude(), $other->getLongitude());
    }

    /**
     * Calculates the great-circle distance between two points, with
     * the Vincenty formula.
     * @param float $latitudeFrom Latitude of start point in [deg decimal]
     * @param float $longitudeFrom Longitude of start point in [deg decimal]
     * @param float $latitudeTo Latitude of target point in [deg decimal]
     * @param float $longitudeTo Longitude of target point in [deg decimal]
     * @param int $earthRadius Mean earth radius in [m]
     * @return float Distance between points in [m] (same as earthRadius)
     */
    private function vincentyGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000) {
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) +
            pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

        $angle = atan2(sqrt($a), $b);
        return $angle * $earthRadius;
    }

	/**
	 * @return array
	 */
	public function toArray() {
		$vars = get_object_vars($this);

		return $vars;
	}
}
