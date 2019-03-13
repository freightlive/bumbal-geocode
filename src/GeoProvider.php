<?php

namespace BumbalGeocode;


interface GeoProvider {

    /**
     * @param Address $address
     * @return LatLonResult
     *
     * @throws \Exception
     */
    public function getLatLonFromAddress(Address $address);
}