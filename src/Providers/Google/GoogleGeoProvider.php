<?php

namespace BumbalGeocode\Providers\Google;

use BumbalGeocode\GeoProvider;
use BumbalGeocode\Model\Address;
use BumbalGeocode\Model\LatLngResult;
use BumbalGeocode\Model\LatLngResultList;
use BumbalGeocode\Model\GeoProviderOptions;

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

    /**
     * GoogleGeoProvider constructor.
     * @param string $api_key
     * @param GeoProviderOptions|NULL $options
     * @param GoogleGeoResponseAnalyser|NULL $response_analyser
     * @throws \Exception
     */
    public function __construct(string $api_key, GeoProviderOptions $options = NULL, GoogleGeoResponseAnalyser $response_analyser = NULL) {
        $this->api_key = $api_key;
        $this->options = ($options ? $options : new GeoProviderOptions());
        $this->response_analyser = ($response_analyser ? $response_analyser : new GoogleGeoResponseAnalyser());
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
                $result->setLogMessage('Google Maps API provider Geocoding invoked for address '.$address_string.' with precision '.$precision);
            }

            $google_result = $this->request($address_string);

            $this->validateResult($google_result);

            if($this->options->log_debug){
                $result->setLogMessage('Google Maps API found '.count($google_result['results']).' result(s) for address '.$address_string);
                $result->setLogMessage('Google Maps API result: '.json_encode($google_result['results']));
            }

            foreach($google_result['results'] as $single_google_result){
                $single_result = $this->analyseResult($single_google_result, $address);
                if($single_result->getPrecision() >= $precision){
                    $result->setLatLngResult($single_result);
                }

            }

            if($this->options->log_debug){
                $result->setLogMessage('Google Maps provider kept '.count($result).' result(s) for address '.$address_string.' with precision '.$precision);
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
     * @return LatLngResult
     * @throws \Exception
     */
    private function analyseResult(array $data, Address $address){
        $result = new LatLngResult();
        $result->setProviderName(self::PROVIDER_NAME);
        $result->setLatitude($data['geometry']['location']['lat']);
        $result->setLongitude($data['geometry']['location']['lng']);
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

        return json_decode($response, TRUE);
    }

}