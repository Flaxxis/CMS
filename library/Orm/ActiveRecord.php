<?php
/**
 * Class Orm_ActiveRecord - Base class responsible for ActiveRecord
 *
 * @package Orm
 */

class Orm_ActiveRecord extends Orm_Array
{
	protected $_table = '';
	protected $_orderby = array('Id' => 'ASC');
	protected $_id = 'Id';
	protected $_created = 'Ctime';
	protected $_updated = 'Utime';
	protected $_lang = '';
	protected $_model = 'Orm_ActiveRecord';
	protected $_multilang = array();
	protected $_relations = array();
	protected $_cached = false;

	protected $db_store = array();
	private $db = null;
	private $stmt;

	public function getTable($new_table = '')
	{
		return $this->_table;
		/*if($new_table)
			return $this->rquote($this->escape($new_table));
		else
			return $this->rquote($this->_table);*/
	}

	public function getIdName()
	{
		//return $this->stmt->rquote($this->_id);
		return $this->_id;
	}

	protected function _getUpdatedName()
	{
		return $this->_updated;
	}

	protected function _getCreatedName()
	{
		return $this->_created;
	}

	/**
	 * Return statement object
	 *
	 * @return Orm_Statement
	 */
	public function getStmt()
	{
		return $this->stmt;
	}

	/**
	 * Return object by given id
	 *
	 * @param int $id
	 * @return Orm_ActiveRecord|null
	 */

	public function findById($id)
	{
		// prepare statement
		$this->stmt
			->from($this->getTable(), '*')
			->where($this->stmt->rquote($this->getTable()) . '.' . $this->stmt->rquote($this->_id) . ' = ?', $id);
		// check for extra params (site_id and lang_id)
		$this->addExtraStmtParams();
		// add objects for autoload to statement
		$this->_addAutoloadToSelect();
		$result = $this->stmt->getRow();

		//
		if ($result) {
			$this->loadObject($result);
		} else {
			return null;
		}
		return $this;
	}

	/**
	 * Load object
	 *
	 * @param stdClass|array $data
	 * @return boolean
	 */
	public function loadObject($data)
	{
		$this->import($data, $from_db = 1);
		return true;
	}

	/**
	 * Add join construction for current select object
	 *
	 * @return boolean true
	 */
	protected function _addAutoloadToSelect()
	{
		return true;
		foreach ($this->_getAutoloadRelations() as $key => $relation) {
			$rel_obj = new $relation['class']();
			$rel_obj_fields = array();
			foreach ($rel_obj->export() as $key => $value) {
				$rel_obj_fields[$rel_obj->getTable() . '__' . $key] = $key;
			}
			$this->stmt
				->join(
					$rel_obj->getTable(),
					$this->getTable() . '.' . $relation['field'] . ' = ' . $rel_obj->getTable() . '.' . $rel_obj->_id,
					$rel_obj_fields
				);
		}

		return true;
	}

	/**
	 * Return autoload relations (relation hasOne, autoload = true)
	 *
	 * @return array
	 */
	protected function _getAutoloadRelations()
	{
		if (!Zend_Registry::isRegistered('autoload') || !Zend_Registry::get('autoload'))
			return array();

		$result = array();
		foreach ($this->_relations as $key => $relation) {
			if (strtolower($relation['type']) == 'hasone' && @$relation['autoload']) {
				$result[$key] = $relation;
			}
		}

		return $result;
	}

	/**
	 * Check if object is new (stored in db)
	 *
	 * @return boolean
	 */
	public function isNew()
	{
		return !$this->getId();
	}

	/**
	 * Check if object is changed
	 *
	 * @return boolean
	 */
	public function isDirty($name = '')
	{
		foreach ($this->db_store as $field => $value) {
			if (array_key_exists($field, $this->_store)) {
				if ($this->_store[$field] !== $value) {
					if ($name) {
						if ($name == $field)
							return true;
					} else
						return true;
				}
			}
		}
		return false;
	}

	/**
	 * Save object
	 *
	 * @return int|boolean Id if saved object or false on failure
	 */
	public function save()
	{

		if (!$this->_beforeSave())
			throw new Orm_Exception('Before save event failed');

		if (!$this->isDirty())
			return $this->getId();

		if (!isset($this->_no_update) || !$this->_no_update)
			if (array_key_exists($this->_getUpdatedName(), $this->db_store))
				$this->{"set" . $this->_implodeCase($this->_getUpdatedName())}(date('Y-m-d H:i:s'));

		if ($this->getId() && $this->db_store[$this->getIdName()]) {
			if (!$this->_beforeUpdate())
				throw new Orm_Exception('Before update event failed');
			$action = 'UPDATE';
			$set = $this->_set($this->export(), 0, $insert = 0);
			$this->stmt->update($this->getTable(), $set, array($this->_id => $this->getId()));
		} else {
			if (Zend_Registry::isRegistered('site_id') && array_key_exists('site_id', $this->db_store))
				$this->setSiteId(Zend_Registry::get('site_id'));
			if (Zend_Registry::isRegistered('lang_id') && array_key_exists('lang_id', $this->db_store))
				$this->setLangId(Zend_Registry::get('lang_id'));

			if (!isset($this->_no_create) || !$this->_no_create)
				if (array_key_exists($this->_getCreatedName(), $this->db_store))
					$this->{"set" . $this->_implodeCase($this->_getCreatedName())}(date('Y-m-d H:i:s'));

			if (!$this->_beforeInsert())
				throw new Orm_Exception('Before insert event failed');

			$action = 'INSERT';
			$set = $this->_set($this->export(), 0, $insert = 1);
			$this->stmt->insert($this->getTable(), $set);
		}
		if ($this->stmt->execute()) {
			if (!$this->getId())
				$this->setId($this->getLastInsertId());

			//update db_store
			foreach ($this->export() as $field => $value) {
				if (array_key_exists($field, $this->db_store)) {
					if ($this->db_store[$field] != $value)
						$this->db_store[$field] = $value;
				}
			}
		} else {
			//throw new Orm_Exception("Error saving data" . $q);
		}

		if (!$this->_afterSave())
			throw new Orm_Exception('After save event failed');

		if ($action == 'UPDATE') {
			if (!$this->_afterUpdate())
				throw new Orm_Exception('After update event failed');
		} else {
			if (!$this->_afterInsert())
				throw new Orm_Exception('After insert event failed');
		}

		/*
		if($this->db_store)
		{
			$cache = Zend_Registry::get('cache');
			$a = array('db'=>$this->db_store, 'data'=>$this->_store);
			foreach($a['data'] as $key=>$row)
				if(is_object($row))
					unset($a['data'][$key]);
			@$cache->save($a, $this->getTable() . '_' . $this->getId(), array('obj'));
		}
		*/

		return $this->getId();
	}

	/**
	 * Delete object and its relations
	 *
	 * @return int 1 - deleted, 0 - failure
	 */
	public function destroy($recursive = true)
	{
		if (!$this->getId())
			//throw new Orm_Exception("Object is not in DB - cannot delete");
			return false;

		if (!$this->_beforeDelete())
			return false;

		//first - delete from DB to avoid recursion
		if (!$deleted = $this->stmt->delete($this->getTable(), array($this->_id => $this->getId()))->execute()) {
			throw new Orm_Exception("Error deleting data");
		}

		if (!$recursive)
			return $deleted;

		//remove al lrealted objects if needed
		if (count($this->_relations))
			foreach ($this->_relations as $key => $relation) {
				$name = $this->_implodeCase($key);
				switch (strtolower($relation['type'])) {
					case 'hasone':
						if (isset($relation['onDelete']) && $relation['onDelete'] == 'delete') {
							if ($this->{"get$name"}())
								$this->{"get$name"}()->destroy();
						} else {
							/*
							$link = $this->_implodeCase($relation['field']);
							$this->{"set$link"}(null);
							$this->save();
							*/
						}
						break;

					case 'hasmany':
						// same as for n2n
					case 'n2n':
						$values = $this->{"get$name"}();
						if($values) {
							//do nullify
							$cnt = $values->count();
							for ($i = 0; $i < $cnt; $i++) {
								$this->{"unlink$name"}($values[$i]);
							}
						}
						break;

					default:
						throw new Orm_Exception("Incorrect relation type '{$relation['type']}'");
				}

			}

		$this->_store = array();
		$this->db_store = array();
		$this->_getDBFields();

		$this->_afterDelete();

		return $deleted;
	}

	static public function find($class_name, $condition = array(), $sorting = array(), $limits = array(), $count = 0)
	{
		return self::useStatic($class_name)->findAll($condition, $sorting, $limits, $count);
	}

	public function findAll($condition = array(), $sorting = array(), $limits = array(/*0, 1000*/), $count = 0)
	{
		if ($count) {
			$this->stmt
				->from(
					$this->getTable(),
					array('cnt' => 'COUNT(*)')
				);
		} else {
			$this->stmt
				->from(
					$this->getTable(),
					array('*')
				)
				->order($this->_sort($sorting));
			if ($limits)
				$this->stmt
					->limit($limits);

			// add autoload objects
			$this->_addAutoloadToSelect();
		}

		// add where condition
		$this->stmt
			->where($this->_where($condition));

		// check for extra params (site_id and lang_id)
		$this->addExtraStmtParams();

		$result = array();
		if ($res = $this->stmt->getResults()) {

			if ($count)
				return $res[0]->cnt;

			foreach ($res as $row) {
				$val = new $this->_model();


				//$val->import($row, $from_db = 1);
				$val->loadObject($row);
				$result[] = $val;
			}

		}

		return new Orm_Collection($result);
	}

	public function findByQuery($query)
	{
		$result = array();
		if (!$query)
			return new Orm_Collection($result);

		if ($res = $this->stmt->setQuery($query)->getResults()) {
			foreach ($res as $row) {
				$val = new $this->_model();
				$val->import($row, $from_db = 1);
				$result[] = $val;
			}
		}
		return new Orm_Collection($result);
	}

	public function count($condition = array(), $sorting = array(), $limits = array())
	{
		return $this->findAll($condition, $sorting, $limits, 1);
	}

	public function findFirst($condition = array(), $sorting = array())
	{
		$res = $this->findAll($condition, $sorting, array(0, 1));
		if ($res)
			return $res[0];
		else
			throw new Orm_Exception("Nothing found in findFirst");
		//return $this;
	}


	public function clear()
	{
		foreach ($this->_store as &$val)
			$val = '';
	}


	//------------------------------------------

	public function generateSelect($condition = array(), $sorting = array(), $limits = array(), $zero = 1)
	{
		$res = $this->findAll($condition, $sorting, $limits);
		if ($zero)
			$result = array('0' => '');
		else
			$result = array();
		foreach ($res as $row) {
			$result[$row->getId()] = $row->getName();
		}

		return $result;
	}


	public function setLang($lang)
	{
		$this->_lang = $lang;
	}

	public function getLang()
	{
		if (!$this->_lang) {
			$this->setLang(Zend_Registry::get('lang'));
		}
		if ($this->_lang == Zend_Registry::get('default_language'))
			return '';
		return '_' . $this->_lang;
	}

	public function export()
	{
		return $this->_store;
	}

	public function import($data, $from_db = 0)
	{
		if (is_numeric($data) && $data > 0)
			return $this->findById($data);

		if (is_array($data))
			$data = (object)$data;
		if (is_object($data)) {

			foreach ($data as $key => $value) {
				$this->{"set$key"}($value);
				if ($from_db) {
					$this->db_store[$key] = $value;
					$this->_store[$key] = $value;
				}
			}
		}
		return $this;
	}

	public function getRelations()
	{
		return $this->_relations;
	}

	//------------------------------------------
	//events

	protected function _beforeUpdate()
	{
		return true;
	}

	protected function _beforeInsert()
	{
		return true;
	}

	protected function _beforeSave()
	{
		return true;
	}

	protected function _beforeDelete()
	{
		return true;
	}

	protected function _afterDelete()
	{
		return true;
	}

	protected function _afterSave()
	{
		return true;
	}

	protected function _afterUpdate()
	{
		return true;
	}

	protected function _afterInsert()
	{
		return true;
	}

	protected function _defineRelations()
	{
		return true;
	}

	//------------------------------------------

	protected function _translatedName($name)
	{
		$lang = $this->getLang();
		if (isset($this->{$name . $lang}))
			return $name . $lang;
		else
			return $name;
	}


	//DB functions
	protected function _update($new_data)
	{
		$set = $this->_set($new_data, $forced = 1);
		$data = array(
			$this->getIdName() => $this->getId(),
		);
		return $this->stmt->update($this->getTable(), $set, $data)->execute();
	}

	public function _query($query)
	{
		return $this->getStmt()->setQuery($query)->execute();
	}

	static public function query($query)
	{
		$s = new Orm_Statement(Zend_Registry::get('db'), 'wsActiveRecord');
		return $s->setQuery($query)->execute();
	}

	protected function _set($conditions, $forced = 0, $insert = 0)
	{

		if (!is_array($conditions) || !count($conditions))
			return '';

		$str = array();
		foreach ($conditions as $field => $value) {
			if (array_key_exists($field, $this->db_store) || $forced) {
				if ($this->db_store[$field] !== $value || $value === null) {
					//if(is_array($value))
					//	d($conditions);
					$str[] = $this->stmt->rquote($this->escape($field)) . ' = ' . ($value === null ? 'NULL' : $this->stmt->quote($this->escape($value)));
				}
			}
		}
		if (!count($str))
			return '';
		return ' ' . implode(', ', $str);
	}

	/*protected function _limit($conditions)
	{
		if(!count($conditions))
			return '';

		if(!is_array($conditions))
			return "0, " . intval($conditions);

		if(count($conditions)==1) {
			return "0, " . intval($conditions[0]);
		}

		return intval($conditions[0]) . ", " . intval($conditions[1]);
	}*/

	protected function _where($conditions)
	{
		if (is_array($conditions)) {
			$new_conditions = array();
			foreach ($conditions as $key => $value) {
				if (!is_numeric($key) && !strpos($key, '.'))
					$new_conditions[$this->getTable() . '.' . $key] = $value;
				else
					$new_conditions[$key] = $value;
			}
			$conditions = $new_conditions;
		}

		return $conditions;
	}

	protected function _sort($conditions)
	{
		if (!$conditions && $this->_orderby) {
			$conditions = $this->_orderby;
		}

		if ((is_array($conditions) && !count($conditions))
			|| !$conditions
		)
			return $this->stmt->rquote($this->_id) . ' ASC ';

		if (!is_array($conditions) && $conditions)
			return $this->escape(str_ireplace('order by', '', $conditions));

		$ret = array();
		foreach ($conditions as $field => $value) {
			$ret[] = ($field ? $this->escape($field) . ' ' : '') . $this->escape($value);
		}

		return $ret;
		/*if(!is_array($conditions) && $conditions)
			return $this->escape(str_ireplace('order by', '', $conditions));

		$ret = array();
		foreach($conditions as $field => $value)
		{
			$ret[] = ($field ? $this->stmt->rquote($this->escape($field)) . ' ' : '') . $this->escape($value);
		}

		if(!count($ret))
			return '';
		return implode(', ', $ret);*/
	}

	/**
	 * Add extra params to statement (add site_id and lang_id if object has it)
	 *
	 * @return void;
	 */
	public function addExtraStmtParams()
	{
		if (Zend_Registry::isRegistered('site_id') && Zend_Registry::get('site_id') && array_key_exists('site_id', $this->db_store))
			$this->stmt->where(
				$this->stmt->rquote($this->getTable()) . '.' . $this->stmt->rquote('site_id')
				. ' IS NULL OR ' . $this->stmt->rquote($this->getTable()) . '.' . $this->stmt->rquote('site_id')
				. ' = ?', (int)Zend_Registry::get('site_id'));

		if (Zend_Registry::isRegistered('lang_id') && array_key_exists('lang_id', $this->db_store))
			$this->stmt->where(
				$this->stmt->rquote($this->getTable()) . '.' . $this->stmt->rquote('lang_id')
				. ' IS NULL OR ' . $this->stmt->rquote($this->getTable()) . '.' . $this->stmt->rquote('lang_id')
				. ' = ?', Zend_Registry::get('lang_id'));

		return;
	}

	protected function _getDBFields()
	{
		GLOBAL $allfields;
		if (!$this->_table)
			return false;
		$cache = Zend_Registry::get('cache');
		if (count($this->db_store)) return $this->db_store;

		$cache_name = $this->_table;
		if (!@$allfields[$this->getTable()])
			if (!$allfields[$this->getTable()] = @json_decode($cache->load($cache_name), true)) {
				$query = "DESC {$this->getTable()}";
				$tmp = $this->stmt->setQuery($query)->getResults();
				if (!$tmp) return false;

				foreach ($tmp as $field) {
					$allfields[$this->getTable()][$field->Field] = '';
				}
				$cache->save(json_encode($allfields[$this->getTable()]), $cache_name);
			}

		$this->db_store = $allfields[$this->getTable()];
		$this->_store = $allfields[$this->getTable()];
		if (isset($this->_store['action']))
			$this->setAction('');
		/*
		foreach($allfields[$this->getTable()] as $field) {
			$this->db_store[$field->Field] = '';
			$this->_store["{$field->Field}"] = '';
			//???
			if($field->Field=='action')
				$this->setAction('');
		}
		*/

		//print_r($this->db_store);
		return $this->db_store;
	}

	protected function _setRelation($label, $value)
	{
		$data = $this->_relations[$label];
		switch (strtolower(@$data['type'])) {
			case 'hasone':
				$name = $this->_implodeCase($data['field']);
				$class = '_scalar_';
				if (is_object($value) && (($class = get_class($value)) == $data['class'])) {
					//save first!
					if (!$value->getId())
						$value->save();
					$this->{"set$name"}($value->getId());
					//load object - not only Id??
				} elseif (!is_object($value)) {
					$this->{"set$name"}(null);
				} else
					throw new Orm_Exception("Wrong hasOne class supplied. Expected {$data['class']}, received $class");

				$this->_store[$this->_explodeCase($label)] = $value;
				break;

			case 'hasmany':
				// same as for n2n
			case 'n2n':
				$name = $this->_implodeCase($label);
				if (!$this->isNew())
					$this->{'unlink' . $name}();

				if (is_object($value) && get_class($value) != 'Orm_Collection') {
					$this->{'addTo' . $name}($value);
				} elseif (is_array($value) || get_class($value) == 'Orm_Collection') {
					foreach ($value as $obj) {
						$this->{'addTo' . $name}($obj);
					}
				}
				break;

			default:
				throw new Orm_Exception("Incorrect relation type '{$data['type']}'");
		}
	}

	protected function _getRelation($label, $params = array())
	{
		$data = $this->_relations[$label];
		$where = @$params[0];
		$order = @$params[1];
		$limit = @$params[2];
		switch (strtolower(@$data['type'])) {
			case 'hasone':
				if ($this->{$data['field']})
					return new $data['class']($this->{$data['field']});
				else
					return null;

				break;

			case 'hasmany':
				try {
					$new_obj = new $data['class']();
				} catch (exception $e) {
					throw new Orm_Exception("Related class not found '{$data['class']}'");
				}
				$name = $this->_implodeCase($data['field_foreign']);
				$where[$this->_explodeCase($name)] = $this->getId();
				$orderby = $order ? $order : (isset($data['orderby']) ? $data['orderby'] : array($new_obj->getTable() . '.' . $new_obj->getIdName() => 'ASC'));
				if (!$this->getId())
					return new Orm_Collection();
				return $new_obj->{"findBy$name"}($where, $orderby, $limit);

				break;

			case 'n2n':
				try {
					$new_obj = new $data['class']();
				} catch (exception $e) {
					throw new Orm_Exception("Related class not found '{$data['class']}'");
				}

				if (!$data['table'])
					throw new Orm_Exception("No n2n table provided");

				if (!$this->getId())
					return new Orm_Collection();

				$orderby = ($order ? $this->_sort($order) : (isset($data['orderby']) ? $this->_sort($data['orderby']) : $new_obj->getTable() . '.' . $new_obj->getIdName() . ' ASC'));

				$namen2n = $data['field_foreign'];
				$name = $data['field'];

				$new_obj->getStmt()
					->from(
						$data['table'],
						'*'
					)
					->join(
						$new_obj->getTable(),
						$this->stmt->rquote($data['table']) . '.' . $this->stmt->rquote($this->escape($namen2n))
						. ' = ' . $this->stmt->rquote($new_obj->getTable()) . '.' . $this->stmt->rquote($new_obj->getIdName()),
						'*'
					)
					->where(
						$this->stmt->rquote($data['table']) . '.' . $this->stmt->rquote($this->escape($name)) . ' = ?', $this->getId()
					)
					->limit($limit);

				// add extra params
				$new_obj->addExtraStmtParams();

				// check for income params
				if ($where)
					$new_obj->getStmt()->where($where);
				if ($orderby)
					$new_obj->getStmt()->order($orderby);
				if ($limit)
					$new_obj->getStmt()->limit($limit);

				$r = $new_obj->findByQuery($new_obj->getStmt()->__toString());
				return $r;

				break;

			default:
				throw new Orm_Exception("Incorrect relation type '{$data['type']}'");
		}
	}

	//------------------------------------------
	//changing names
	/**
	 * Escape given value
	 *
	 * @param string $value
	 * @return string
	 */
	public function escape($value)
	{
		return $this->stmt->escape($value);
	}

	public function getLastInsertId()
	{
		if (PDO)
			return $this->db->lastInsertId();
		else
			return @mysql_insert_id($this->db);
	}

	//------------------------------------------
	/**
	 * @param $class
	 * @return ActiveRecord
	 * @throws Orm_Exception
	 */
	static public function useStatic($class)
	{
		if (class_exists($class))
			return new $class();
		else
			throw new Orm_Exception("Class '$class' not found");
	}

	/**
	 * @return ActiveRecord
	 * @throws Orm_Exception
	 */
	static public function DB()
	{
		return self::useStatic(get_called_class());
	}


	//reconnect if connection is closed
	public function _checkDB()
	{
		$db = Zend_Registry::get('db');
		Zend_Registry::set('dbpdo', $db);

		return $db;
	}

	//Magic
	public function __construct($id = 0)
	{
		$cache = Zend_Registry::get('cache');
		$this->_model = get_class($this);

		$data = null;

		if($this->_cached && $id){
			$cache_name = $this->getTable() . '_' . $id;
			$data = $cache->load($cache_name);
		}

		$this->db = $this->_checkDB();

		if (PDO)
			$this->db->setFetchMode(Zend_Db::FETCH_OBJ);

		$this->stmt = new Orm_Statement($this->db, $this->_model);
		$this->_defineRelations();

		if($id) Zend_Registry::set('obj', Zend_Registry::get('obj') + 1);

		if(!$data){
			if (!$this->_getDBFields())
				throw new Orm_Exception("Cannot init table {$this->getTable()}");
			if ($id) {
				$this->import($id);
				if($this->db_store)
				{
					$a = array('db'=>$this->db_store, 'data'=>$this->_store);
					foreach($a['data'] as $key=>$row)
						if(is_object($row))
							unset($a['data'][$key]);
					if($this->_cached && $id) {
						@$cache->save($a, $this->getTable() . '_' . $id);
					}
				}
			}
		} else {
			$this->_store = $data['data'];
			$this->db_store = $data['db'];
		}
	}


	public function __set($label, $object)
	{

		//can be used store[$label]
		$label = $this->_explodeCase($label);

		//check relations
		if (isset($this->_relations[$label]))
			$this->_setRelation($label, $object);
		else
			$this->_store[$label] = $object;

		return $this;
	}

	public function __get($label)
	{
		$data = @func_get_args();
		$label = $data[0];
		$params = @$data[1];

		//can be used store[$label]
		$label = $this->_explodeCase($label);

		//realtions
		if (array_key_exists($label, $this->_relations) &&
			(!@$this->_store[$label] ||
				((get_class(@$this->_store[$label]) == 'Orm_Collection') && !$this->_store[$label]->count())
				|| $params
			)
		)
			$this->_store[$label] = $this->_getRelation($label, $params);

		if (array_key_exists($label, $this->_store)) {
			$res = $this->_store[$label];
			//if($params)
			//$this->_store[$label] = null;
			return $res;
		}

		return null;
	}

	public function __call($method, $params)
	{
//Zend_Registry::set('call', Zend_Registry::get('call') + 1);
		//$this->log($method . ' -- ' . get_class(@$params[0]));
		if (strpos($method, 'set') === 0) {
			$prop = substr($method, 3);
			if ($prop == 'Id')
				$prop = $this->_id;

			if (array_key_exists($this->_explodeCase($prop), $this->_multilang)) {
				$prop = $this->_translatedName($this->_multilang[$this->_explodeCase($prop)]);
			}
			$this->$prop = $params[0];

			return true;
		}

		if (strpos($method, 'get') === 0) {
			$prop = substr($method, 3);
			if ($prop == 'Id') $prop = $this->_id;
			if (array_key_exists($this->_explodeCase($prop), $this->_multilang)) {
				$prop = $this->_translatedName($this->_multilang[$this->_explodeCase($prop)]);
			}

			return $this->__get($prop, $params);
		}

		if (strpos($method, 'findBy') === 0) {
			$prop = substr($method, 6);
			$prop_c = $this->_explodeCase($prop);
			if ($prop == 'Id') $prop = $this->_id;
			if (array_key_exists($prop_c, $this->db_store)) {
				return $this->findAll((!is_array(@$params[0]) ? array($prop_c => @$params[0]) : @$params[0]), @$params[1] /*sorting*/, @$params[2]/*limit*/, @$params[3]/*count*/);
			} else
				throw new Orm_Exception("Method not found findBy$prop");
		}

		//n2n and hasMany
		if (strpos($method, 'addTo') === 0) {
			$prop = substr($method, 5);
			$prop = $this->_explodeCase($prop);

			if (isset($this->_relations[$prop])) {
				$r = $this->_relations[$prop];
				switch (strtolower($r['type'])) {
					case 'hasmany':
						if (($class = get_class($params[0])) == $r['class']) {
							//save this object first
							if (!$this->getId())
								$this->save();

							$cnt = ($collection = $this->{'get' . $prop}(array(), array(), array(0, 1))) ? $collection->count() : 0;

							$name = $this->_implodeCase($r['field_foreign']);
							$params[0]->{"set$name"}($this->getId());
							//?? load this object to foreign object
							$params[0]->save();

							if ($cnt)
								$this->{'get' . $prop}()->add($params[0]);
							else
								$this->_store[$prop] = new Orm_Collection(array($params[0]));
							//$this->{'get' . $prop}()->add($params[0]);
						} else {
							throw new Orm_Exception("Wrong hasMany class supplied. Expected {$r['class']}, received $class");
						}
						break;

					case 'n2n':
						if (($class = get_class($params[0])) == $r['class']) {
							//save this object first
							if (!$this->getId())
								$this->save();
							//save foreign object
							if (!$params[0]->getId())
								$params[0]->save();

							$data = array(
								$r['field']         => $this->getId(),
								$r['field_foreign'] => $params[0]->getId()
							);
							//merge with additional data from n2n table
							if (@$params[1])
								$data = array_merge($data, $params[1]);

							$cnt = ($collection = $this->{'get' . $prop}(array(), array(), array(0, 1))) ? $collection->count() : 0;

							$set = $this->_set($data, $forced = 1);
							$this->stmt->insert($r['table'], $set)->execute();

							if ($cnt)
								$this->{'get' . $prop}()->add($params[0]);
							else
								$this->_store[$prop] = new Orm_Collection(array($params[0]));
						} else {
							throw new Orm_Exception("Wrong n2n class supplied. Expected {$r['class']}, received $class");
						}
						break;

					default:
						throw new Orm_Exception("$prop must have realtion hasMany or n2n");
				}
			} else {
				throw new Orm_Exception("$prop has no realtion");
			}
		}

		//n2n only
		if (strpos($method, 'unlink') === 0) {
			$prop = substr($method, 6);
			$prop = $this->_explodeCase($prop);
			if (isset($this->_relations[$prop])) {
				$r = $this->_relations[$prop];
				switch (strtolower($r['type'])) {
					case 'hasmany':
						if (isset($params[0])) {
							if (($class = get_class($params[0])) == $r['class']) {
								if (isset($r['onDelete']) && $r['onDelete'] == 'delete') {
									$values = $this->{"get$prop"}();
									foreach ($values as $value) {
										$value->destroy();
									}
								}
								$link = $this->_implodeCase($r['field_foreign']);
								$params[0]->{"set$link"}(new Orm_Collection());
								$params[0]->save();
							} else
								throw new Orm_Exception("Wrong n2n class supplied. Expected {$r['class']}, received $class");

							if ($collection = $this->{'get' . $prop}()) {
								foreach ($this->{'get' . $prop}() as $key => $obj) {
									if ($obj->getId() == $params[0]->getId()) {
										$this->{'get' . $prop}()->del($key);
										break;
									}
								}
							}
						} else {
							if ($r['onDelete'] == 'delete') {
								foreach ($this->{'get' . $prop}() as $obj) {
									$obj->destroy();
								}
							} else {
								$obj = new $r['class'];
								$data = array(
									$r['field_foreign'] => null,
								);
								$set = $this->_set($data, $forced = 1);
								$data = array(
									$r['field_foreign'] => $this->getId(),
								);
								$this->stmt->update($obj->getTable(), $set, $data)->execute();
								/*$where = $this->_where($data);
								$q = 'UPDATE ' . $this->getTable($r['table']) . ' SET ' . $set  . ' WHERE ' . $where;
								$this->query($q);*/
							}

							$this->_store[$prop] = new Orm_Collection();
						}
						break;
					case 'n2n':
						if (isset($params[0])) {
							if (($class = get_class($params[0])) == $r['class']) {
								if (isset($r['onDelete']) && $r['onDelete'] == 'delete') {
									$params[0]->destroy();
								}
								//if foreign and this saved is saved
								if ($params[0]->getId() && $this->getId()) {
									$data = array(
										$r['field']         => $this->getId(),
										$r['field_foreign'] => $params[0]->getId()
									);

									$this->stmt->delete($r['table'], $data)->execute();
								}
							} else
								throw new Orm_Exception("Wrong n2n class supplied. Expected {$r['class']}, received $class");

							if ($collection = $this->{'get' . $prop}()) {
								foreach ($collection as $key => $obj) {
									if ($obj->getId() == $params[0]->getId()) {
										$this->{'get' . $prop}()->del($key);
										break;
									}
								}
							}
						} else {
							if ($r['onDelete'] == 'delete') {
								foreach ($this->{'get' . $prop}() as $obj) {
									$obj->destroy();
								}
							} else {
								$data = array(
									$r['field'] => $this->getId(),
								);
								//$where = $this->_where($data);
								$this->stmt->delete($r['table'], $data)->execute();
							}
							$this->{'set' . $prop}(null);
						}
						break;
					default:
						throw new Orm_Exception("$prop should have n2n or hasMany object");
						break;
				}
				return true;
			} else
				throw new Orm_Exception("$prop does not have linked object");
		}

		return false;
	}


	//------------------------------------------

	public function debug($var)
	{
		MyDebug::dump($var);
	}

	public function log($data)
	{
		//echo ' - ' . $data . "<br/>\n";
	}

}
