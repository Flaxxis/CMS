<?php
/**
 * Class Orm_Statement - select statement class for orm package
 * 
 * @package Orm
 */
class Orm_Statement extends Zend_Db_Select
{
	protected $_query = null;
	protected $_class_name = 'ActiveRecord';
	
	private $pdo_statement;
	private $sqllog = null;
	
	//if no PDO
	private $_db = null;
	private $_last_result = null;
	
	/**
     * Class constructor
     *
     * @param Zend_Db_Adapter_Abstract $adapter
     */
    public function __construct(/*Zend_Db_Adapter_Abstract*/ $adapter, $class_name = 'ActiveRecord')
    {
    	//if(PDO)
    		parent::__construct(Zend_Registry::get('dbpdo'));
    	//else
    		$this->_db = $adapter;
        /*$this->_adapter = $adapter;
        $this->_parts = self::$_partsInit;*/
		if(Zend_Registry::isRegistered('SQLLogger'))
			$this->sqllog = Zend_Registry::get('SQLLogger');
        $this->_class_name = $class_name;
    }
    
	/**
	 * Add rquotes to given string
	 *
	 * @param string $value
	 * @return string
	 */
	public function rquote($value)
	{
		$parts = explode('.', $value);
		foreach ($parts as &$part)
		{
			$part = '`' . $part . '`';
		}
		$field = implode('.', $parts);	
		return $field;
	}
	
	/**
	 * Escape given value
	 *
	 * @param string $value
	 * @return string
	 */
	public static function escape($value)
	{
		if(PDO)
			return addcslashes($value, "\000\n\r\\'\"\032");
		else
			return mysql_real_escape_string(stripslashes($value));
	}
	
	/**
	 * Add quotes to given string
	 *
	 * @param string $value
	 * @return string
	 */
	public function quote($value)
	{
		return "'" . $value . "'";
	}
	
	/**
	 * Set query to execute
	 *
	 * @param string $query
	 * @return Statement This Statement object.
	 */
	public function setQuery($query)
	{
		$this->_query = $query;
		return $this;
	}
	
	/**
	 * Execute object
	 *
	 * @return int Number of affected rows
	 */
	public function execute()
	{
		$query = ($this->_query ? $this->_query : $this->__toString() ). ';';

		//log query
		if($this->sqllog)
			$this->sqllog->log($this->_class_name, $query);
		try
		{
			if(PDO)
			{
				$this->pdo_statement = $this->_adapter->prepare($query);
				$this->pdo_statement->execute();
			}
			else
			{
				$this->_last_result = @mysql_query($query,$this->_db);
			}
		}
		catch (Exception $e)
		{
			$message = $e->getMessage() . " (" . $query .  " - " . $this->_class_name . ")";
			throw new Orm_Exception($message);
		}
		$this->reset();
		if($this->sqllog)
			$this->sqllog->done($this->getAffectedRows());
		return $this->getAffectedRows();
	}	

	/**
	 * Get column based on executed query
	 *
	 * @return object
	 */
	public function getColumn($num = 0)
	{
		$this->execute();
		if(PDO)
			return $this->pdo_statement->fetchAll(PDO::FETCH_COLUMN, $num);
		else
			throw new Orm_Exception('G');
	}
	
	/**
	 * Return row based on executed query
	 *
	 * @return object Selected row
	 */
	public function getRow()
	{
		$this->execute();
		if (PDO)
		{
			$res = $this->pdo_statement->fetchAll();
			return @$res[0];
		}
		else
		{
			return @mysql_fetch_assoc($this->_last_result);
		}		
	}

	/**
	 * Return value based on executed query
	 *
	 * @return string Selected value
	 */
	public function getVar($var_name = 0)
	{
		$this->execute();
		if(PDO)
		{
			$res = $this->pdo_statement->fetchAll();
			return @$res[0]->$var_name;
		}
		else
		{
			$res = @mysql_fetch_row($this->_last_result);
			return $res[0];
		}
	}
	
	/**
	 * Return rows based on executed query
	 *
	 * @return array Selected rows
	 */
	public function getResults()
	{
		$this->execute();
		if(PDO)
			return $this->pdo_statement->fetchAll();
		else {
			$ret = array();
			while ( $row = @mysql_fetch_object($this->_last_result) )
				$ret[] = $row;
			return $ret;
		}
	}
	
	/**
	 * Return number of affected rows
	 *
	 * @return int Number of affected rows
	 */
	public function getAffectedRows()
	{
		if(PDO)
			return $this->pdo_statement->rowCount();
		else
			return @mysql_affected_rows();
	}
	
	/**
     * Clear parts of the Statement object, or an individual part.
     *
     * @param string $part OPTIONAL
     * @return Orm_Statement
     */
	public function reset($part = null)
	{
		parent::reset($part);
		$this->_query = null;
		return $this;
	}
	
	/**
     * Adds a WHERE condition to the query by AND.
     *
     * If a value is passed as the second param, it will be quoted
     * and replaced into the condition wherever a question-mark
     * appears. Array values are quoted and comma-separated.
     * 
     * If condition is array, then each item will be handled as separate where
     *
     * @param string   $conditions  The WHERE condition.
     * @param string   $value OPTIONAL A single value to quote into the condition.
     * @return Statement This Statement object.
     */
	public function where($conditions, $value = null, $type = null)
    {
    	if (is_array($conditions))
    	{
    		foreach ($conditions as $s_key => $s_value)
    		{
    			if ($s_key && !is_numeric($s_key))
    			{
					if ($s_value === null)
						$expr = $this->rquote($s_key) . ' IS NULL';
					else
						$expr = $this->rquote($s_key) . ' = ?';
	    			parent::where($expr, $s_value);
    			}
    			else 
    				parent::where($s_value);
    		}
    	}
    	else 
    	{
    		parent::where($conditions, $value);
    	}

        return $this;
    }
    
    /**
     * Build delete statement 
     *
     * @param string $table
     * @param string|array $conditions
     * @return Statement This Statement object.
     */
    public function delete($table, $conditions)
    {
    	$this->_query = 'DELETE FROM ' . $this->rquote($table) . ' WHERE ' . $this->_buildWhere($conditions);
    	return $this;
    }
    
    /**
     * Build update statement 
     *
     * @param string $table
     * @param string $data
     * @param string|array $conditions
     * @return Statement This Statement object.
     */
    public function update($table, $data, $conditions)
    {
    	$this->_query = 'UPDATE ' . $this->rquote($table) . ' SET ' . $data . ' WHERE ' . $this->_buildWhere($conditions);
    	return $this;
    }
    
    /**
     * Build insert statement 
     *
     * @param string $table
     * @param string $data
     * @return Statement This Statement object.
     */
    public function insert($table, $data)
    {
    	$this->_query = 'INSERT INTO ' . $this->rquote($table) . ' SET ' . $data;
    	return $this;
    }
    
    /**
     * Build where statement (for DELETE, UPDATE only)
     *
     * @param string|array $conditions
     * @return string Where statement
     */
    protected function _buildWhere($conditions)
    {   	
		$where = $this->_where('1 = 1');
    	if (is_array($conditions))
    	{
    		foreach ($conditions as $s_key => $s_value)
    		{
    			if ($s_key && !is_numeric($s_key))
    			{
	    			$expr = $this->rquote($s_key) . ' = ?';
	    			$where .= ' AND ' . $this->_where($expr, $s_value);
    			}
    			else 
    				$where .= ' AND ' . $this->_where($s_value);
    		}
    	}
    	else 
    	{
    		$where .= ' AND ' . $this->_where($conditions);
    	}

    	return $where;
    }
    
    /*protected function _buildData($data)
    {
    	if (!$data)
    		throw new Orm_Exception('Empty data given');
    	
    	foreach ($data as $key => $value)
    	{
    		
    	}
    }*/
	
	/**
     * Sets a limit count and offset to the query.
     *
     * @param mixed $params Limit params.
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function limit($params = 1, $offset = null)
    {
        if(!is_array($params))
        {
            $count = $params;
            $offset = 0;
        }
        elseif(count($params) == 1)
        {
            $count = $params[0];
            $offset = 0;
        }
        else
        {
            $count = $params[1];
            $offset = $params[0];
        }
        
        $this->_parts[self::LIMIT_COUNT]  = (int) $count;
        $this->_parts[self::LIMIT_OFFSET] = (int) $offset;
        return $this;
    }	
}