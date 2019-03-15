<?php

namespace BumbalGeocode\Providers;

use BumbalGeocode\GeoProvider;
use BumbalGeocode\Address;
use BumbalGeocode\LatLngResult;

class Google implements GeoProvider
{
    const URL = 'https://maps.googleapis.com/maps/api/geocode/json?address={{address}}&key={{apikey}}';
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
            $google_result = $this->request($address_string);
            $this->validateResult($google_result);
            $result = $this->analyseResult($google_result);

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
        if(empty($data['status']) || $data['status'] != "OK"){
            throw new \Exception('Google maps API returned Status Code: '.$data['status']);
        }

        if(empty($data['results'][0])){
            throw new \Exception('Google maps API returned no results');
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

        if(empty($data['results'][0]['geometry']['location'])) {
            $error_message = empty($data['error_message']) ?  'Unknown' : $data['error_message'];
            throw new \Exception('Google maps API returned no locations due to: [' . $error_message . ']');
        }

        $result->setLatitude($data['results'][0]['geometry']['location']['lat']);
        $result->setLongitude($data['results'][0]['geometry']['location']['lng']);


        /**
         * @todo tweak code below to find acceptable precision values for use within Bumbal
         */
        $google_result_types = $data['results'][0]['types'];
        $google_location_type = $data['results'][0]['geometry']['location_type'];
        $valid_result_types = array_intersect(array_keys(self::VALID_GOOGLE_RESULT_TYPES), $google_result_types);
        if(empty($valid_result_types)){
            throw new \Exception("Google maps API didn't return a valid result type (".implode(',', $google_result_types).")");
        } else {
            $result->setValid(TRUE);

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