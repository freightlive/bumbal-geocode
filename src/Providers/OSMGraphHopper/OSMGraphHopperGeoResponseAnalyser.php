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

    const OSM_KEY_STREET = 'highway';
    const OSM_KEY_CITY = 'place';


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

    public function getAddressStringFromResult(array $data){
        return $this->makeAddressFromAddressComponents($data)->getAddressString();
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
    protected function getValueAddressComponentsCompare(array $osm_result, Address $address){
        $address_from_osm = $this->makeAddressFromAddressComponents($osm_result);

        $result = $address->compare($address_from_osm);
        return $result;
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
        $area_km2 = $this->surfaceArea($bounds[1], $bounds[0], $bounds[3], $bounds[2]);

        //1 km2 is too much, 0 is perfect
        if($area_km2 > 1.0){
            return 0.0;
        }

        return 1.0 - $area_km2;

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

        if(empty($address_array['street']) && $osm_result['osm_key'] == self::OSM_KEY_STREET){
            $address_array['street'] = $osm_result['name'];
        }

        if(empty($address_array['city']) && $osm_result['osm_key'] == self::OSM_KEY_CITY){
            $address_array['city'] = $osm_result['name'];
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
     * Surface area in km2
     * @param $latFrom
     * @param $lonFrom
     * @param $latTo
     * @param $lonTo
     * @param float $earthRadius (km)
     * @return float
     */
    private function surfaceArea($latFrom, $lonFrom, $latTo, $lonTo, $earthRadius = 6378.000)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($latFrom);
        $lonFrom = deg2rad($lonFrom);
        $latTo = deg2rad($latTo);
        $lonTo = deg2rad($lonTo);

        return $earthRadius * $earthRadius * abs(sin($latTo)-sin($latFrom)) * abs($lonTo-$lonFrom);
    }


}