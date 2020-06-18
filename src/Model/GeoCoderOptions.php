<?php

namespace BumbalGeocode\Model;


class GeoCoderOptions {

    /**
     * @var bool
     */
    public $diagnose = FALSE;

    public function __construct(/*array*/ $options = []) {
        foreach($options as $key => $value){
            if(property_exists($this, $key)){
                $this->$key = $value;
            }
        }
    }

}
