<?php


namespace BumbalGeocode\Model;


class Report {
    public $provider_name;

    public $address;

    public $url;

    public $response;

    public $latlngresults = [];

    public function addLatLngResult(LatLngResult $result) {
        $this->latlngresults[] = $result;
    }
}