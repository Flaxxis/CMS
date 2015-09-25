<?php
/**
 * Class Orm_Array - object as array
 * 
 * @package Orm
 */
class Orm_Array extends Orm_Magic implements Iterator, ArrayAccess
{
	
	protected $_store = array();

    public function __construct($array = array())
    {
        if (is_array($array))
        {
            $this->_store = $array;
			/* Don''t work because first index = 0
			foreach($array as $key => $value)
				$this->$key = $value;*/
        }
    }

	//------------------------------------------
	//iterator

    public function rewind() {
        reset($this->_store);
    }

    public function current() {
        $var = current($this->_store);
        return $var;
    }

    public function key() {
        $var = key($this->_store);
        return $var;
    }

    public function next() {
        $var = next($this->_store);
        return $var;
    }

    public function valid() {
        $var = $this->current() !== false;
        return $var;
    }
    
    public function seek($key)
    {
    	return $this->_store[$key];
    }

	//------------------------------------------
	//array stuff
	
    function offsetExists($offset) {
		return isset($this->_store[$offset]);
    }

    function offsetGet($offset) {
		if(isset($this->_store[$offset]))
			return $this->_store[$offset];
		else
			return null;
    }

    function offsetSet($offset, $value) {
		$this->_store[$offset] = $value;
    }

    function offsetUnset($offset) {
		unset($this->_store[$offset]);
    } 
    
	public function export() {
		return $this->_store;
	}    

}
