<?php


namespace BumbalGeocode\Util;

use \Countable;
use \Iterator;

class PriorityList implements Iterator, Countable {
    /**
     * 2D array. Keys are priorities, values are arrays of GeoProviders
     * @var array
     */
    private $priorities = [];

    /**
     * Array of items. Order is priority order
     * @var array
     */
    protected $container = [];

    public function __construct(/*array*/ $items = []) {
        $this->container = $items;
        foreach($items as $priority => $item){
            $this->priorities[$priority] = [$item];
        }
    }

    /**
     * priority 0 is highest.
     * @param mixed $item
     * @param int $priority
     */
    protected function setItem($item, /*int*/ $priority = 0){
        //make priority a value equal/greater than 0
        $priority = max(0, $priority);
        if(empty($this->priorities[$priority])){
            $this->priorities[$priority] = [
                $item
            ];
        } else {
            array_unshift($this->priorities[$priority], $item);
        }

        ksort($this->priorities);
        $this->container = call_user_func_array('array_merge', $this->priorities);
    }

    /**
     * @param int $index
     * @return mixed|NULL
     */
    protected function getItem(/*int*/ $index){
        if(!empty($this->container[$index])){
            return $this->container[$index];
        }
        return NULL;
    }

    /**
     * @param int|NULL $priority
     * @return array|mixed
     */
    protected function getItems(/*int*/ $priority = NULL){
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