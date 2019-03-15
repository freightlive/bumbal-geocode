<?php

namespace BumbalGeocode;


class Geocoder {

    protected $providers;

    public function __construct(GeoProviderList $providers){
        $this->providers = $providers;
    }

    public function getLatLngResultFromAddress(Address $address, float $precision){
        foreach($this->providers as $provider){
            $result = $provider->getLatLngResultFromAddress($address);
            if($result->isValid() && $result->getPrecision() >= $precision){
                return $result;
            }
        }

        return FALSE;
    }
}
