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
        $this->orderLatLngResults();
    }

    public function getBestResult() {
        if(!empty($this->latlngresults[0])) {
            return $this->latlngresults[0];
        }
        return null;
    }

    /**
     * Order results based on accuracy
     */
    private function orderLatLngResults(){
        usort($this->latlngresults, function(LatLngResult $a, LatLngResult $b){
            $a_accuracy = $a->getAccuracy();
            $b_accuracy = $b->getAccuracy();
            if($a_accuracy == $b_accuracy){
                return 0;
            }
            return ($a_accuracy < $b_accuracy) ? 1 : -1;
        });
    }
}