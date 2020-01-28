<?php


namespace BumbalGeocode\Model;

use \Countable;
use \Iterator;
use \ArrayAccess;

class ResultList implements Iterator, Countable, ArrayAccess {
    /**
     * @var array
     */
    protected $log = [];

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @return array
     */
    public function getLog(){
        return $this->log;
    }

    /**
     * @param string $message
     */
    public function setLogMessage(/*string*/ $message){
        $this->log[] = $message;
    }

    /**
     * @param array $messages
     */
    public function setLog(/*array*/ $messages){
        $this->log = $messages;
    }

    /**
     * @return array
     */
    public function getErrors(){
        return $this->errors;
    }

    /**
     * @param string $message
     */
    public function setError(/*string*/ $message){
        $this->errors[] = $message;
    }

    /**
     * @param array $messages
     */
    public function setErrors(/*array*/ $messages){
        $this->errors = $messages;
    }

    /**
     * @return bool
     */
    public function hasErrors(){
        return count($this->errors) > 0;
    }

    function count(){
        return count($this->items);
    }

    function rewind() {
        return reset($this->items);
    }

    function current() {
        return current($this->items);
    }

    function key() {
        return key($this->items);
    }

    function next() {
        return next($this->items);
    }

    function valid() {
        return key($this->items) !== null;
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->items[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->items[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->items[$offset]) ? $this->items[$offset] : null;
    }
}