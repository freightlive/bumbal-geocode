<?php

namespace BumbalGeocode\Providers;


use BumbalGeocode\GeoProvider;
use BumbalGeocode\Address;

class Google implements GeoProvider
{
    const URL = 'https://maps.googleapis.com/maps/api/geocode/json?address={{address}}&key={{apikey}}';
    private $api_key;

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    /**
     * @param Address $address
     * @throws \Exception
     */
    public function getLatLonFromAddress(Address $address){
        $address_string = $this->getAddressString($address);
        $result = $this->request($address_string);
        var_dump($result);
        die();
    }

    /**
     * @param string $address_string
     * @return array mixed
     * @throws \Exception
     */
    private function request(string $address_string){
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

        if(curl_errno($channel)) {
            throw new \Exception('Curl returned error code ' . curl_errno($channel));
        }

        return json_decode($response, TRUE);
    }


    private function getAddressString(Address $address){

        $address_data = json_decode(json_encode($address), true);
        $minimum_needed_fields = [
            ['iso_country'],
            ['city']
        ];

        $filtered_address_data = array_filter($address_data);

        $missing_fields = [];
        foreach($minimum_needed_fields as $fields) {
            if(!array_intersect($fields, array_keys($filtered_address_data))) {
                $missing_fields[] = implode(' or ', $fields);
            }
        }

        if(!empty($missing_fields)) {
            throw new \Exception('Missing fields in Address ' . implode(', ', $missing_fields));
        }

        $address_array = [
            [
                empty($address_data['street'])?'':$address_data['street'],
                empty($address_data['house_nr'])?'':$address_data['house_nr']
            ],
            [
                empty($address_data['zipcode'])?'':$address_data['zipcode'],
                empty($address_data['city'])?'':$address_data['city'],
                empty($address_data['iso_country'])?'':$address_data['iso_country']
            ],
        ];

        foreach($address_array as $key => $value) {
            $value = array_filter($value);
            $address_array[$key] = implode(' ',$value);
        }

        $address_array = array_filter($address_array);
        return implode(', ', $address_array);
    }
}