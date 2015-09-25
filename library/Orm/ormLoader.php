<?php
/**
 * Class ormLoader - class for orm autoload
 * 
 * @package orm
 */
class ormLoader extends Zend_Loader
{
    public static function autoload($class)
    {
    	$class = strtr($class, '\\', '/');
		$filename = $class . '.php';
 		$file_m = WEBSHOP_PATH . 'models' . DIRECTORY_SEPARATOR . $filename;
 		$file_c = WEBSHOP_PATH . 'packages' . DIRECTORY_SEPARATOR . $filename;
 		$file_o = WEBSHOP_PATH . 'packages/orm' . DIRECTORY_SEPARATOR . $filename;
 		$result = $class;
		switch(true)
		{
			case file_exists($file_m):
				include ($file_m);					
				break;
   
			case file_exists($file_c):
				include ($file_c);
				break;
				
			case file_exists($file_o):
				include ($file_o);
				break;
				
			default:
				$result = false;
				break;
		}
		
		if (!$result) {
			$result = parent::autoload($class);
    	}
        return $result;
    }
}

spl_autoload_register(array('OrmLoader', 'autoload'));

function ___autoload($class)
{
	OrmLoader::autoload($class);
}