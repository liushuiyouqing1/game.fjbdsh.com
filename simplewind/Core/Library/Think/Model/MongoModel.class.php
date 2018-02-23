<?php
namespace Think\Model;

use Think\Model;

class MongoModel extends Model
{
	const TYPE_OBJECT = 1;
	const TYPE_INT = 2;
	const TYPE_STRING = 3;
	protected $pk = '_id';
	protected $_idType = self::TYPE_OBJECT;
	protected $_autoinc = true;
	protected $autoCheckFields = false;
	protected $methods = array('table', 'order', 'auto', 'filter', 'validate');

	public function __call($method, $args)
	{
		if (in_array(strtolower($method), $this->methods, true)) {
			$this->options[strtolower($method)] = $args[0];
			return $this;
		} elseif (strtolower(substr($method, 0, 5)) == 'getby') {
			$field = parse_name(substr($method, 5));
			$where[$field] = $args[0];
			return $this->where($where)->find();
		} elseif (strtolower(substr($method, 0, 10)) == 'getfieldby') {
			$name = parse_name(substr($method, 10));
			$where[$name] = $args[0];
			return $this->where($where)->getField($args[1]);
		} else {
			E(__CLASS__ . ':' . $method . L('_METHOD_NOT_EXIST_'));
			return;
		}
	}

	public function flush()
	{
		$fields = $this->db->getFields();
		if (!$fields) {
			return false;
		}
		$this->fields = array_keys($fields);
		foreach ($fields as $key => $val) {
			$type[$key] = $val['type'];
		}
		if (C('DB_FIELDTYPE_CHECK')) $this->fields['_type'] = $type;
		if (C('DB_FIELDS_CACHE')) {
			$db = $this->dbName ? $this->dbName : C('DB_NAME');
			F('_fields/' . $db . '.' . $this->name, $this->fields);
		}
	}

	protected function _before_write(&$data)
	{
		$pk = $this->getPk();
		if (isset($data[$pk]) && $this->_idType == self::TYPE_OBJECT) {
			$data[$pk] = new \MongoId($data[$pk]);
		}
	}

	public function count()
	{
		$options = $this->_parseOptions();
		return $this->db->count($options);
	}

	public function distinct($field, $where = array())
	{
		$this->options = $this->_parseOptions();
		$this->options['where'] = array_merge((array)$this->options['where'], $where);
		$command = array("distinct" => $this->options['table'], "key" => $field, "query" => $this->options['where']);
		$result = $this->command($command);
		return isset($result['values']) ? $result['values'] : false;
	}

	public function getMongoNextId($pk = '')
	{
		if (empty($pk)) {
			$pk = $this->getPk();
		}
		return $this->db->getMongoNextId($pk);
	}

	public function add($data = '', $options = array(), $replace = false)
	{
		if (empty($data)) {
			if (!empty($this->data)) {
				$data = $this->data;
				$this->data = array();
			} else {
				$this->error = L('_DATA_TYPE_INVALID_');
				return false;
			}
		}
		$options = $this->_parseOptions($options);
		$data = $this->_facade($data);
		if (false === $this->_before_insert($data, $options)) {
			return false;
		}
		$result = $this->db->insert($data, $options, $replace);
		if (false !== $result) {
			$this->_after_insert($data, $options);
			if (isset($data[$this->getPk()])) {
				return $data[$this->getPk()];
			}
		}
		return $result;
	}

	protected function _before_insert(&$data, $options)
	{
		if ($this->_autoinc && $this->_idType == self::TYPE_INT) {
			$pk = $this->getPk();
			if (!isset($data[$pk])) {
				$data[$pk] = $this->db->getMongoNextId($pk);
			}
		}
	}

	public function clear()
	{
		return $this->db->clear();
	}

	protected function _after_select(&$resultSet, $options)
	{
		array_walk($resultSet, array($this, 'checkMongoId'));
	}

	protected function checkMongoId(&$result)
	{
		if (is_object($result['_id'])) {
			$result['_id'] = $result['_id']->__toString();
		}
		return $result;
	}

	protected function _options_filter(&$options)
	{
		$id = $this->getPk();
		if (isset($options['where'][$id]) && is_scalar($options['where'][$id]) && $this->_idType == self::TYPE_OBJECT) {
			$options['where'][$id] = new \MongoId($options['where'][$id]);
		}
	}

	public function find($options = array())
	{
		if (is_numeric($options) || is_string($options)) {
			$id = $this->getPk();
			$where[$id] = $options;
			$options = array();
			$options['where'] = $where;
		}
		$options = $this->_parseOptions($options);
		$result = $this->db->find($options);
		if (false === $result) {
			return false;
		}
		if (empty($result)) {
			return null;
		} else {
			$this->checkMongoId($result);
		}
		$this->data = $result;
		$this->_after_find($this->data, $options);
		return $this->data;
	}

	public function setInc($field, $step = 1)
	{
		return $this->setField($field, array('inc', $step));
	}

	public function setDec($field, $step = 1)
	{
		return $this->setField($field, array('inc', '-' . $step));
	}

	public function getField($field, $sepa = null)
	{
		$options['field'] = $field;
		$options = $this->_parseOptions($options);
		if (strpos($field, ',')) {
			if (is_numeric($sepa)) {
				$options['limit'] = $sepa;
				$sepa = null;
			}
			$resultSet = $this->db->select($options);
			if (!empty($resultSet)) {
				$_field = explode(',', $field);
				$field = array_keys($resultSet[0]);
				$key = array_shift($field);
				$key2 = array_shift($field);
				$cols = array();
				$count = count($_field);
				foreach ($resultSet as $result) {
					$name = $result[$key];
					if (2 == $count) {
						$cols[$name] = $result[$key2];
					} else {
						$cols[$name] = is_null($sepa) ? $result : implode($sepa, $result);
					}
				}
				return $cols;
			}
		} else {
			if (true !== $sepa) {
				$options['limit'] = is_numeric($sepa) ? $sepa : 1;
			}
			$result = $this->db->select($options);
			if (!empty($result)) {
				if (1 == $options['limit']) {
					$result = reset($result);
					return $result[$field];
				}
				foreach ($result as $val) {
					$array[] = $val[$field];
				}
				return $array;
			}
		}
		return null;
	}

	public function command($command, $options = array())
	{
		$options = $this->_parseOptions($options);
		return $this->db->command($command, $options);
	}

	public function mongoCode($code, $args = array())
	{
		return $this->db->execute($code, $args);
	}

	protected function _after_db()
	{
		$this->db->switchCollection($this->getTableName(), $this->dbName ? $this->dbName : C('db_name'));
	}

	public function getTableName()
	{
		if (empty($this->trueTableName)) {
			$tableName = !empty($this->tablePrefix) ? $this->tablePrefix : '';
			if (!empty($this->tableName)) {
				$tableName .= $this->tableName;
			} else {
				$tableName .= parse_name($this->name);
			}
			$this->trueTableName = strtolower($tableName);
		}
		return $this->trueTableName;
	}

	public function group($key, $init, $reduce, $option = array())
	{
		$option = $this->_parseOptions($option);
		if (isset($option['where'])) $option['condition'] = array_merge((array)$option['condition'], $option['where']);
		return $this->db->group($key, $init, $reduce, $option);
	}

	public function getLastError()
	{
		return $this->db->command(array('getLastError' => 1));
	}

	public function status()
	{
		$option = $this->_parseOptions();
		return $this->db->command(array('collStats' => $option['table']));
	}

	public function getDB()
	{
		return $this->db->getDB();
	}

	public function getCollection()
	{
		return $this->db->getCollection();
	}
}