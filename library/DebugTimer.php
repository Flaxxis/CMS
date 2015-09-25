<?php

class DebugTimer {

	protected $results;
	protected $digits;

	public function DebugTimer($digits = 3) {
		$this->results = array();
		$this->digits = $digits;
		$this->start();
	}

	public function start($name='main') {
		//if(!isset($this->results[$name]))
			$this->results[$name] = array();

		//if(!isset($this->results[$name]['start']))
			$this->results[$name]['start'] = microtime(true);

		return;
	}

	public function stop($name='main') {
		if(!isset($this->results[$name]['end']))
			$this->results[$name]['end'] = microtime(true);

		return;
	}


	public function getResults($name='') {
		if($name) {
			$this->stop($name);
			//if(!isset($this->results[$name]['time']))
				$this->results[$name]['time'] = $this->_calc($this->results[$name]['start'],$this->results[$name]['end']);
			return $this->results[$name]['time'];
		} else {
			$res = array();
			foreach($this->results as $key=>$value) {
				$res[$key] = $this->getResults($key);
			}
			return $res;
		}
	}


	protected function _calc($s, $e) {
		return round($e - $s, $this->digits);
	}

}


?>