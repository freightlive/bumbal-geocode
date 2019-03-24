<?php

namespace BumbalGeocode\Providers\OSMGraphHopper;

use BumbalGeocode\GeoResponseAnalyser;
use BumbalGeocode\Model\Address;

class OSMGraphHopperGeoResponseAnalyser extends GeoResponseAnalyser{

    const ISO_CODES_FILE = 'iso_codes_en.json';

    const OSM_TYPE_NODE = 'N';
    const OSM_TYPE_WAY = 'W';
    const OSM_TYPE_RELATION = 'R';

    /**
     * @todo tweak values
     */
    const VALUE_OSM_RESULT_TYPES = [
        self::OSM_TYPE_NODE => 1.0,
        self::OSM_TYPE_WAY => 0.7,
        self::OSM_TYPE_RELATION => 0.0
    ];

    const OSM_HOUSE_NR = 'housenumber';
    const OSM_STREET = 'street';
    const OSM_ZIPCODE = 'postcode';
    const OSM_CITY = 'city';
    const OSM_COUNTRY = 'country';

    const MAP_OSM_ADDRESS_COMPONENT_TO_ADDRESS = [
        self::OSM_HOUSE_NR => 'house_nr',
        self::OSM_STREET => 'street',
        self::OSM_ZIPCODE => 'zipcode',
        self::OSM_CITY => 'city',
        self::OSM_COUNTRY => 'iso_country'
    ];

    /**
     * OSMGraphHopperGeoResponseAnalyser constructor.
     * @param array $weights
     */
    public function __construct(array $weights = []) {
        parent::__construct($weights);
    }

    /**
     * @param array $osm_result
     * @param Address $address
     * @return float
     */
    protected function getValueResultType(array $osm_result, Address $address){
        return max(array_intersect_key(self::VALUE_OSM_RESULT_TYPES, array_flip([$osm_result['osm_type']])));
    }


    /**
     * @param array $osm_result
     * @param Address $address
     * @return float
     */
     /*protected function getValueLocationType(array $osm_result, Address $address){

         $osm_key = $osm_result['osm_key'];
         $osm_value = $osm_result['osm_value'];

         //return max(array_intersect_key(self::PRECISION_OSM_RESULT_TYPES, array_flip([$osm_result['osm_type']])));
         return 0.0;
     }*/

    /**
     * @param array $osm_result
     * @param Address $address
     * @return float
     */
    protected function getValueAddressComponentsEquals(array $osm_result, Address $address){
        $address_from_osm = $this->makeAddressFromAddressComponents($osm_result);
        return $address->compare($address_from_osm);
    }

    /**
     * @param array $osm_result
     * @param Address $address
     * @return float
     */
    protected function getValueAddressComponentsSimilarity(array $osm_result, Address $address){
        $address_from_osm = $this->makeAddressFromAddressComponents($osm_result);
        return $address->similarity($address_from_osm);
    }

    /**
     * @param array $osm_result
     * @param Address $address
     * @return float
     */
    protected function getValueBoundingBox(array $osm_result, Address $address){
        //no bounds in result, so result is a point
        if(empty($osm_result['extent'])){
            return 1.0;
        }
        $bounds = $osm_result['extent'];

        //calculate distance on earth's surface between points of bounding box
        $distance_meters = $this->haversineGreatCircleDistance($bounds[1], $bounds[0], $bounds[3], $bounds[2]);

        //1000 meters is too much, 0 meters is perfect
        if($distance_meters > 1000.0){
            return 0.0;
        }

        return 1.0 - $distance_meters / 1000.0;

    }

    /**
     * @param array $osm_result
     * @return Address
     */
    private function makeAddressFromAddressComponents(array $osm_result){
        $address_array = [];
        foreach(self::MAP_OSM_ADDRESS_COMPONENT_TO_ADDRESS as $osm_key => $address_key){
            if(!empty($osm_result[$osm_key])){
                $address_array[$address_key] = $osm_result[$osm_key];
            }
        }

        //try to get iso code for country
        $iso_codes = array_flip($this->getISOCodes());
        if(!empty($iso_codes[$address_array['iso_country']])){
            $address_array['iso_country'] = $iso_codes[$address_array['iso_country']];
        }

        return new Address($address_array);
    }

    /**
     * @return array
     */
    private function getISOCodes(){
        static $iso_codes = [];

        if(!empty($iso_codes)){
            return $iso_codes;
        }

        $file_name = dirname(__FILE__).DIRECTORY_SEPARATOR.self::ISO_CODES_FILE;
        if(file_exists($file_name)){
            $iso_codes = json_decode(file_get_contents($file_name), TRUE);
        }
        return $iso_codes;
    }

    /**
     * Calculates the great-circle distance between two points, with
     * the Haversine formula.
     * @param float $latitudeFrom Latitude of start point in [deg decimal]
     * @param float $longitudeFrom Longitude of start point in [deg decimal]
     * @param float $latitudeTo Latitude of target point in [deg decimal]
     * @param float $longitudeTo Longitude of target point in [deg decimal]
     * @param float $earthRadius Mean earth radius in [m]
     * @return float Distance between points in [m] (same as earthRadius)
     */
    function haversineGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000.0)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return abs($angle * $earthRadius);
    }


}