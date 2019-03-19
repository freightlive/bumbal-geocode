<?php

namespace BumbalGeocode;

use BumbalGeocode\Model\LatLngResultList;
use BumbalGeocode\Model\LatLngResult;
use BumbalGeocode\Model\Address;
use BumbalGeocode\Model\GeocoderOptions;

class Geocoder {

    protected $providers;

    protected $options;

    /**
     * Geocoder constructor.
     * @param GeoProviderList $providers
     * @param GeocoderOptions $options
     */
    public function __construct(GeoProviderList $providers, GeocoderOptions $options = NULL){
        $this->providers = $providers;
        $this->options = ($options ? $options : new GeocoderOptions());
    }

    /**
     * @param Address $address
     * @param float $precision
     * @return LatLngResultList
     */
    public function getLatLngResultListFromAddress(Address $address, float $precision){
        $result = new LatLngResultList();
        foreach($this->providers as $provider){
            $provider_result = $provider->getLatLngResultListFromAddress($address, $precision, $this->options);
            if($this->options->quit_on_error && $provider_result->hasErrors()){
                return $provider_result;
            }

            if($this->options->quit_after_first_result && count($provider_result) > 0){
                return $provider_result;
            } else {
                $result->merge($provider_result);
            }
        }

        return $result;
    }
}
