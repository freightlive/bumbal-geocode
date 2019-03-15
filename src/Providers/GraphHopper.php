<?php

namespace BumbalGeocode\Providers;

use BumbalGeocode\GeoProvider;
use BumbalGeocode\Address;
use BumbalGeocode\LatLngResult;


class GraphHopper implements GeoProvider {
    const URL = 'https://graphhopper.com/api/1/geocode?q={{address}}&locale=en&debug=true&key={{apikey}}';

    const OSM_KEY_PLACE = 'place';
    const OSM_VALUE_HOUSE = 'house';


    const PROVIDER_NAME = 'graphhopper_osm';

    private $api_key;

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    /**
     * @param Address $address
     * @return LatLngResult
     */
    public function getLatLngResultFromAddress(Address $address){
        $result = null;
        $address_string = '';

        try {
            $address_string = $address->getAddressString();
            $graphhopper_result = $this->request($address_string);
            $this->validateResult($graphhopper_result);
            $result = $this->analyseResult($graphhopper_result);

        } catch(\Exception $e){
            $result = new LatLngResult(
                [
                    'provider_name' => self::PROVIDER_NAME,
                    'latitude' => null,
                    'longitude' => null,
                    'precision' => 0.0,
                    'valid' => FALSE,
                    'error_message' => $e->getMessage()." ($address_string)"
                ]
            );
        }

        return $result;
    }

    /**
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    private function validateResult(array $data){
        if(empty($data['hits'][0]['point'])){
            throw new \Exception('GraphHopper API did not return a result');
        }
        return TRUE;
    }

    /**
     * @param array $data
     * @return LatLngResult
     * @throws \Exception
     */
    private function analyseResult(array $data){

        if($data['hits'][0]['osm_key'] != self::OSM_KEY_PLACE || $data['hits'][0]['osm_value'] != self::OSM_VALUE_HOUSE){
            throw new \Exception('GraphHopper API did not return a street address');
        }
        $result = new LatLngResult();
        $result->setProviderName(self::PROVIDER_NAME);
        $result->setLatitude($data['hits'][0]['point']['lat']);
        $result->setLongitude($data['hits'][0]['point']['lng']);
        $result->setPrecision(1.0);
        $result->setValid(TRUE);
        return $result;
    }

    /**
     * @param string $address_string
     * @return array mixed
     * @throws \Exception
     */
    private function request(string $address_string) {
        $url = str_replace(['{{address}}', '{{apikey}}'], [urlencode($address_string), $this->api_key], self::URL);

        $channel = curl_init();

        curl_setopt($channel, CURLOPT_URL, $url);
        curl_setopt($channel, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($channel, CURLOPT_HEADER, false);
        curl_setopt($channel, CURLOPT_POST, false);
        //curl_setopt($channel, CURLOPT_REFERER, 'http://'.$this->getService('\FreightLive\Configuration')->getParam('domain').'.freightlive.eu');
        curl_setopt($channel, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($channel, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($channel);

        if (curl_errno($channel)) {
            throw new \Exception('Curl returned error code ' . curl_errno($channel));
        }

        return json_decode($response, TRUE);
    }
}