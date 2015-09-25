<?php

class Models_Config extends ActiveRecord
{
	protected $_table = 'ws_config';

	protected static $_cache = array();


	public static function findByCode($code)
	{
		if(isset(self::$_cache[$code]))
			return self::$_cache[$code];

		self::$_cache[$code] = Models_Config::DB()->findFirst(array('Code'=>$code));
		if(!self::$_cache[$code] || !self::$_cache[$code]->getId())
		{
			self::$_cache[$code] = new Models_Config();

			//autosave new values
			self::$_cache[$code]->setCode($code);
			self::$_cache[$code]->save();
		}
		return self::$_cache[$code];
	}

}