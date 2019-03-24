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
        self::GOOGLE_RESULT_TYPE_COUNTRY => 'iso_country'
    ];

    /**
     * GoogleGeoValueAnalyser constructor.
     * @param array $weights
     */
    public function __construct(array $weights = []) {
        parent::__construct($weights);
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
        if(isset(self::VALUE_GOOGLE_LOCATION_TYPES[$google_result['geometry']['location_type']])){
            return self::VALUE_GOOGLE_LOCATION_TYPES[$google_result['geometry']['location_type']];
        }
        return 0.0;
    }

    /**
     * @param array $google_result
     * @param Address $address
     * @return float
     */
    protected function getValueAddressComponentsEquals(array $google_result, Address $address){
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
         $distance_meters = $this->haversineGreatCircleDistance($bounds['northeast']['lat'], $bounds['northeast']['lng'], $bounds['southwest']['lat'], $bounds['southwest']['lng']);

         //1000 meters is too much, 0 meters is perfect
         if($distance_meters > 1000.0){
             return 0.0;
         }

         return 1.0 - $distance_meters / 1000.0;

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
                $address_array[self::MAP_GOOGLE_ADDRESS_COMPONENT_TO_ADDRESS[$types[0]]] = $component['short_name'];
            }
        }
        return new Address($address_array);
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