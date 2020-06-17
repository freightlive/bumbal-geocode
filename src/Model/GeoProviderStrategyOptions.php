<?php


namespace BumbalGeocode\Model;


class GeoProviderStrategyOptions {
    /**
     * @var bool
     */
    public $quit_on_error = FALSE;

    /**
     * @var bool
     */
    public $quit_after_first_result = TRUE;

    /**
     * @var float
     */
    public $accuracy_threshold = 0.0;

    public function __construct(/*array*/ $options = []) {
        foreach($options as $key => $value){
            if(property_exists($this, $key)){
                $this->$key = $value;
            }
        }
    }
}