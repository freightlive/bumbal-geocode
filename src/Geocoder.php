<?php

namespace BumbalGeocode;


class Geocoder {

    protected $providers;

    public function __construct(GeoProviderList $providers){
        $this->providers = $providers;
    }

    public function getLatLngResultFromAddress(Address $address){
        foreach($this->providers as $provider){
            $result = $provider->getLatLngResultFromAddress($address);

        }
    }
}
