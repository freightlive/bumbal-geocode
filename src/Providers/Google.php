<?php

namespace BumbalGeocode\Providers;

use BumbalGeocode\GeoProvider;
use BumbalGeocode\Model\Address;
use BumbalGeocode\Model\LatLngResult;
use BumbalGeocode\Model\LatLngResultList;
use BumbalGeocode\Model\GeoProviderOptions;

class Google implements GeoProvider
{
    const URL = 'https://maps.googleapis.com/maps/api/geocode/json?address={{address}}&key={{apikey}}';

    const GOOGLE_STATUS_ACCEPTED = [
        'ZERO_RESULTS',
        'OK'
    ];

    const GOOGLE_RESULT_TYPE_STREET_ADDRESS = 'street_address';
    const GOOGLE_RESULT_TYPE_ROUTE = 'route';
    const GOOGLE_RESULT_TYPE_LOCALITY = 'locality';
    const GOOGLE_RESULT_TYPE_SUBLOCALITY = 'sublocality';
    const GOOGLE_RESULT_TYPE_POSTAL_CODE = 'postal_code';

    /**
     * @todo tweak values
     */
    const VALID_GOOGLE_RESULT_TYPES = [
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

    const PROVIDER_NAME = 'google_maps';

    private $api_key;
    private $options;

    /**
     * Google constructor.
     * @param string $api_key
     * @param GeoProviderOptions $options
     */
    public function __construct(string $api_key, GeoProviderOptions $options = NULL) {
        $this->api_key = $api_key;
        $this->options = ($options ? $options : new GeoProviderOptions());
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
                $single_result = $this->analyseResult($single_google_result);
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
    private function analyseResult(array $data){
        $result = new LatLngResult();
        $result->setProviderName(self::PROVIDER_NAME);
        $result->setLatitude($data['geometry']['location']['lat']);
        $result->setLongitude($data['geometry']['location']['lng']);


        /**
         * @todo tweak code below to find acceptable precision values for use within Bumbal
         */
        $google_result_types = $data['types'];
        $google_location_type = $data['geometry']['location_type'];
        $valid_result_types = array_intersect(array_keys(self::VALID_GOOGLE_RESULT_TYPES), $google_result_types);
        if(empty($valid_result_types)){
            $result->setPrecision(0.0);
        } else {

            $precision_multiplier = max(array_intersect_key(self::VALID_GOOGLE_RESULT_TYPES, array_flip($valid_result_types)));
            switch($google_location_type){
                case self::GOOGLE_LOCATION_TYPE_ROOFTOP:
                    $result->setPrecision(1.0 * $precision_multiplier);
                    break;
                case self::GOOGLE_LOCATION_TYPE_RANGE_INTERPOLATED:
                    $result->setPrecision(.75 * $precision_multiplier);
                    break;
                case self::GOOGLE_LOCATION_TYPE_GEOMETRIC_CENTER:
                    $result->setPrecision(.5 * $precision_multiplier);
                    break;
                case self::GOOGLE_LOCATION_TYPE_APPROXIMATE:
                    $result->setPrecision(.25 * $precision_multiplier);
                    break;
                default:
                    $result->setPrecision(0.0);
            }
        }
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
        curl_setopt($channel, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($channel);

        if (curl_errno($channel)) {
            throw new \Exception('Google maps API request failed. Curl returned error code ' . curl_errno($channel));
        }

        return json_decode($response, TRUE);
    }

}