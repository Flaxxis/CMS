<?php
/**
 * Class Orm_Collection - collection class for orm package
 * 
 * @package Orm
 */
class Orm_Collection extends Orm_Array
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
    
    public function add($obj)
    {
    	$this->_store[] = $obj;
    }
    
    public function merge($objs)
    {
		foreach($objs as $obj) {
			$this->add($obj);
		}
		return $this;   
    }
    
    public function del($index)
    {
    	if (isset($this->_store[$index]))
    		unset($this->_store[$index]);
    }

	public function at($pos) {
		if(isset($this->_store[$pos]))
			return $this->_store[$pos];
		else
			return null;
	}
	
	public function count() {
		return count($this->_store);
	}

	public function shuffle() {
		shuffle($this->_store);
		return $this;
	}
	
	public function random() {
		$pos = rand(0, $this->count()-1);
		return $this->_store[$pos];
	}
	
	public function paginate($start = 0, $count  = 1) {
		if(!$count)
			return new Orm_Collection();
		
		return new Orm_Collection(array_slice($this->_store, $start, $count, true));
	} 
	
	public function export($recursive = false) {
        if($recursive) {
            return array_map(create_function('$item', 'return $item->export(true);'), $this->_store);
        } else {
            return $this->_store;
        }
	}
}
