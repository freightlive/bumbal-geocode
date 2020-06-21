<?php

namespace BumbalGeocode\Providers\OSMGraphHopper;

use BumbalGeocode\GeoProvider;
use BumbalGeocode\GeoResponseAnalyser;
use BumbalGeocode\Model\Address;
use BumbalGeocode\Model\LatLngResult;
use BumbalGeocode\Model\LatLngResultList;
use BumbalGeocode\Model\GeoProviderOptions;
use BumbalGeocode\Model\ProviderResponseCache;
use BumbalGeocode\Model\Report;


class OSMGraphHopperGeoProvider implements GeoProvider {
    const URL = 'https://graphhopper.com/api/1/geocode?q={{address}}&locale=en&debug=true&key={{apikey}}';

    const PROVIDER_NAME = 'graphhopper_osm';

    private $api_key;
    private $options;
    private $response_analyser;

    private $cache;
    private $report;

    /**
     * OSMGraphHopperGeoProvider constructor.
     * @param $api_key
     * @param GeoProviderOptions|NULL $options
     * @param GeoResponseAnalyser|NULL $response_analyser
     * @param ProviderResponseCache|NULL $cache
     */
    public function __construct(/*string*/ $api_key, GeoProviderOptions $options = NULL, GeoResponseAnalyser $response_analyser = NULL, ProviderResponseCache $cache = NULL) {
        $this->api_key = $api_key;
        $this->options = ($options ? $options : new GeoProviderOptions());
        $this->response_analyser = ($response_analyser ? $response_analyser : new OSMGraphHopperGeoResponseAnalyser());
        $this->cache = $cache;
        $this->report = null;
    }

    /**
     * @param Address $address
     * @param float $min_accuracy
     * @return LatLngResultList
     */
    public function getLatLngResultListForAddress(Address $address, /*float*/ $min_accuracy){
        $result = new LatLngResultList();
        $address_string = '';

        if($this->options->add_report) {
            $this->report = new Report();
            $this->report->address = $address;
            $this->report->provider_name = self::PROVIDER_NAME;
        }

        try {
            $address_string = $address->getAddressString();

            if($this->options->log_debug){
                $result->setLogMessage('GraphHopper OSM provider Geocoding invoked for address '.$address_string.' with accuracy '.$min_accuracy);
            }

            if($this->options->add_report) {
                $this->report->url = str_replace('{{address}}', urlencode($address_string), self::URL);
            }

            $graphhopper_result = $this->request($address_string, $result);

            if($this->options->add_report) {
                $this->report->response = $graphhopper_result;
            }

            $this->validateResult($graphhopper_result);

            if($this->options->log_debug){
                $result->setLogMessage('GraphHopper OSM API found '.count($graphhopper_result['hits']).' result(s) for address '.$address_string);
                $result->setLogMessage('GraphHopper OSM result: '.json_encode($graphhopper_result['hits']));
            }

            foreach($graphhopper_result['hits'] as $single_graphhopper_result) {
                $single_result = $this->analyseResult($single_graphhopper_result, $address);

                if($this->options->add_report) {
                    $this->report->addLatLngResult($single_result);
                }

                if($single_result->getAccuracy() >= $min_accuracy){
                    $result->setLatLngResult($single_result);
                }
            }

            if($this->options->log_debug){
                $result->setLogMessage('GraphHopper OSM provider kept '.count($result).' result(s) for address '.$address_string.' with accuracy '.$min_accuracy);
            }

        } catch(\Exception $e){
            if($this->options->log_errors) {
                $result->setError($e->getMessage() .(!empty($address_string) ? " ($address_string)" : ''));
            }

            if($this->options->log_debug){
                $result->setLogMessage('Error: '.$e->getMessage() .(!empty($address_string) ? " ($address_string)" : ''));
            }
        }

        if($this->options->add_report) {
            $result->setReport($this->report);
        }

        return $result;
    }

    /**
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    private function validateResult(/*array*/ $data){
        if($data['code'] != 200){
            if(!empty($data['message'])) {
                throw new \Exception('GraphHopper OSM '.$data['message']);
            } else {
                throw new \Exception('GraphHopper OSM returned HTTP code '.$data['code']);
            }
        }
        return TRUE;
    }

    /**
     * @param array $data
     * @return LatLngResult
     * @throws \Exception
     */
    private function analyseResult(/*array*/ $data, Address $address){

        $result = new LatLngResult();
        $result->setProviderName(self::PROVIDER_NAME);
        $result->setProviderId((string)$data['osm_id']);
        $result->setLatitude($data['point']['lat']);
        $result->setLongitude($data['point']['lng']);
        $result->setAccuracy($this->response_analyser->getValue($data, $address));
        if($this->options->add_description) {
            $result->setDescription($this->response_analyser->getAddressStringFromResult($data));
        }
        if($this->options->add_address) {
            $result->setAddress($this->response_analyser->getAddressFromResult($data));
        }

        return $result;
    }

    /**
     * @param string $address_string
     * @param LatLngResultList $result
     * @return array mixed
     * @throws \Exception
     */
    private function request(/*string*/ $address_string, LatLngResultList $result) {
        $url = str_replace(['{{address}}', '{{apikey}}'], [urlencode($address_string), $this->api_key], self::URL);

        if($this->options->log_debug) {
            $result->setLogMessage('Google Maps url requested: '.$url);
        }

        if($this->cache && $this->cache->hasProviderResponse($url)) {
            if($this->options->log_debug) {
                $result->setLogMessage('Google Maps response found in cache: ' . $url);
            }

            return $this->cache->getProviderResponse($url);
        }
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
        $httpcode = curl_getinfo($channel, CURLINFO_HTTP_CODE);

        $response_obj = json_decode($response, TRUE);
        $response_obj['code'] = $httpcode;

        if($this->cache) {
            $this->cache->setProviderResponse($url, $response_obj);
        }

        return $response_obj;
    }
}
