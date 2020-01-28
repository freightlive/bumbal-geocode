<?php

namespace BumbalGeocode\Providers\Google;

use BumbalGeocode\GeoProvider;
use BumbalGeocode\GeoReverseProvider;
use BumbalGeocode\GeoResponseAnalyser;
use BumbalGeocode\Model\Address;
use BumbalGeocode\Model\AddressResultList;
use BumbalGeocode\Model\LatLngResult;
use BumbalGeocode\Model\LatLngResultList;
use BumbalGeocode\Model\GeoProviderOptions;

class GoogleGeoProvider implements GeoProvider, GeoReverseProvider {

    const URL = 'https://maps.googleapis.com/maps/api/geocode/json?address={{address}}&key={{apikey}}';
    const REVERSE_URL = 'https://maps.googleapis.com/maps/api/geocode/json?latlng={{latlng}}&location_type=ROOFTOP&result_type=street_address&key={{apikey}}';



    const PROVIDER_NAME = 'google_maps';


    private $options;
    private $response_analyser;

    /**
     * GoogleGeoProvider constructor.
     * @param string $api_key
     * @param GeoProviderOptions|NULL $options
     * @param GeoResponseAnalyser|NULL $response_analyser
     */
    public function __construct(/*string*/ $api_key, GeoProviderOptions $options = NULL, GeoResponseAnalyser $response_analyser = NULL) {
        $this->api_key = $api_key;
        $this->options = ($options ? $options : new GeoProviderOptions());
        $this->response_analyser = ($response_analyser ? $response_analyser : new GoogleGeoResponseAnalyser());
    }

    /**
     * @param $latitude
     * @param $longitude
     * @return AddressResultList
     */
    public function getAddressResultListFromLatLng(/*float*/ $latitude, /*float*/ $longitude) {
        $result = new AddressResultList();

        try {
            $latlng_string = $latitude.','.$longitude;

            if($this->options->log_debug){
                $result->setLogMessage('Google Maps API provider Reverse Geocoding invoked for latitude,longitude '.$latlng_string);
            }

            $url = str_replace(['{{latlng}}', '{{apikey}}'], [urlencode($latlng_string), $this->api_key], self::REVERSE_URL);
            $google_result = $this->request($url);

            $this->validateResult($google_result);

            var_dump($google_result);
            die();
            if($this->options->log_debug){
                $result->setLogMessage('Google Maps API found '.count($google_result['results']).' result(s) for latitude,longitude '.$latlng_string);
                $result->setLogMessage('Google Maps API result: '.json_encode($google_result['results']));
            }

            foreach($google_result['results'] as $single_google_result){
                $result->setAddress($single_result);
            }

            if($this->options->log_debug){
                $result->setLogMessage('Google Maps provider kept '.count($result).' result(s) for address '.$address_string.' with accuracy '.$accuracy);
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
     * @param Address $address
     * @param float $accuracy
     * @return LatLngResultList
     */
    public function getLatLngResultListFromAddress(Address $address, /*float*/ $accuracy){
        $result = new LatLngResultList();
        $address_string = '';

        try {
            $address_string = $address->getAddressString();

            if($this->options->log_debug){
                $result->setLogMessage('Google Maps API provider Geocoding invoked for address '.$address_string.' with accuracy '.$accuracy);
            }

            $url = str_replace(['{{address}}', '{{apikey}}'], [urlencode($address_string), $this->api_key], self::URL);
            $google_result = $this->request($url);

            $this->validateResult($google_result);

            if($this->options->log_debug){
                $result->setLogMessage('Google Maps API found '.count($google_result['results']).' result(s) for address '.$address_string);
                $result->setLogMessage('Google Maps API result: '.json_encode($google_result['results']));
            }

            foreach($google_result['results'] as $single_google_result){
                $single_result = $this->analyseResult($single_google_result, $address);
                if($single_result->getAccuracy() >= $accuracy){
                    $result->setLatLngResult($single_result);
                }

            }

            if($this->options->log_debug){
                $result->setLogMessage('Google Maps provider kept '.count($result).' result(s) for address '.$address_string.' with accuracy '.$accuracy);
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
     * @param Address $address
     * @return LatLngResult
     * @throws \Exception
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



    public function useForAddress(Address $address){
        return true;
    }

}
