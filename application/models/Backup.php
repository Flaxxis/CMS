<?php
class Models_Backup extends ActiveRecord
{
	protected $_table = 'ws_backup';
	protected $_orderby = array('Id' => 'ASC');
	static $folder = '/backup/';

	protected function _beforeDelete()
	{
		if ($this->getFile() != '') {
			@unlink($_SERVER['DOCUMENT_ROOT'] . Models_Backup::$folder . $this->getFile());
		}
		return true;
	}

	public function SizeText() {
		$size = $this->getSize();
		$units = array('KB', 'MB', 'GB', 'TB');
		$currUnit = 'B';
		while (count($units) > 0  &&  $size > 1024) {
			$currUnit = array_shift($units);
			$size /= 1024;
		}
		return ($size | 0) . $currUnit;
	}

	static function create($name, $addFiles = true){
		/**
		 * @var $db Zend_Db_Adapter_Abstract
		 */
		$db = Zend_Registry::get('db');
		$db = $db->getConfig();
		$fn = $db['dbname'] . date("Y-m-d-H-i-s");
		$backup =  new Models_Backup();
		$backup->setName($name);

		$backupSQL = $_SERVER['DOCUMENT_ROOT'] . Models_Backup::$folder . $fn . '.sql';
		$command = "mysqldump --host={$db['host']} --user {$db['username']} --password={$db['password']} {$db['dbname']} > $backupSQL";
		@system($command);

		$zip = new ZipArchive();
		$zip_name = $fn.".zip";
		$zip->open($_SERVER['DOCUMENT_ROOT'] . Models_Backup::$folder.$zip_name, ZIPARCHIVE::CREATE);
		$zip->addFile($backupSQL, $fn.'.sql');

		if($addFiles) {
			$zip->addEmptyDir('tmp');
			$zip->addEmptyDir('tmp/sessions');
			$zip->addEmptyDir('backup');

			$zip->addFile($_SERVER['DOCUMENT_ROOT'] . '/.htaccess', '.htaccess');
			$zip->addFile($_SERVER['DOCUMENT_ROOT'] . '/.zfproject.xml', '.zfproject.xml');


			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($_SERVER['DOCUMENT_ROOT'] . '/application/'),
				RecursiveIteratorIterator::LEAVES_ONLY
			);
			foreach ($files as $name => $file) {
				$local = substr(str_replace('\\', '/', str_replace(str_replace('/', '\\', $_SERVER['DOCUMENT_ROOT']), '', $file->getRealPath())), 1);
				if (!$file->isDir()) {
					$zip->addFile($file->getRealPath(), $local);
				} else {
					$zip->addEmptyDir($local);
				}
			}

			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($_SERVER['DOCUMENT_ROOT'] . '/public/'),
				RecursiveIteratorIterator::LEAVES_ONLY
			);
			foreach ($files as $name => $file) {
				$local = substr(str_replace('\\', '/', str_replace(str_replace('/', '\\', $_SERVER['DOCUMENT_ROOT']), '', $file->getRealPath())), 1);
				if (!$file->isDir()) {
					$zip->addFile($file->getRealPath(), $local);
				} else {
					$zip->addEmptyDir($local);
				}
			}

			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($_SERVER['DOCUMENT_ROOT'] . '/library/'),
				RecursiveIteratorIterator::LEAVES_ONLY
			);
			foreach ($files as $name => $file) {
				$local = substr(str_replace('\\', '/', str_replace(str_replace('/', '\\', $_SERVER['DOCUMENT_ROOT']), '', $file->getRealPath())), 1);
				if (strpos($local, 'Zend') === false) {
					if (!$file->isDir()) {
						$zip->addFile($file->getRealPath(), $local);
					} else {
						$zip->addEmptyDir($local);
					}
				}
			}
		}

		$zip->close();

		$backup->setFile($zip_name);
		$backup->setSize(filesize($_SERVER['DOCUMENT_ROOT'] . Models_Backup::$folder.$zip_name));
		$backup->save();
		unlink($backupSQL);

		return true;
	}

	public function download(){
		$file = $_SERVER['DOCUMENT_ROOT'] . Models_Backup::$folder . $this->getFile();
		if(!file_exists($file)){
			header("HTTP/1.0 404 Not Found");
			exit;
		} else {
			// сбрасываем буфер вывода PHP, чтобы избежать переполнения памяти выделенной под скрипт
			// если этого не сделать файл будет читаться в память полностью!
			if (ob_get_level()) {
				ob_end_clean();
			}
			// заставляем браузер показать окно сохранения файла
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename=' . basename($file));
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($file));
			// читаем файл и отправляем его пользователю
			readfile($file);
			exit;
		}

	}
}
