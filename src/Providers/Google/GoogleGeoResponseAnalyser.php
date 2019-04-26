<?php

namespace BumbalGeocode\Providers\Google;


use BumbalGeocode\GeoResponseAnalyser;
use BumbalGeocode\Model\Address;

class GoogleGeoResponseAnalyser extends GeoResponseAnalyser {

    const GOOGLE_RESULT_TYPE_STREET_ADDRESS = 'street_address';
    const GOOGLE_RESULT_TYPE_STREET_NUMBER = 'street_number';
    const GOOGLE_RESULT_TYPE_ROUTE = 'route';
    const GOOGLE_RESULT_TYPE_LOCALITY = 'locality';
    const GOOGLE_RESULT_TYPE_SUBLOCALITY = 'sublocality';
    const GOOGLE_RESULT_TYPE_POSTAL_CODE = 'postal_code';
    const GOOGLE_RESULT_TYPE_COUNTRY = 'country';
    const GOOGLE_RESULT_TYPE_ADMINISTRATIVE_AREA = 'administrative_area_level_2';

    /**
     * @todo tweak values
     */
    const VALUE_GOOGLE_RESULT_TYPES = [
        self::GOOGLE_RESULT_TYPE_STREET_ADDRESS => 1.0,
        self::GOOGLE_RESULT_TYPE_ROUTE => 0.9,
        self::GOOGLE_RESULT_TYPE_SUBLOCALITY => 0.6,
        self::GOOGLE_RESULT_TYPE_LOCALITY => 0.5,
        self::GOOGLE_RESULT_TYPE_POSTAL_CODE => 0.8
    ];

    const GOOGLE_LOCATION_TYPE_ROOFTOP = 'ROOFTOP';
    const GOOGLE_LOCATION_TYPE_RANGE_INTERPOLATED = 'RANGE_INTERPOLATED';
    const GOOGLE_LOCATION_TYPE_GEOMETRIC_CENTER = 'GEOMETRIC_CENTER';
    const GOOGLE_LOCATION_TYPE_APPROXIMATE = 'APPROXIMATE';

    /**
     * @todo tweak values
     */
    const VALUE_GOOGLE_LOCATION_TYPES = [
        self::GOOGLE_LOCATION_TYPE_ROOFTOP => 1.0,
        self::GOOGLE_LOCATION_TYPE_RANGE_INTERPOLATED => 0.9,
        self::GOOGLE_LOCATION_TYPE_GEOMETRIC_CENTER => 0.6,
        self::GOOGLE_LOCATION_TYPE_APPROXIMATE => 0.5
    ];

    const MAP_GOOGLE_ADDRESS_COMPONENT_TO_ADDRESS = [
        self::GOOGLE_RESULT_TYPE_STREET_NUMBER => 'house_nr',
        self::GOOGLE_RESULT_TYPE_ROUTE => 'street',
        self::GOOGLE_RESULT_TYPE_POSTAL_CODE => 'zipcode',
        self::GOOGLE_RESULT_TYPE_LOCALITY => 'city',
        self::GOOGLE_RESULT_TYPE_COUNTRY => 'iso_country',
        self::GOOGLE_RESULT_TYPE_ADMINISTRATIVE_AREA => 'city'
    ];

    /**
     * GoogleGeoResponseAnalyser constructor.
     * @param array $weights
     */
    public function __construct(array $weights = []) {
        parent::__construct($weights);
    }

    public function getAddressStringFromResult(array $data){
        return $this->makeAddressFromAddressComponents($data['address_components'])->getAddressString();
    }

    /**
     * @param array $google_result
     * @param Address $address
     * @return float
     */
    protected function getValueResultTypes(array $google_result, Address $address){
        return max(array_intersect_key(self::VALUE_GOOGLE_RESULT_TYPES, array_flip($google_result['types'])));
    }

    /**
     * @param array $google_result
     * @param Address $address
     * @return float
     */
    protected function getValueLocationType(array $google_result, Address $address){
        if(self::VALUE_GOOGLE_LOCATION_TYPES[$google_result['geometry']['location_type']] !== null){
            return self::VALUE_GOOGLE_LOCATION_TYPES[$google_result['geometry']['location_type']];
        }
        return 0.0;
    }

    /**
     * @param array $google_result
     * @param Address $address
     * @return float
     */
    protected function getValueAddressComponentsCompare(array $google_result, Address $address){
        $address_from_google = $this->makeAddressFromAddressComponents($google_result['address_components']);
        return $address->compare($address_from_google);
    }


    /**
     * @param array $google_result
     * @param Address $address
     * @return float
     */
    protected function getValueAddressComponentsSimilarity(array $google_result, Address $address){
        $address_from_google = $this->makeAddressFromAddressComponents($google_result['address_components']);
        return $address->similarity($address_from_google);
    }

    /**
     * @param array $google_result
     * @param Address $address
     * @return float
     */
     protected function getValueBoundingBox(array $google_result, Address $address){
         //no bounds in result, so result is a point
         if(empty($google_result['geometry']['bounds'])){
             return 1.0;
         }
         $bounds = $google_result['geometry']['bounds'];

         //calculate distance on earth's surface between points of bounding box
         $area_km2 = $this->surfaceArea($bounds['northeast']['lat'], $bounds['northeast']['lng'], $bounds['southwest']['lat'], $bounds['southwest']['lng']);

         //1 km2 is too much, 0 is perfect
         if($area_km2 > 1.0){
             return 0.0;
         }

         return 1.0 - $area_km2;

     }

    /**
     * @param array $address_components
     * @return Address
     */
    private function makeAddressFromAddressComponents(array $address_components){
        $address_array = [];

        foreach($address_components as $component){
            $types = array_intersect($component['types'], array_keys(self::MAP_GOOGLE_ADDRESS_COMPONENT_TO_ADDRESS));

            if(!empty($types)){
                if(empty($address_array[self::MAP_GOOGLE_ADDRESS_COMPONENT_TO_ADDRESS[$types[0]]])) {
                    $address_array[self::MAP_GOOGLE_ADDRESS_COMPONENT_TO_ADDRESS[$types[0]]] = $component['short_name'];
                }
            }
        }

        return new Address($address_array);
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
