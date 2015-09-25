<?php
date_default_timezone_set('Europe/Kiev');
ini_set('session.save_path', APPLICATION_PATH . '/../tmp/sessions/');
setlocale(LC_ALL, "ru_RU");
setlocale(LC_NUMERIC, 'en_US'); //dot - for MySQL
mb_internal_encoding("UTF-8");

set_time_limit(60);

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

define('DOCUMENT_ROOT', realpath(dirname(__FILE__) . '/../'));
define('DEVIP', json_encode(array('62.80.164.3', '5.9.145.93', '127.0.0.1')));
define('PUB', $_SERVER['DOCUMENT_ROOT'] . '/public');
define('INPATH', $_SERVER['DOCUMENT_ROOT']);
define('PDO', 1);

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
	realpath(APPLICATION_PATH),
	realpath(DOCUMENT_ROOT . '/library/'),
	get_include_path(),
)));

require_once('Zend/Loader/Autoloader.php');
$loader = Zend_Loader_Autoloader::getInstance();
$loader->setFallbackAutoloader(true);
$loader->suppressNotFoundWarnings(false);


$timer = new DebugTimer(4);
Zend_Registry::set('timer', $timer);

function d($var, $die = true)
{
	MyDebug::dump($var, $die);
}

function generateHash()
{
	return md5(uniqid(mt_rand(), true));
}

function showDebugInfo()
{
	$timer = Zend_Registry::get('timer');
	//d(SQLLogger::getInstance()->reportShort(), 0);
	//d(SQLLogger::getInstance()->reportBySqlAndResults(),0);
	//d(SQLLogger::getInstance()->reportBySql(),0);
	//d(SQLLogger::getInstance()->reportByTime(),0);
	//d(SQLLogger::getInstance()->reportByClass(),0);
	//d($timer->getResults(),0);
	//d($_SESSION,0);

	$timer->stop();
	d(array(
		'SQL'        => SQLLogger::getInstance()->reportByClass(),
		'SQLTime'    => SQLLogger::getInstance()->reportShort(),
		'DebugTimer' => $timer->getResults(),
		'Time'       => $timer->getResults('main'),
		'Memory'     => number_format(memory_get_peak_usage(true) / 1024 / 1024, 3, '.', ',') . 'Mb'
	), 0);
}