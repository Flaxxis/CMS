<?php
class Models_Log extends ActiveRecord
{
    protected $_table = 'ws_log';
    protected $_orderby = array('Id' => 'ASC');


	public static function add($message, $type = 'INFO', $params ='')
	{
		$logger = new Models_Log();
		$logger->setHashVisit(self::HashVisit());
		$logger->setUrl(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '' );
		$logger->setReffererUrl(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
		$logger->setTimestamp(date('Y-m-d H:i:s'));
		$logger->setPriorityName($type);
		$logger->setPriority(1);
		$logger->setParams($params);
		$logger->setMessage($message);
		$logger->save();
	}
}
?>