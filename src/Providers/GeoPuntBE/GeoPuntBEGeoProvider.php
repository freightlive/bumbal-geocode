<?php


namespace BumbalGeocode\Providers\GeoPuntBE;

use BumbalGeocode\GeoProvider;
use BumbalGeocode\GeoResponseAnalyser;
use BumbalGeocode\Model\GeoProviderOptions;
use BumbalGeocode\Model\ProviderResponseCache;
use BumbalGeocode\Model\Address;
use BumbalGeocode\Model\LatLngResult;
use BumbalGeocode\Model\LatLngResultList;
use BumbalGeocode\Model\Report;


class GeoPuntBEGeoProvider implements GeoProvider {

    const URL = 'https://loc.geopunt.be/geolocation/location?q={{address}}';
    const PROVIDER_NAME = 'geopunt_be';

    private $options;
    private $response_analyser;
    private $cache;
    private $report;

    /**
     * GeoPuntBEGeoProvider constructor.
     * @param GeoProviderOptions|NULL $options
     * @param GeoResponseAnalyser|NULL $response_analyser
     * @param ProviderResponseCache|NULL $cache
     */
    public function __construct(GeoProviderOptions $options = NULL, GeoResponseAnalyser $response_analyser = NULL, ProviderResponseCache $cache = NULL) {
        $this->options = ($options ? $options : new GeoProviderOptions());
        $this->response_analyser = ($response_analyser ? $response_analyser : new GeoPuntBEGeoResponseAnalyser());
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
            $address_string = $address->getAddressString(true);

            if($this->options->log_debug){
                $result->setLogMessage('GeoPuntBE API provider Geocoding invoked for address '.$address_string.' with accuracy '.$min_accuracy);
            }

            if($this->options->add_report) {
                $this->report->url = str_replace('{{address}}', urlencode($address_string), self::URL);
            }

            $geo_punt_be_result = $this->request($address_string, $result);

            if($this->options->add_report) {
                $this->report->response = $geo_punt_be_result;
            }
            $this->validateResult($geo_punt_be_result);

            if($this->options->log_debug){
                $result->setLogMessage('GeoPuntBE API found '.count($geo_punt_be_result['LocationResult']).' result(s) for address '.$address_string);
                $result->setLogMessage('GeoPuntBE API result: '.json_encode($geo_punt_be_result['LocationResult']));
            }

            foreach($geo_punt_be_result['LocationResult'] as $single_geo_punt_be_result){
                $single_result = $this->analyseResult($single_geo_punt_be_result, $address);

                if($this->options->add_report) {
                    $this->report->addLatLngResult($single_result);
                }

                if($single_result->getAccuracy() >= $min_accuracy){
                    $result->setLatLngResult($single_result);
                }
            }

            if($this->options->log_debug){
                $result->setLogMessage('GeoPuntBE provider kept '.count($result).' result(s) for address '.$address_string.' with accuracy '.$min_accuracy);
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
        $result->setProviderId($data['ID']);
        $result->setLatitude($data['Location']['Lat_WGS84']);
        $result->setLongitude($data['Location']['Lon_WGS84']);
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
        $url = str_replace('{{address}}', urlencode($address_string), self::URL);

        if($this->options->log_debug) {
            $result->setLogMessage('GeoPuntBE url requested: '.$url);
        }

        if($this->cache && $this->cache->hasProviderResponse($url)) {
            if($this->options->log_debug) {
                $result->setLogMessage('GeoPuntBE response found in cache: ' . $url);
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
            throw new \Exception('GeoPuntBE API request failed. Curl returned error ' . curl_error($channel));
        }

        $response_array = json_decode($response, TRUE);
        if($this->cache) {
            $this->cache->setProviderResponse($url, $response_array);
        }
        return $response_array;
    }
}