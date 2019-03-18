<?php

namespace BumbalGeocode;

use \Countable;
use \Iterator;

class GeoProviderList implements Iterator, Countable {

    /**
     * 2D array. Keys are priorities, values are arrays of GeoProviders
     * @var array
     */
    private $priorities = [];

    /**
     * Array of GeoProviders. Order is priority order
     * @var array
     */
    protected $container = [];

    public function __construct(array $providers = []) {
        $this->container = $providers;
        foreach($providers as $priority => $provider){
            $this->priorities[$priority] = [$provider];
        }
    }

    /**
     * priority 0 is highest.
     * @param GeoProvider $provider
     * @param int $priority
     */
    public function setProvider(GeoProvider $provider, int $priority = 0){
        //make priority a value equal/greater than 0
        $priority = max(0, $priority);
        if(empty($this->priorities[$priority])){
            $this->priorities[$priority] = [
                $provider
            ];
        } else {
            array_unshift($this->priorities[$priority], $provider);
        }

        ksort($this->priorities);
        $this->container = call_user_func_array('array_merge', $this->priorities);
    }

    /**
     * @param int $index
     * @return GeoProvider|null
     */
    public function getProvider(int $index){
        if(!empty($this->container[$index])){
            return $this->container[$index];
        }
        return NULL;
    }

    /**
     * @param int|NULL $priority
     * @return array|mixed
     */
    public function getProviders(int $priority = NULL){
        if($priority === NULL) {
            return $this->container;
        } else {
            return (!empty($this->priorities[$priority]) ? $this->priorities[$priority] : []);
        }
    }

    function count(){
        return count($this->container);
    }

    function rewind() {
        return reset($this->container);
    }

    function current() {
        return current($this->container);
    }

    function key() {
        return key($this->container);
    }

    function next() {
        return next($this->container);
    }

    function valid() {
        return key($this->container) !== null;
    }
}