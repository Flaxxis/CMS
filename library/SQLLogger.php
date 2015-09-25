<?php

class SQLLogger {

	private $data = array();
	private $_timer = null;
	private $_totaltime = 0;
	
	protected $digits = 4;

	static function &getInstance () {
        static $instance;
        
        if (!isset($instance)) {
            $c = __CLASS__;
            $instance = new $c;
        }
        
        return $instance;
    }

	public function __construct() {
		$this->_timer = new Timer();
	}

	public function log($class, $sql) {
		$this->data[] = array('sql' => $sql, 'class' => $class );	
		$this->_timer->start(/*md5($sql) . count($this->data)*/);
	}
	
	public function done($numrows = 0) {
		$sql = $this->data[count($this->data)-1]['sql'];
		$time = $this->_timer->stop(md5($sql) . (count($this->data)-1))->getTime();
		
		$this->data[count($this->data)-1]['time'] = $time;
		$this->data[count($this->data)-1]['results'] = $numrows;
		$this->_totaltime += $time;
	}
	
	public function setDigits($num) {
		$this->digits = $num;
	}
	
	public function formatTime($time) {
		return round($time, $this->digits);
	}
	
	public function getUsedClasses() {
		$found = array();
		foreach($this->data as $row) {
			$found[$row['class']] = 1;
		}
		
		return array_keys($found);
	}
	
	public function reportByClass($class = '') {
		$found = array();
		foreach($this->data as $row) {
			if(!isset($found[$row['class']]))
			{
				$found[$row['class']] = array();
				$found[$row['class']]['count'] = 0;
				$found[$row['class']]['time'] = 0;
			}
				
			$found[$row['class']]['count']++;
			$found[$row['class']]['time'] += $row['time'];
			
			if(!isset($found[$row['class']]['sql']))
				$found[$row['class']]['sql'] = array();
			$found[$row['class']]['sql'][] = $row['sql'];
		}
		
		foreach($found as &$newrow) {
			$newrow['time'] = $this->formatTime($newrow['time']);
		}
		
		if($class)
			return $found[$class];
		else
			return $found;
	}

	public function reportBySql() {
		$found = array();
		foreach($this->data as $row) {
			$found[] = $row['sql'];
		}
		
		return $found;
	}
	
	public function reportBySqlAndResults() {
		$found = array();
		foreach($this->data as $row) {
			$found[] = array('rows'=>$row['results'], 'sql'=>$row['sql']);
		}
		
		return $found;
	}	
	
	public function reportByTime() {
		$found = array();
		$found['time'] = array();
		$found['sql'] = array();
		foreach($this->data as $row) {
			$found['time'][] = $row['time'];
			$found['sql'][] = $row['sql'];
		}
		
		array_multisort($found['time'], SORT_DESC, SORT_NUMERIC,
						$found['sql'], SORT_ASC, SORT_STRING);
		
		$results = array();
		foreach($found['time'] as $key => $newrow) {
			$results[] = $this->formatTime($newrow) . ' - ' . $found['sql'][$key];
		}
		
		return $results;		
	}
	
	public function reportShort() {
		return array('time' => $this->formatTime($this->_totaltime), 'count' => count($this->data));
	}
}