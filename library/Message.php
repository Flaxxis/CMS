<?php
class Message {
	protected $ses = '_msg_';
	protected static  $_instance = null;

	public static function getInstance(){
		if (null === self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	private function __construct() {
		$this->msg = array();
		if(!isset($_SESSION[$this->ses]))
			$_SESSION[$this->ses] = array();
	}

	private function __clone()
	{
	}
	
	public function set($msg,$type='info') {
		$_SESSION[$this->ses][md5($msg)] = array('type'=>$type, 'message'=>$msg);
		return;
	}
	
	public function has($type='info') {
		foreach($_SESSION[$this->ses] as $val) {
			if($val['type']==$type) return true;
		}
		return false;
	}
	
	public function get($type='info') {
		$vals = array();
		foreach($_SESSION[$this->ses] as $key => $val) {
			if($val['type']==$type) {
				unset($_SESSION[$this->ses][$key]);
				$vals[] = $val['message'];
			}
		}
		return $vals;
	}	
	
	public function count($type='info') {
		$cnt = 0;
		foreach($_SESSION[$this->ses] as $key => $val) {
			if($val['type']==$type) {
				$cnt++;
			}
		}	
		return $cnt;
	}
}