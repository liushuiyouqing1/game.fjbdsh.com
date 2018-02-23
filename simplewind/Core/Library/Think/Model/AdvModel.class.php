<?php
namespace Think\Model;

use Think\Model;

class AdvModel extends Model
{
	protected $optimLock = 'lock_version';
	protected $returnType = 'array';
	protected $blobFields = array();
	protected $blobValues = null;
	protected $serializeField = array();
	protected $readonlyField = array();
	protected $_filter = array();
	protected $partition = array();

	public function __construct($name = '', $tablePrefix = '', $connection = '')
	{
		if ('' !== $name || is_subclass_of($this, 'AdvModel')) {
		} else {
			$this->autoCheckFields = false;
		}
		parent::__construct($name, $tablePrefix, $connection);
	}

	public function __call($method, $args)
	{
		if (strtolower(substr($method, 0, 3)) == 'top') {
			$count = substr($method, 3);
			array_unshift($args, $count);
			return call_user_func_array(array(&$this, 'topN'), $args);
		} else {
			return parent::__call($method, $args);
		}
	}

	protected function _facade($data)
	{
		$data = $this->serializeField($data);
		return parent::_facade($data);
	}

	protected function _after_find(&$result, $options = '')
	{
		$this->checkSerializeField($result);
		$this->getBlobFields($result);
		$result = $this->getFilterFields($result);
		$this->cacheLockVersion($result);
	}

	protected function _after_select(&$resultSet, $options = '')
	{
		$resultSet = $this->checkListSerializeField($resultSet);
		$resultSet = $this->getListBlobFields($resultSet);
		$resultSet = $this->getFilterListFields($resultSet);
	}

	protected function _before_insert(&$data, $options = '')
	{
		$data = $this->recordLockVersion($data);
		$data = $this->checkBlobFields($data);
		$data = $this->setFilterFields($data);
	}

	protected function _after_insert($data, $options)
	{
		$this->saveBlobFields($data);
	}

	protected function _before_update(&$data, $options = '')
	{
		$pk = $this->getPK();
		if (isset($options['where'][$pk])) {
			$id = $options['where'][$pk];
			if (!$this->checkLockVersion($id, $data)) {
				return false;
			}
		}
		$data = $this->checkBlobFields($data);
		$data = $this->checkReadonlyField($data);
		$data = $this->setFilterFields($data);
	}

	protected function _after_update($data, $options)
	{
		$this->saveBlobFields($data);
	}

	protected function _after_delete($data, $options)
	{
		$this->delBlobFields($data);
	}

	protected function recordLockVersion($data)
	{
		if ($this->optimLock && !isset($data[$this->optimLock])) {
			if (in_array($this->optimLock, $this->fields, true)) {
				$data[$this->optimLock] = 0;
			}
		}
		return $data;
	}

	protected function cacheLockVersion($data)
	{
		if ($this->optimLock) {
			if (isset($data[$this->optimLock]) && isset($data[$this->getPk()])) {
				$_SESSION[$this->name . '_' . $data[$this->getPk()] . '_lock_version'] = $data[$this->optimLock];
			}
		}
	}

	protected function checkLockVersion($id, &$data)
	{
		$identify = $this->name . '_' . $id . '_lock_version';
		if ($this->optimLock && isset($_SESSION[$identify])) {
			$lock_version = $_SESSION[$identify];
			$vo = $this->field($this->optimLock)->find($id);
			$_SESSION[$identify] = $lock_version;
			$curr_version = $vo[$this->optimLock];
			if (isset($curr_version)) {
				if ($curr_version > 0 && $lock_version != $curr_version) {
					$this->error = L('_RECORD_HAS_UPDATE_');
					return false;
				} else {
					$save_version = $data[$this->optimLock];
					if ($save_version != $lock_version + 1) {
						$data[$this->optimLock] = $lock_version + 1;
					}
					$_SESSION[$identify] = $lock_version + 1;
				}
			}
		}
		return true;
	}

	public function topN($count, $options = array())
	{
		$options['limit'] = $count;
		return $this->select($options);
	}

	public function getN($position = 0, $options = array())
	{
		if ($position >= 0) {
			$options['limit'] = $position . ',1';
			$list = $this->select($options);
			return $list ? $list[0] : false;
		} else {
			$list = $this->select($options);
			return $list ? $list[count($list) - abs($position)] : false;
		}
	}

	public function first($options = array())
	{
		return $this->getN(0, $options);
	}

	public function last($options = array())
	{
		return $this->getN(-1, $options);
	}

	public function returnResult($data, $type = '')
	{
		if ('' === $type) $type = $this->returnType;
		switch ($type) {
			case 'array' :
				return $data;
			case 'object':
				return (object)$data;
			default:
				if (class_exists($type)) return new $type($data); else E(L('_CLASS_NOT_EXIST_') . ':' . $type);
		}
	}

	protected function getFilterFields(&$result)
	{
		if (!empty($this->_filter)) {
			foreach ($this->_filter as $field => $filter) {
				if (isset($result[$field])) {
					$fun = $filter[1];
					if (!empty($fun)) {
						if (isset($filter[2]) && $filter[2]) {
							$result[$field] = call_user_func($fun, $result);
						} else {
							$result[$field] = call_user_func($fun, $result[$field]);
						}
					}
				}
			}
		}
		return $result;
	}

	protected function getFilterListFields(&$resultSet)
	{
		if (!empty($this->_filter)) {
			foreach ($resultSet as $key => $result) $resultSet[$key] = $this->getFilterFields($result);
		}
		return $resultSet;
	}

	protected function setFilterFields($data)
	{
		if (!empty($this->_filter)) {
			foreach ($this->_filter as $field => $filter) {
				if (isset($data[$field])) {
					$fun = $filter[0];
					if (!empty($fun)) {
						if (isset($filter[2]) && $filter[2]) {
							$data[$field] = call_user_func($fun, $data);
						} else {
							$data[$field] = call_user_func($fun, $data[$field]);
						}
					}
				}
			}
		}
		return $data;
	}

	protected function returnResultSet(&$resultSet, $type = '')
	{
		foreach ($resultSet as $key => $data) $resultSet[$key] = $this->returnResult($data, $type);
		return $resultSet;
	}

	protected function checkBlobFields(&$data)
	{
		if (!empty($this->blobFields)) {
			foreach ($this->blobFields as $field) {
				if (isset($data[$field])) {
					if (isset($data[$this->getPk()])) $this->blobValues[$this->name . '/' . $data[$this->getPk()] . '_' . $field] = $data[$field]; else $this->blobValues[$this->name . '/@?id@_' . $field] = $data[$field];
					unset($data[$field]);
				}
			}
		}
		return $data;
	}

	protected function getListBlobFields(&$resultSet, $field = '')
	{
		if (!empty($this->blobFields)) {
			foreach ($resultSet as $key => $result) {
				$result = $this->getBlobFields($result, $field);
				$resultSet[$key] = $result;
			}
		}
		return $resultSet;
	}

	protected function getBlobFields(&$data, $field = '')
	{
		if (!empty($this->blobFields)) {
			$pk = $this->getPk();
			$id = $data[$pk];
			if (empty($field)) {
				foreach ($this->blobFields as $field) {
					$identify = $this->name . '/' . $id . '_' . $field;
					$data[$field] = F($identify);
				}
				return $data;
			} else {
				$identify = $this->name . '/' . $id . '_' . $field;
				return F($identify);
			}
		}
	}

	protected function saveBlobFields(&$data)
	{
		if (!empty($this->blobFields)) {
			foreach ($this->blobValues as $key => $val) {
				if (strpos($key, '@?id@')) $key = str_replace('@?id@', $data[$this->getPk()], $key);
				F($key, $val);
			}
		}
	}

	protected function delBlobFields(&$data, $field = '')
	{
		if (!empty($this->blobFields)) {
			$pk = $this->getPk();
			$id = $data[$pk];
			if (empty($field)) {
				foreach ($this->blobFields as $field) {
					$identify = $this->name . '/' . $id . '_' . $field;
					F($identify, null);
				}
			} else {
				$identify = $this->name . '/' . $id . '_' . $field;
				F($identify, null);
			}
		}
	}

	protected function serializeField(&$data)
	{
		if (!empty($this->serializeField)) {
			foreach ($this->serializeField as $key => $val) {
				if (empty($data[$key])) {
					$serialize = array();
					foreach ($val as $name) {
						if (isset($data[$name])) {
							$serialize[$name] = $data[$name];
							unset($data[$name]);
						}
					}
					if (!empty($serialize)) {
						$data[$key] = serialize($serialize);
					}
				}
			}
		}
		return $data;
	}

	protected function checkSerializeField(&$result)
	{
		if (!empty($this->serializeField)) {
			foreach ($this->serializeField as $key => $val) {
				if (isset($result[$key])) {
					$serialize = unserialize($result[$key]);
					foreach ($serialize as $name => $value) $result[$name] = $value;
					unset($serialize, $result[$key]);
				}
			}
		}
		return $result;
	}

	protected function checkListSerializeField(&$resultSet)
	{
		if (!empty($this->serializeField)) {
			foreach ($this->serializeField as $key => $val) {
				foreach ($resultSet as $k => $result) {
					if (isset($result[$key])) {
						$serialize = unserialize($result[$key]);
						foreach ($serialize as $name => $value) $result[$name] = $value;
						unset($serialize, $result[$key]);
						$resultSet[$k] = $result;
					}
				}
			}
		}
		return $resultSet;
	}

	protected function checkReadonlyField(&$data)
	{
		if (!empty($this->readonlyField)) {
			foreach ($this->readonlyField as $key => $field) {
				if (isset($data[$field])) unset($data[$field]);
			}
		}
		return $data;
	}

	public function patchQuery($sql = array())
	{
		if (!is_array($sql)) return false;
		$this->startTrans();
		try {
			foreach ($sql as $_sql) {
				$result = $this->execute($_sql);
				if (false === $result) {
					$this->rollback();
					return false;
				}
			}
			$this->commit();
		} catch (ThinkException $e) {
			$this->rollback();
		}
		return true;
	}

	public function getPartitionTableName($data = array())
	{
		if (isset($data[$this->partition['field']])) {
			$field = $data[$this->partition['field']];
			switch ($this->partition['type']) {
				case 'id':
					$step = $this->partition['expr'];
					$seq = floor($field / $step) + 1;
					break;
				case 'year':
					if (!is_numeric($field)) {
						$field = strtotime($field);
					}
					$seq = date('Y', $field) - $this->partition['expr'] + 1;
					break;
				case 'mod':
					$seq = ($field % $this->partition['num']) + 1;
					break;
				case 'md5':
					$seq = (ord(substr(md5($field), 0, 1)) % $this->partition['num']) + 1;
					break;
				default :
					if (function_exists($this->partition['type'])) {
						$fun = $this->partition['type'];
						$seq = (ord(substr($fun($field), 0, 1)) % $this->partition['num']) + 1;
					} else {
						$seq = (ord($field{0}) % $this->partition['num']) + 1;
					}
			}
			return $this->getTableName() . '_' . $seq;
		} else {
			$tableName = array();
			for ($i = 0; $i < $this->partition['num']; $i++) $tableName[] = 'SELECT * FROM ' . $this->getTableName() . '_' . ($i + 1);
			$tableName = '( ' . implode(" UNION ", $tableName) . ') AS ' . $this->name;
			return $tableName;
		}
	}
}