<?php

class AdminController extends controllerAbstract
{
	protected $menu_pages = array(
		'Главная'      => array(
			'url'   => '/admin/',
			'class' => 'fa-home'
		),
		'Календарь'    => array(
			'url'   => '/admin/calendar/',
			'class' => 'fa-calendar'
		),
		'Страницы'     => array(
			'class' => 'fa-desktop',
			'kids'  => array(
				'Список'         => array(
					'url' => '/admin/pages/'
				),
				'Новая страница' => array(
					'url' => '/admin/page/'
				)
			)
		),
		'Новости'      => array(
			'class' => 'fa-list-alt',
			'kids'  => array(
				'Список'        => array(
					'url' => '/admin/news/'
				),
				'Новая новость' => array(
					'url' => '/admin/onenews/'
				)
			)
		),
		'Загрузки'     => array(
			'class' => 'fa-cloud-download',
			'kids'  => array(
				'Галереи' => array(
					'url' => '/admin/gallery/'
				),
				'Изображения' => array(
					'url' => '/admin/images/'
				),
				'PDF'         => array(
					'url' => '/admin/pdfs/'
				),
				'Остальные'   => array(
					'url' => '/admin/other/'
				),
				'Загрузить'   => array(
					'url' => '/admin/upload/'
				)
			)
		),
		'Пользователи' => array(
			'class' => 'fa-group',
			'kids'  => array(
				'Список'             => array(
					'url' => '/admin/users/'
				),
				'Новый пользователь' => array(
					'url' => '/admin/user/'
				)
			)
		),
		'Настройки'    => array(
			'class' => 'fa-cogs',
			'kids'  => array(
				'Профиль'         => array(
					'url' => '/admin/profile/'
				),
				'Настройка сайта' => array(
					'url' => '/admin/configs/'
				),
				'Резервные копии' => array(
					'url' => '/admin/backup/'
				),
				'Очистка кеша' => array(
					'url' => '/admin/clearcache/'
				),
			)
		),
		'Файловая система'      => array(
			'url'   => '/admin/filesystem/',
			'class' => 'fa-folder'
		),
		'Запросы в БД'      => array(
			'url'   => '/admin/db/',
			'class' => 'fa-random'
		),

	);

	public function init()
	{
		parent::init();
		$this->view->showSite = 1;
		$this->validIp(json_decode(DEVIP), '/');

		$this->_helper->layout->setLayout('admin');
		if ($this->customer->isSuperAdmin()) {
			ini_set('display_errors', 1);
			error_reporting(E_ALL ^ E_NOTICE);
		} else {
			ini_set('display_errors', 0);
			error_reporting(0);
		}

		if (!$this->customer->getIsLoggedIn() or !$this->customer->isAdmin()) {
			$this->loginAction();
		} else {
			$this->view->menu_pages = json_decode(json_encode($this->menu_pages));
			$this->view->tiny_path = '/js/tiny_mce/tinymce.min.js';
		}

		$this->view->MessageInfo = '';
		$this->view->MessageError = '';
		$this->view->MessageWarning = '';

	}

	public function indexAction()
	{
		$this->view->page_title = 'Главная';
	}

	public function loginAction()
	{
		if ($this->customer->getIsLoggedIn()) {
			$this->redirect('/admin/');
		}

		$this->_helper->layout->setLayout('admin_login');

		if ($this->getRequest()->isPost()) {
			$login = $this->customer->loginByUsername($this->post->login, $this->post->password, (int)$this->post->remember);
			if ($login) {
				$this->redirect('/admin/');
			}
		}
	}

	public function logoutAction()
	{
		$this->customer->logout();
		$this->redirect('/admin/');
	}

	public function tinymcelistAction()
	{
		$links = array();
		foreach (Models_Menu::DB()->findAll() as $page) {
			$links[] = array('title' => addslashes($page->getName()), 'value' => $page->getPath());
		}
		die(json_encode($links));
	}

	public function profileAction()
	{
		$this->view->page_title = 'Профиль';

		$user = $this->customer;
		$errors = array();
		if ($this->getRequest()->isPost()) {
			$user->setStatusId(0);
			$user->setBan(0);
			$user->import($_POST);

			if (!$user->getEmail() or !Validation::isValidEmail($user->getEmail())) $errors['Email'] = '1';
			if (!$user->getTypeId()) $errors['TypeId'] = '1';

			if ($user->ChangePassword) {
				$user->setPassword($user->cryptPassword($user->ChangePassword));
			}

			if (!count($errors)) {
				$user->save();

				if (isset($_FILES['Ava']) and @$_FILES['Ava']['error'] == 0) {
					$user->uploadAva($_FILES['Ava']);
				}

				$this->view->MessageInfo = 'Сохранено успешно';
			} else {
				$this->view->MessageError = 'Ошибка сохранения';
			}
		}
		$this->view->errors = $errors;
		$this->view->user = $user;
	}

	public function configsAction()
	{
		$this->view->page_title = 'Настройки сайта';
		if ($this->getRequest()->isPost()) {
			foreach ($this->post->config as $k => $v) {
				$config = Models_Config::DB()->findFirst(array('Code' => $k));
				if ($config) {
					$config->setValue($v);
					$config->save();
				}
			}
			$this->view->MessageInfo = 'Сохранено успешно';
		}
		$this->view->configs = Models_Config::DB()->findAll();
	}

	public function usersAction()
	{
		if ((int)$this->getRequest()->getParam('delete')) {
			$user = Models_Customer::DB()->findById((int)$this->getRequest()->getParam('delete'));
			if ($user) {
				$user->destroy();
			}
			$this->redirect('/admin/users/');
		}

		$this->view->page_title = 'Список пользователей';
		$this->view->users = Models_Customer::DB()->findAll();
		$this->render('user/list');
	}

	public function userAction()
	{
		$this->view->page_title = 'Редактировние пользователя';

		$user = new Models_Customer((int)$this->getRequest()->getParam('id'));
		$errors = array();
		if ($this->getRequest()->isPost()) {
			$user->setStatusId(0);
			$user->setBan(0);
			$user->import($_POST);

			if (!$user->getId()) {
				if (!$user->getUsername()) $errors['Username'] = '1';
				$findUser = Models_Customer::DB()->findFirst(array('Username' => $user->getUsername()));
				if ($findUser) $errors['Username'] = '1';
				if (!$user->ChangePassword) $errors['Password'] = '1';
			}

			if (!$user->getEmail() or !Validation::isValidEmail($user->getEmail())) $errors['Email'] = '1';
			if (!$user->getTypeId()) $errors['TypeId'] = '1';

			if ($user->ChangePassword) {
				$user->setPassword($user->cryptPassword($user->ChangePassword));
			}

			if (!count($errors)) {
				$user->save();

				if (isset($_FILES['Ava']) and @$_FILES['Ava']['error'] == 0) {
					$user->uploadAva($_FILES['Ava']);
				}

				$this->view->MessageInfo = 'Сохранено успешно';
			} else {
				$this->view->MessageError = 'Ошибка сохранения';
			}
		}
		$this->view->errors = $errors;
		$this->view->user = $user;
		$this->render('user/edit');
	}

	public function pagesAction()
	{
		if ((int)$this->getRequest()->getParam('delete')) {
			$page = Models_Menu::DB()->findById((int)$this->getRequest()->getParam('delete'));
			if ($page) {
				$page->destroy();
			}
			$this->redirect('/admin/pages/');
		}
		if ($this->getRequest()->getParam('act') == 'saveajaxorder') {
			foreach ($_POST as $k => $v) {
				if (substr($k, 0, 2) == 'id') {
					$updatequery = 'UPDATE  ws_menus SET Sequence=' . ($v * 10) . ' WHERE Id = ' . (int)substr($k, 2, strlen($k));
					echo ActiveRecord::query($updatequery);
				}
			}
			die();
		}

		$this->view->page_title = 'Список страниц';
		$this->view->pages = Models_Menu::DB()->findAll();
		$this->render('page/list');
	}

	public function pageAction()
	{
		$this->view->page_title = 'Редактировние страницы';

		$page = new Models_Menu((int)$this->getRequest()->getParam('id'));

		if (!$page->getId()) {
			if (!$page->getController()) {
				$page->setController('index');
			}
			if (!$page->getAction()) {
				$page->setAction('page');
			}
		}

		$errors = array();
		if ($this->getRequest()->isPost()) {
			$page->setNoDelete(0);
			$page->import($_POST);

			$findPage = Models_Menu::DB()->findFirst(array('Url' => $page->getUrl(), 'ParentId' => $page->getParentId(), 'Id <> ' . (int)$page->getId()));
			if ($findPage) $errors['Url'] = '1';

			if (!$page->getUrl()) $errors['Url'] = '1';
			if (!$page->getName()) $errors['Name'] = '1';


			if (!count($errors)) {
				$page->save();
				if (isset($_FILES['Image']) and @$_FILES['Image']['error'] == 0) {
					$page->uploadImage($_FILES['Image']);
				}

				$this->view->MessageInfo = 'Сохранено успешно';
			} else {
				$this->view->MessageError = 'Ошибка сохранения';
			}
		}
		$this->view->errors = $errors;
		$this->view->page = $page;
		$this->render('page/edit');
	}

	public function uploadAction()
	{
		$this->view->page_title = 'Загрузить файлы';
		$gallery = (int)$this->getRequest()->getParam('gallery');
		if ($_FILES) {
			foreach ($_FILES as $f) {
				$file = new Models_File();
				$file->setGalleryId($gallery);
				$file->uploadImage($f);
			}
			exit;
		}
		$this->view->gallery = $gallery;
		$this->render('file/upload');
	}

	public function delfileAction()
	{
		if ((int)$this->getRequest()->getParam('id')) {
			$file = Models_File::DB()->findById((int)$this->getRequest()->getParam('id'));
			if ($file) {
				$file->destroy();
			}
		}
		$this->redirect($_SERVER['HTTP_REFERER']);
	}

	public function editfileAction()
	{
		$this->view->page_title = 'Редактировать файл';
		$file = new Models_File((int)$this->getRequest()->getParam('id'));
		if (!$file->getId()) {
			$this->redirect('/admin/upload/');
		}
		$errors = array();
		if ($this->getRequest()->isPost()) {
			$file->import($_POST);
			if (!$file->getName()) $errors['Name'] = '1';
			if (!count($errors)) {
				$file->save();
				$this->view->MessageInfo = 'Сохранено успешно';
			} else {
				$this->view->MessageError = 'Ошибка сохранения';
			}
		}

		$this->view->file = $file;
		$this->render('file/edit');

	}

	public function galleryAction(){
		$this->view->page_title = 'Галереи';
		if ((int)$this->getRequest()->getParam('delete')) {
			$item = Models_Gallery::DB()->findById((int)$this->getRequest()->getParam('delete'));
			if ($item) {
				$item->destroy();
			}
			$this->redirect('/admin/gallery/');
		}
		if ($this->getRequest()->isPost()) {
			if($this->post->Name){
				$item = new Models_Gallery();
				$item->import($_POST);
				$item->save();
				$this->redirect('/admin/gallery/');
			}
		}

		$this->view->items = Models_Gallery::DB()->findAll();
		$this->render('file/gallery');
	}

	public function seegalleryAction(){
		$item = new Models_Gallery((int)$this->getRequest()->getParam('id'));
		if(!$item->getId()){
			$this->redirect('/admin/gallery/');
		}
		$this->view->page_title = "Галерея \"{$item->getName()}\"";
		if ($this->getRequest()->isPost()) {
			$item->import($_POST);
			$item->save();
		}

		$this->view->item = $item;
		$this->render('file/seegallery');
	}

	public function imagesAction()
	{
		$this->view->page_title = 'Изображения';
		$this->view->files = Models_File::DB()->findAll(array('TypeId' => 1));
		$this->render('file/images');
	}

	public function pdfsAction()
	{
		$this->view->page_title = 'PDF файлы';
		$this->view->files = Models_File::DB()->findAll(array('TypeId' => 2));
		$this->render('file/list');
	}

	public function otherAction()
	{
		$this->view->page_title = 'Остальные файлы';
		$this->view->files = Models_File::DB()->findAll(array('TypeId' => 0));
		$this->render('file/list');
	}

	public function calendarAction()
	{
		$this->view->page_title = 'Календарь';

		if ($this->getRequest()->getParam('act') == 'add') {
			if ($this->post->title) {
				$calnderItem = new Models_Calendar();
				$calnderItem->setTitle($this->post->title);
				$calnderItem->setDescription($this->post->description);
				$calnderItem->setIcon($this->post->icon);
				$calnderItem->setClasses($this->post->priority);
				$calnderItem->setCustomerId($this->customer->getId());
				$calnderItem->setStatusId(0);
				$calnderItem->save();
				die(json_encode(array('id' => $calnderItem->getId())));
			}
			die();
		}

		if ($this->getRequest()->getParam('act') == 'del') {
			if ($this->post->id) {
				$calnderItem = new Models_Calendar((int)$this->post->id);
				$calnderItem->destroy();
				die(json_encode(array('status' => 1)));
			}
			die();
		}

		if ($this->getRequest()->getParam('act') == 'dates') {
			if ($this->post->id) {
				$calnderItem = new Models_Calendar((int)$this->post->id);

				$calnderItem->setStatusId(1);

				if ($this->post->start) {
					$calnderItem->setStart(date('Y-m-d H:i:s', strtotime($this->post->start)));
				}
				if ($this->post->end) {
					$calnderItem->setEnd(date('Y-m-d H:i:s', strtotime($this->post->end)));
				}

				if ($this->post->allday) {
					if ($this->post->allday == 'true' or $this->post->allday == 1) {
						$calnderItem->setIsAllDay(1);
					} else {
						$calnderItem->setIsAllDay(0);
					}
				}

				$calnderItem->save();
				die(json_encode(array('status' => 1)));
			}
			die();
		}

		$this->view->noActivEvents = Models_Calendar::DB()->findAll(array('CustomerId' => $this->customer->getId(), 'StatusId' => 0));
		$this->view->ActivEvents = Models_Calendar::DB()->findAll(array('CustomerId' => $this->customer->getId(), 'StatusId' => 1));
	}

	public function backupAction()
	{

		if ((int)$this->getRequest()->getParam('delete')) {
			$backup = Models_Backup::DB()->findById((int)$this->getRequest()->getParam('delete'));
			if ($backup) {
				$backup->destroy();
			}
			$this->redirect('/admin/backup/');
		}

		if ((int)$this->getRequest()->getParam('id')) {
			$backup = Models_Backup::DB()->findById((int)$this->getRequest()->getParam('id'));
			if ($backup) {
				$backup->download();
			}
		}

		$this->view->page_title = 'Резервные копии';
		if ($this->getRequest()->isPost()) {
			if ($this->post->name) {
				Models_Backup::create($this->post->name, $this->post->addfiles);
			}
		}

		$this->view->backups = Models_Backup::DB()->findAll();
	}


	public function newsAction()
	{
		if ((int)$this->getRequest()->getParam('delete')) {
			$item = Models_News::DB()->findById((int)$this->getRequest()->getParam('delete'));
			if ($item) {
				$item->destroy();
			}
			$this->redirect('/admin/news/');
		}

		$this->view->page_title = 'Список новостей';
		$this->view->items = Models_News::DB()->findAll();
		$this->render('news/list');
	}

	public function onenewsAction()
	{
		$this->view->page_title = 'Редактировние новость';

		$item = new Models_News((int)$this->getRequest()->getParam('id'));

		$errors = array();
		if ($this->getRequest()->isPost()) {
			$item->setStatus(0);
			$item->import($_POST);
			if ($item->getStart()) $item->setStart(date('Y-m-d', strtotime($item->getStart())));
			if ($item->getEnd()) $item->setEnd(date('Y-m-d', strtotime($item->getEnd())));

			if (!$item->getTitle()) $errors['Title'] = '1';


			if (!count($errors)) {
				$item->save();
				if (isset($_FILES['Image']) and @$_FILES['Image']['error'] == 0) {
					$item->uploadImage($_FILES['Image']);
				}

				$this->view->MessageInfo = 'Сохранено успешно';
			} else {
				$this->view->MessageError = 'Ошибка сохранения';
			}
		}
		$this->view->errors = $errors;
		$this->view->item = $item;
		$this->render('news/edit');
	}

	public function filesystemconnectorAction(){
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);

		include_once ('elfinder/elFinderConnector.class.php');
		include_once ('elfinder/elFinder.class.php');
		include_once ('elfinder/elFinderVolumeDriver.class.php');
		include_once ('elfinder/elFinderVolumeLocalFileSystem.class.php');

		$opts = array(
			// 'debug' => true,
			'roots' => array(
				array(
					'driver'        => 'LocalFileSystem',   // driver for accessing file system (REQUIRED)
					'path'          => DOCUMENT_ROOT,         // path to files (REQUIRED)
					'URL'           => 'http://' . $_SERVER['HTTP_HOST'], // URL to files (REQUIRED)
					'accessControl' => 'access',             // disable and hide dot starting files (OPTIONAL)
					'tmbPath'		=> DOCUMENT_ROOT.'\public\.tmb',
				)
			)
		);

		$connector = new elFinderConnector(new elFinder($opts));
		$connector->run();
	}

	public function filesystemAction(){
		$this->view->page_title = 'Файловая система';
		if (!$this->customer->isSuperAdmin()) {
			$this->redirect('/admin/');
		}
	}

	public function clearcacheAction(){
		$this->view->page_title = 'Очистка кеша';

		$cache = Zend_Registry::get('cache');
		/**
		 * @var $cache Zend_Cache_Core|Zend_Cache_Frontend
		 */
		if($this->getRequest()->isPost()){
			$cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array($this->getRequest()->getParam('tag')));
		} else {
			$cache->clean(Zend_Cache::CLEANING_MODE_ALL);
		}
	}

	public function dbAction(){
		$this->view->page_title = 'Запросы в БД';
		if (!$this->customer->isSuperAdmin()) {
			$this->redirect('/admin/');
		}

		$db = Zend_Registry::get('db');
		$config =$db->getConfig();
		if (function_exists('xdebug_disable')) {
			xdebug_disable();
		}
		@mysql_connect($config['host'], $config['username'], $config['password']);
		@mysql_select_db($config['dbname']);

		$result = array();
		if ($res = mysql_query('SHOW TABLES')) {
			if ($res === true) {
				return true;
			}
			if (mysql_num_rows($res) > 0) {
				while ($row = mysql_fetch_row($res)) {

					$resi = mysql_query('SHOW COLUMNS FROM ' . $row[0]);
					$result[$row[0]] = array();
					while ($rowi = mysql_fetch_assoc($resi)) {
						$result[$row[0]][] = $rowi;
					}

				}
			}
		}
		$this->view->struct = $result;


		if ($this->getRequest()->isPost()) {
			$result = array();
			if ($res = mysql_query($this->post->query)) {
				if ($res === true) {
					return true;
				}
				if (mysql_num_rows($res) > 0) {
					while ($row = mysql_fetch_assoc($res)) {
						$result[] = $row;
					}
					$this->view->answer = $result;
				} else {
					$this->view->answer = array();
				}
			} else {
				$this->view->answer = "MySQL Error: " . mysql_error();
			}

		}
	}
}
