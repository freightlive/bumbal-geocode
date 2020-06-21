<?php

namespace BumbalGeocode\Model;


class GeoProviderOptions {
    /**
     * @var bool
     */
    public $log_errors = TRUE;

    /**
     * @var bool
     */
    public $log_debug = FALSE;


    /**
     * adds a description to each LatLngResult
     * @var bool
     */
    public $add_description = FALSE;

    /**
     * adds an address object to each LatLngResult
     * @var bool
     */
    public $add_address = FALSE;

    /**
     * adds a ProviderReport object to a provider's LatLngResultList
     * @var bool
     */
    public $add_report = FALSE;

    public function __construct(/*array*/ $options = []) {
        foreach($options as $key => $value){
            if(property_exists($this, $key)){
                $this->$key = $value;
            }
        }
    }

}
