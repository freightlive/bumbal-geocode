<?php

namespace BumbalGeocode;


class GeoProviderList implements \Iterator {

    protected $container;

    public function __construct(array $providers) {
        $this->container = $providers;
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