<?php

namespace BumbalGeocode\Providers\OSMGraphHopper;

use BumbalGeocode\GeoProvider;
use BumbalGeocode\Model\Address;
use BumbalGeocode\Model\LatLngResult;
use BumbalGeocode\Model\LatLngResultList;
use BumbalGeocode\Model\GeoProviderOptions;


class OSMGraphHopperGeoProvider implements GeoProvider {
    const URL = 'https://graphhopper.com/api/1/geocode?q={{address}}&locale=en&debug=true&key={{apikey}}';

    const PROVIDER_NAME = 'graphhopper_osm';

    private $api_key;
    private $options;
    private $response_analyser;

    /**
     * OSMGraphHopperGeoProvider constructor.
     * @param string $api_key
     * @param GeoProviderOptions|NULL $options
     * @param OSMGraphHopperGeoResponseAnalyser $response_analyser
     * @throws \Exception
     */
    public function __construct(string $api_key, GeoProviderOptions $options = NULL, OSMGraphHopperGeoResponseAnalyser $response_analyser = NULL) {
        $this->api_key = $api_key;
        $this->options = ($options ? $options : new GeoProviderOptions());
        $this->response_analyser = ($response_analyser ? $response_analyser : new OSMGraphHopperGeoResponseAnalyser());
    }

    /**
     * @param Address $address
     * @param float $precision
     * @return LatLngResultList
     */
    public function getLatLngResultListFromAddress(Address $address, float $precision){
        $result = new LatLngResultList();
        $address_string = '';

        try {
            $address_string = $address->getAddressString();

            if($this->options->log_debug){
                $result->setLogMessage('GraphHopper OSM provider Geocoding invoked for address '.$address_string.' with precision '.$precision);
            }

            $graphhopper_result = $this->request($address_string);
            $this->validateResult($graphhopper_result);

            if($this->options->log_debug){
                $result->setLogMessage('GraphHopper OSM API found '.count($graphhopper_result['hits']).' result(s) for address '.$address_string);
                $result->setLogMessage('GraphHopper OSM result: '.json_encode($graphhopper_result['hits']));
            }

            foreach($graphhopper_result['hits'] as $single_graphhopper_result) {
                $single_result = $this->analyseResult($single_graphhopper_result, $address);
                if($single_result->getPrecision() >= $precision){
                    $result->setLatLngResult($single_result);
                }
            }

            if($this->options->log_debug){
                $result->setLogMessage('GraphHopper OSM provider kept '.count($result).' result(s) for address '.$address_string.' with precision '.$precision);
            }

        } catch(\Exception $e){
            if($this->options->log_errors) {
                $result->setError($e->getMessage() .(!empty($address_string) ? " ($address_string)" : ''));
            }

            if($this->options->log_debug){
                $result->setLogMessage('Error: '.$e->getMessage() .(!empty($address_string) ? " ($address_string)" : ''));
            }
        }

        return $result;
    }

    /**
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    private function validateResult(array $data){
        /*if(empty($data['hits'])){
            throw new \Exception('GraphHopper API did not return a result');
        }*/
        return TRUE;
    }

    /**
     * @param array $data
     * @return LatLngResult
     * @throws \Exception
     */
    private function analyseResult(array $data, Address $address){

        $result = new LatLngResult();
        $result->setProviderName(self::PROVIDER_NAME);
        $result->setLatitude($data['point']['lat']);
        $result->setLongitude($data['point']['lng']);
        $result->setPrecision($this->response_analyser->getValue($data, $address));

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
        curl_setopt($channel, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($channel, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($channel);

        if (curl_errno($channel)) {
            throw new \Exception('GraphHopper API request failed. Curl returned error code ' . curl_errno($channel));
        }

        return json_decode($response, TRUE);
    }
}