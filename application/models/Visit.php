<?php
class Models_Visit extends ActiveRecord
{
    protected $_table = 'ws_visits';
    protected $_orderby = array('Id' => 'DESC');


	static function findByHash($hash){
		if($hash) {
			$visit = Models_Visit::DB()->findFirst(array('Hash' => $hash));
			if (!$visit) {
				$visit = new Models_Visit();
				$visit->setHash($hash);
				$visit->setStartUrl('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
				$visit->setRefererUrl(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
			} else {
				$visit->setEndUrl('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
				$visit->setTotalNumberOfPages($visit->getTotalNumberOfPages() + 1);
				$visit->setDurationInMinutes(round((time() - ($visit->getCtime() ? strtotime($visit->getCtime()) : time())) / 60, 0));
			}

			if (!$visit->getCustomerId()) {
				$visit->setCustomerId(Zend_Registry::get('CustomerId'));
			}

			$visit->save();
			return $visit;
		}
		return false;
	}
}
?>