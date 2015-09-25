<?php
/**
 * Class Orm_Magic - base magic functions class for orm package
 * 
 * @package Orm
 */
class Orm_Magic
{
	protected $_store = array();

	public function __set($label, $object) {
		//can be used _store[$label]
		$label = $this->_explodeCase($label);
		$this->_store[$label] = $object;
		
		return $this;
    }
    
    public function __get($label) {
    	//can be used _store[$label]
		$label = $this->_explodeCase($label);		
			  	
        if(isset($this->_store[$label])) {
            return $this->_store[$label];
        }
        return null;
    }
    
 
    public function __unset($label) {
    	//can be used _store[$label]  
    	$label = $this->_explodeCase($label);  
        if(isset($this->_store[$label])) {
            unset($this->_store[$label]);
        }
    }

 
    public function __isset($label) {
    	//can be used _store[$label]
    	$label = $this->_explodeCase($label);
        if(isset($this->_store[$label]) || array_key_exists($label, $this->_store))
        {
            return true;
        }
        return false;
    }

	/* NOT WORKING - only for PHP >= 5.2.0
	static public function __callStatic($method, $params) {
		$cname = __CLASS__;
		$c = new $cname;
		$c->___call($method, $params);
	}
	*/

	public function __call($method, $params) {
		if(strpos($method, 'set') === 0) {
			$prop = substr($method,3);
			$this->$prop = $params[0];
			return true;
		}
		
		if(strpos($method, 'get') === 0) {
			$prop = substr($method,3);		
			return $this->$prop;
		}
		
		return false;
	}
	
	public function __toString() {
		return print_r($this->_store, 1);
	}

	/**
	 * Splits up a string into an array similar to the explode() function but according to CamelCase.
	 * Uppercase characters are treated as the separator but returned as part of the respective array elements.
	 * @author Charl van Niekerk <charlvn@charlvn.za.net>
	 * @param string $string The original string
	 * @param bool $lower Should the uppercase characters be converted to lowercase in the resulting array?
	 * @return array The given string split up into an array according to the case of the individual characters.
	 */
	public static function _explodeCase($string, $lower = true)
	{
		return $string;
	  // Initialise the array to be returned
	  $array = array();
	 
	  // Initialise a temporary string to hold the current array element before it's pushed onto the end of the array
	  $segment = '';
	 
	  // Loop through each character in the string
	  foreach (str_split($string) as $char) {
		// If the current character is uppercase
		if (ctype_upper($char)) {
		  // If the old segment is not empty (for when the original string starts with an uppercase character)
		  if ($segment) {
			// Push the old segment onto the array
			$array[] = $segment;
		  }
		 
		  // Set the character (either uppercase or lowercase) as the start of the new segment
		  $segment = $lower ? strtolower($char) : $char;
		} else { // If the character is lowercase or special
		  // Add the character to the end of the current segment
		  $segment .= $char;
		}
	  }
	 
	  // If the last segment exists (for when the original string is empty)
	  if ($segment) {
		// Push it onto the array
		$array[] = $segment;
	  }
	 
	  // Return the resulting array
	  return implode('_', $array);
	}	
	
	
	/**
	 * Does the reverse of explodeCase() - takes an array as input and combines all the elements back into a string
	 * according to CamelCase format. Uppercase characters are used as the separator between the various segments
	 * so all other characters will be taken to lowercase by default (see the preserve parameter). Therefore this
	 * will not normally preserve the case of the original strings in the array.
	 * 
	 * @param array $array The original array to be converted
	 * @param bool $first Should the first character also be taken to uppercase? (e.g. camelCase versus CamelCase)
	 * @param bool $preserve Preserve the uppercase characters in the original array and don't convert them.
	 * @return string The resulting string combining all of the array elements into CamelCase format.
	 */
	public static function _implodeCase($data, $first = true, $preserve = true)
	{
	  // Initialise the string to be returned
	  $string = '';
	  $array = explode('_', $data);
	  
	  // Loop through each element in the array
	  foreach ($array as $i => $segment) {
		// If the preserve case option has not been set
		if (!$preserve) {
		  // Make sure the current segment does not contain uppercase characters
		  $segment = strtolower($segment);
		}
		
		// If it isn't the first segment or the resulting string must start with an uppercase character
		if ($i || $first) {
		  // Capitalise the first character of the segment
		  $segment = ucfirst($segment);
		}
		
		// Add this segment to the end of the string
		$string .= $segment;
	  }

	  // Return the resulting string
	  return $string;
	}


}