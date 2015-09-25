<?php
class Models_Calendar extends ActiveRecord
{
    protected $_table = 'ws_calendar';
    protected $_orderby = array('Start' => 'ASC');

	public $statuses = array(
		0 => 'Неактивная',
		1 => 'Активная',
	);

	static function findActiv($customerId){
		return Models_Calendar::DB()->findAll(array('CustomerId'=>$customerId,'StatusId'=>1));
	}
}