<?php
// Define path to application directory
defined('APPLICATION_PATH')
|| define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define application environment
defined('APPLICATION_ENV')
|| define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

include_once 'functions.php';

$config = new Zend_Config_Ini(DOCUMENT_ROOT . '/application/configs/application.ini', 'production');
Zend_Registry::set('config', $config);
Zend_Registry::set('obj',0);


$engineCache = Zend_Cache::factory('Core', 'File',
	array(
		'caching'                   => true,
		'cache_id_prefix'           => 'Zend',
		'automatic_cleaning_factor' => 0,
		'lifetime'                  => 3 * 60 * 60,
		'automatic_serialization'   => true,
	),
	array(
		'cache_dir' => DOCUMENT_ROOT . $config->path->temporary_cache
	));
Zend_Registry::set('engineCache', $engineCache);
Zend_Registry::set('cache', $engineCache);

$db = Zend_Db::factory($config->resources->db);
Zend_Registry::set('db', $db);
Zend_Db_Table::setDefaultAdapter($db);

Zend_Db_Table_Abstract::setDefaultMetadataCache($engineCache);
Zend_Locale::setCache($engineCache);
Zend_Translate::setCache($engineCache);

Zend_Session::start();

Zend_Registry::set('SQLLogger', SQLLogger::getInstance());

$sessionNamespace = new Zend_Session_Namespace('Visit');
$visitHash = $sessionNamespace->VisitHash;
if (!$visitHash) {
	$sessionNamespace->setExpirationSeconds(60 * 60 * 3);
	$sessionNamespace->VisitHash = generateHash();
}

$customer = Models_Customer::FindUser();
Zend_Registry::set('CustomerId', (int)$customer->getId());
$visit = Models_Visit::findByHash($visitHash);


$writer_errors_file = new Zend_Log_Writer_Stream($config->logs->errors.'errors');
$logger_errors = new Zend_Log();
$logger_errors->addWriter($writer_errors_file);
Zend_Registry::set('logger_errors', $logger_errors);

// Create application, bootstrap, and run
$application = new Zend_Application(
	APPLICATION_ENV,
	$config
);
$application->bootstrap()
	->run();

if ($customer->isSuperAdmin() and Models_Config::findByCode('debugReport')->getValue()) {
	showDebugInfo();
}