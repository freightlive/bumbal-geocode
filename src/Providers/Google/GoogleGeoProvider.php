<?php

namespace BumbalGeocode\Providers\Google;

use BumbalGeocode\GeoProvider;
use BumbalGeocode\GeoResponseAnalyser;
use BumbalGeocode\Model\Address;
use BumbalGeocode\Model\LatLngResult;
use BumbalGeocode\Model\LatLngResultList;
use BumbalGeocode\Model\GeoProviderOptions;
use BumbalGeocode\Model\ProviderResponseCache;
use BumbalGeocode\Model\Report;

class GoogleGeoProvider implements GeoProvider
{
    const URL = 'https://maps.googleapis.com/maps/api/geocode/json?address={{address}}&key={{apikey}}';

    const GOOGLE_STATUS_ACCEPTED = [
        'ZERO_RESULTS',
        'OK'
    ];

    const PROVIDER_NAME = 'google_maps';

    private $api_key;
    private $options;
    private $response_analyser;
    private $cache;
    private $report;

    /**
     * GoogleGeoProvider constructor.
     * @param $api_key
     * @param GeoProviderOptions|NULL $options
     * @param GeoResponseAnalyser|NULL $response_analyser
     * @param ProviderResponseCache|NULL $cache
     */
    public function __construct(/*string*/ $api_key, GeoProviderOptions $options = NULL, GeoResponseAnalyser $response_analyser = NULL, ProviderResponseCache $cache = NULL) {
        $this->api_key = $api_key;
        $this->options = ($options ? $options : new GeoProviderOptions());
        $this->response_analyser = ($response_analyser ? $response_analyser : new GoogleGeoResponseAnalyser());
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

        if($this->options->add_report) {
            $this->report = new Report();
            $this->report->address = $address;
            $this->report->provider_name = self::PROVIDER_NAME;
        }

        $address_string = '';

        try {
            $address_string = $address->getAddressString();

            if($this->options->log_debug){
                $result->setLogMessage('Google Maps API provider Geocoding invoked for address '.$address_string.' with accuracy '.$min_accuracy);
            }

            if($this->options->add_report) {
                $this->report->url = str_replace(['{{address}}', '{{apikey}}'], [urlencode($address_string), $this->api_key], self::URL);
            }

            $google_result = $this->request($address_string, $result);

            if($this->options->add_report) {
                $this->report->response = $google_result;
            }

            $this->validateResult($google_result);

            if($this->options->log_debug){
                $result->setLogMessage('Google Maps API found '.count($google_result['results']).' result(s) for address '.$address_string);
                $result->setLogMessage('Google Maps API result: '.json_encode($google_result['results']));
            }

            foreach($google_result['results'] as $single_google_result){
                $single_result = $this->analyseResult($single_google_result, $address);
                if($this->options->add_report) {
                    $this->report->addLatLngResult($single_result);
                }

                if($single_result->getAccuracy() >= $min_accuracy){
                    $result->setLatLngResult($single_result);
                }

            }

            if($this->options->log_debug){
                $result->setLogMessage('Google Maps provider kept '.count($result).' result(s) for address '.$address_string.' with accuracy '.$min_accuracy);
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
        if(empty($data['status']) || !in_array($data['status'], self::GOOGLE_STATUS_ACCEPTED)){
            throw new \Exception('Google maps API returned Status Code: '.$data['status']);
        }

        if(!empty($data['error_message'])){
            throw new \Exception('Google maps API returned Error Message: '.$data['error_message']);
        }
        return TRUE;
    }

    /**
     * @param array $data
     * @param Address $address
     * @return LatLngResult
     */
    private function analyseResult(/*array*/ $data, Address $address){

        $result = new LatLngResult();
        $result->setProviderName(self::PROVIDER_NAME);
        $result->setProviderId($data['place_id']);
        $result->setLatitude($data['geometry']['location']['lat']);
        $result->setLongitude($data['geometry']['location']['lng']);
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
     * @return array|mixed
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

        curl_setopt($channel,CURLOPT_URL, $url);
        curl_setopt($channel,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($channel,CURLOPT_HEADER, false);
        curl_setopt($channel,CURLOPT_POST, false);
        curl_setopt($channel,CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($channel,CURLOPT_SSL_VERIFYHOST, 2);
        $response = curl_exec($channel);

        if (curl_error($channel)) {
            throw new \Exception('Google maps API request failed. Curl returned error ' . curl_error($channel));
        }

        $response_array = json_decode($response, TRUE);
        if($this->cache) {
            $this->cache->setProviderResponse($url, $response_array);
        }
        return $response_array;
    }
}
