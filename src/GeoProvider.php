<?php

namespace BumbalGeocode;


interface GeoProvider
{
    public function getLatLonFromAddress(Address $address);
}