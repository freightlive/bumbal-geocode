<?php

namespace BumbalGeocode;


class Geocoder {

    protected $providers;

    /**
     * Geocoder constructor.
     * @param GeoProviderList $providers
     */
    public function __construct(GeoProviderList $providers){
        $this->providers = $providers;
    }

    /**
     * @param Address $address
     * @param float $precision
     * @return bool|LatLngResult
     */
    public function getLatLngResultFromAddress(Address $address, float $precision){
        foreach($this->providers as $provider){
            $result = $provider->getLatLngResultFromAddress($address);
            if($result->getPrecision() >= $precision){
                return $result;
            }
        }

        return FALSE;
    }
}
