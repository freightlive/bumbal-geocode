<?php


namespace BumbalGeocode\Providers\GeoPuntBE;


use BumbalGeocode\GeoResponseAnalyser;
use BumbalGeocode\Model\Address;

class GeoPuntBEGeoResponseAnalyser extends GeoResponseAnalyser {

    const ISO_COUNTRY = 'BE';

    const GEOPUNT_RESULT_TYPE_STREET = 'Thoroughfarename';
    const GEOPUNT_RESULT_TYPE_HOUSE_NUMBER = 'Housenumber';
    const GEOPUNT_RESULT_TYPE_CITY = 'Municipality';
    const GEOPUNT_RESULT_TYPE_ZIPCODE = 'Zipcode';

    const MAP_GEOPUNT_ADDRESS_COMPONENT_TO_ADDRESS = [
        self::GEOPUNT_RESULT_TYPE_HOUSE_NUMBER => 'house_nr',
        self::GEOPUNT_RESULT_TYPE_STREET => 'street',
        self::GEOPUNT_RESULT_TYPE_ZIPCODE => 'zipcode',
        self::GEOPUNT_RESULT_TYPE_CITY => 'city'
    ];

    /**
     * GeoPuntBEGeoResponseAnalyser constructor.
     * @param array $weights
     */
    public function __construct(/*array*/ $weights = []) {
        parent::__construct($weights);
    }

    /**
     * @param array $geopunt_result
     * @param Address $address
     * @return float
     */
    protected function getValueAddressComponentsCompare(/*array*/ $geopunt_result, Address $address){
        $address_from_geopunt = $this->makeAddressFromAddressComponents($geopunt_result);

        return $address->compare($address_from_geopunt, ['city']);
    }

    /**
     * @param $data
     * @return string
     * @throws \Exception
     */
    public function getAddressStringFromResult(/*array*/ $data){
        return $this->makeAddressFromAddressComponents($data)->getAddressString();
    }

    /**
     * @param $data
     * @return Address
     */
    public function getAddressFromResult(/*array*/ $data){
        return $this->makeAddressFromAddressComponents($data);
    }

    /**
    * @param array $address_components
    * @return Address
    */
    private function makeAddressFromAddressComponents(/*array*/ $address_components){
        $address_array = [];

        foreach($address_components as $key => $value){

            if(!empty(self::MAP_GEOPUNT_ADDRESS_COMPONENT_TO_ADDRESS[$key])){
                $address_array[self::MAP_GEOPUNT_ADDRESS_COMPONENT_TO_ADDRESS[$key]] = $value;
            }
        }
        $address_array['iso_country'] = self::ISO_COUNTRY;
        return new Address($address_array);
    }
}