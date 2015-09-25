<?php

class Models_Customer extends ActiveRecord
{
	const HASH_TYPE = 'sha256';

	protected $_table = 'ws_customers';

	protected $is_logged_in = false;
	protected $_address = null;

	public $types = array(
		'1'   => 'user',
		'2'   => 'manager',
		'3'   => 'supermanager',
		'100' => 'admin',
		'200' => 'superadmin',
	);

	protected $ControllersRights = array(
		'Admin' => 100,
		'Wm'    => 2,
	);
	protected $ActionsRights = array();

	protected $folder = '/storage/users/';
	protected $ava_x = 150;
	protected $ava_y = 150;

	protected function _defineRelations()
	{
		$this->_relations = array(
			'Parent' => array(
				'type'  => 'hasOne',
				'class' => 'Models_Customer',
				'field' => 'ParentId'
			),
			'Kids'   => array(
				'type'          => 'hasMany',
				'class'         => 'Models_Customer',
				'field_foreign' => 'ParentId',
				'onDelete'      => 'null'
			)
		);
	}

	protected function _beforeDelete()
	{
		if ($this->getAva() != '') {
			@unlink(PUB . $this->getAva());
		}
		return true;
	}

	static function FindUser()
	{
		$auth = Zend_Auth::getInstance();
		$auth->setStorage(new Zend_Auth_Storage_Session('AuthUser'));

		if ($auth->hasIdentity()) {
			$user = new Models_Customer($auth->getIdentity()->Id);
			$user->setIsLoggedIn(true);
		} else {
			$user = new Models_Customer();
		}
		return $user;
	}

	public function loginByUsername($email, $password, $remember = false)
	{
		$hashed_password = Models_Customer::cryptPassword($password);

		$authAdapter = new Zend_Auth_Adapter_DbTable(Zend_Registry::get('db'), 'ws_customers', 'Username', 'Password');
		$authAdapter->setIdentity($email);
		$authAdapter->setCredential($hashed_password);
		$auth = Zend_Auth::getInstance();

		$sessionNamespace = new Zend_Session_Namespace('AuthUser');
		$sessionNamespaceVisit = new Zend_Session_Namespace('Visit');
		if ($remember) {
			$sessionNamespace->setExpirationSeconds(60 * 60 * 10);
			$sessionNamespaceVisit->setExpirationSeconds(60 * 60 * 10);
		} else {
			$sessionNamespace->setExpirationSeconds(60 * 60 * 3);
			$sessionNamespaceVisit->setExpirationSeconds(60 * 60 * 3);
		}

		$auth->setStorage(new Zend_Auth_Storage_Session('AuthUser'));

		$authRes = $auth->authenticate($authAdapter);

		if ($authRes->isValid()) {
			$storage = $auth->getStorage();

			$storage->write($authAdapter->getResultRowObject(
				null,
				'Password'
			));

			$user = new Models_Customer(Zend_Auth::getInstance()->getIdentity()->Id);
			$user->setHashVisit(self::HashVisit());
			$user->save();
			$this->import($user, $from_db = 1);
			$this->setIsNew(false);
			$this->setIsLoggedIn(true);
			return true;
		}

		$this->setIsLoggedIn(false);
		return false;
	}

	public function logout()
	{
		Zend_Auth::getInstance()->clearIdentity();
		$sessionNamespace = new Zend_Session_Namespace('AuthUser');
		$sessionNamespaceVisit = new Zend_Session_Namespace('Visit');
		$sessionNamespace->unsetAll();
		$sessionNamespaceVisit->unsetAll();
		$this->clear();
		$this->import(new Models_Customer());
		$this->setIsLoggedIn(false);
		return true;
	}

	static function cryptPassword($password)
	{
		return hash(self::HASH_TYPE, $password . self::$_key);
	}

	public function getFullname()
	{
		return ($this->getMiddleName() ? $this->getMiddleName() . ' ' : '') . $this->getFirstName() . ' ' . $this->getLastName();
	}

	protected function _beforeSave()
	{
		if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'])
			$this->setIp($_SERVER['REMOTE_ADDR']);

		if ($this->getHashVisit() != self::HashVisit()) {
			$this->setVisitTime(date('Y-m-d H:i:s'));
		}

		return true;
	}

	static function findByHash($hash)
	{
		return Models_Customer::DB()->findFirst(array('HashId' => (string)$hash));
	}

	public function getType()
	{
		if (isset($this->types[$this->getTypeId()])) {
			return $this->types[$this->getTypeId()];
		}
		return 'undefined';
	}

	public function isAdmin()
	{
		$types = array_flip($this->types);
		if (isset($types['admin'])) {
			if ($types['admin'] <= $this->getTypeId()) {
				return true;
			}
		}
		return false;
	}

	public function isSuperAdmin()
	{
		$types = array_flip($this->types);
		if (isset($types['superadmin'])) {
			if ($types['superadmin'] <= $this->getTypeId()) {
				return true;
			}
		}
		return false;
	}


	static function generatePassword($length = 9)
	{
		$allowedChars = 'aeuybdghjmnpqrstvz';
		$allowedChars .= '123456789';

		$password = '';
		$chars_count = mb_strlen($allowedChars) - 1;
		for ($i = 0; $i < $length; $i++) {
			$password .= $allowedChars[(rand(0, $chars_count))];
		}

		return $password;
	}

	public function uploadAva($file)
	{
		require_once('upload/class.upload.php');
		$folder = PUB . $this->folder;


		$fname = pathinfo($file['name']);
		$file['name'] = Validation::translit($fname['filename']) . '.' . $fname['extension'];
		$handle = new upload($file, 'ru_RU');

		if ($handle->uploaded) {
			$handle->image_resize = true;
			$handle->image_x = $this->ava_x;
			$handle->image_y = $this->ava_y;
			$handle->image_ratio = true;
			$handle->image_ratio_fill = true;
			$handle->image_background_color = "#FFFFFF";
			$handle->process($folder);
			if ($handle->processed) {
				$uploaded_image_name = $this->folder . $handle->file_dst_name;
				@unlink(PUB . $this->getAva());
				$this->setAva($uploaded_image_name);
				$this->save();
				return true;
			}

		}
	}

	public function getAllStatus($separator = '<br/>')
	{
		$text = array();
		if ($this->getStatusId()) {
			$text[] = 'Активный';
		} else {
			$text[] = 'Неактивный';
		}

		if ($this->getBan()) {
			$text[] = 'Бан';
		}

		return implode($separator, $text);
	}
}