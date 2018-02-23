<?php
namespace Think\Model;

use Think\Model;

class MergeModel extends Model
{
	protected $modelList = array();
	protected $masterModel = '';
	protected $joinType = 'INNER';
	protected $fk = '';
	protected $mapFields = array();

	public function __construct($name = '', $tablePrefix = '', $connection = '')
	{
		parent::__construct($name, $tablePrefix, $connection);
		if (empty($this->fields) && !empty($this->modelList)) {
			$fields = array();
			foreach ($this->modelList as $model) {
				$result = $this->db->getFields(M($model)->getTableName());
				$_fields = array_keys($result);
				$fields = array_merge($fields, $_fields);
			}
			$this->fields = $fields;
		}
		if (empty($this->masterModel) && !empty($this->modelList)) {
			$this->masterModel = $this->modelList[0];
		}
		$this->pk = M($this->masterModel)->getPk();
		if (empty($this->fk)) {
			$this->fk = strtolower($this->masterModel) . '_id';
		}
	}

	public function getTableName()
	{
		if (empty($this->trueTableName)) {
			$tableName = array();
			$models = $this->modelList;
			foreach ($models as $model) {
				$tableName[] = M($model)->getTableName() . ' ' . $model;
			}
			$this->trueTableName = implode(',', $tableName);
		}
		return $this->trueTableName;
	}

	protected function _checkTableInfo()
	{
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
		$this->startTrans();
		$result = M($this->masterModel)->strict(false)->add($data);
		if ($result) {
			$data[$this->fk] = $result;
			$models = $this->modelList;
			array_shift($models);
			foreach ($models as $model) {
				$res = M($model)->strict(false)->add($data);
				if (!$res) {
					$this->rollback();
					return false;
				}
			}
			$this->commit();
		} else {
			$this->rollback();
			return false;
		}
		return $result;
	}

	protected function _facade($data)
	{
		if (!empty($this->fields)) {
			if (!empty($this->options['field'])) {
				$fields = $this->options['field'];
				unset($this->options['field']);
				if (is_string($fields)) {
					$fields = explode(',', $fields);
				}
			} else {
				$fields = $this->fields;
			}
			foreach ($data as $key => $val) {
				if (!in_array($key, $fields, true)) {
					unset($data[$key]);
				} elseif (array_key_exists($key, $this->mapFields)) {
					$data[$this->mapFields[$key]] = $val;
					unset($data[$key]);
				}
			}
		}
		if (!empty($this->options['filter'])) {
			$data = array_map($this->options['filter'], $data);
			unset($this->options['filter']);
		}
		$this->_before_write($data);
		return $data;
	}

	public function save($data = '', $options = array())
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
		if (empty($data)) {
			$this->error = L('_DATA_TYPE_INVALID_');
			return false;
		}
		$pk = $this->pk;
		if (isset($data[$pk])) {
			$where[$pk] = $data[$pk];
			$options['where'] = $where;
			unset($data[$pk]);
		}
		$options['join'] = '';
		$options = $this->_parseOptions($options);
		$options['table'] = $this->getTableName();
		if (is_array($options['where']) && isset($options['where'][$pk])) {
			$pkValue = $options['where'][$pk];
		}
		if (false === $this->_before_update($data, $options)) {
			return false;
		}
		$result = $this->db->update($data, $options);
		if (false !== $result) {
			if (isset($pkValue)) $data[$pk] = $pkValue;
			$this->_after_update($data, $options);
		}
		return $result;
	}

	public function delete($options = array())
	{
		$pk = $this->pk;
		if (empty($options) && empty($this->options['where'])) {
			if (!empty($this->data) && isset($this->data[$pk])) return $this->delete($this->data[$pk]); else return false;
		}
		if (is_numeric($options) || is_string($options)) {
			if (strpos($options, ',')) {
				$where[$pk] = array('IN', $options);
			} else {
				$where[$pk] = $options;
			}
			$options = array();
			$options['where'] = $where;
		}
		$options['join'] = '';
		$options = $this->_parseOptions($options);
		if (empty($options['where'])) {
			return false;
		}
		if (is_array($options['where']) && isset($options['where'][$pk])) {
			$pkValue = $options['where'][$pk];
		}
		$options['table'] = implode(',', $this->modelList);
		$options['using'] = $this->getTableName();
		if (false === $this->_before_delete($options)) {
			return false;
		}
		$result = $this->db->delete($options);
		if (false !== $result) {
			$data = array();
			if (isset($pkValue)) $data[$pk] = $pkValue;
			$this->_after_delete($data, $options);
		}
		return $result;
	}

	protected function _options_filter(&$options)
	{
		if (!isset($options['join'])) {
			$models = $this->modelList;
			array_shift($models);
			foreach ($models as $model) {
				$options['join'][] = $this->joinType . ' JOIN ' . M($model)->getTableName() . ' ' . $model . ' ON ' . $this->masterModel . '.' . $this->pk . ' = ' . $model . '.' . $this->fk;
			}
		}
		$options['table'] = M($this->masterModel)->getTableName() . ' ' . $this->masterModel;
		$options['field'] = $this->checkFields(isset($options['field']) ? $options['field'] : '');
		if (isset($options['group'])) $options['group'] = $this->checkGroup($options['group']);
		if (isset($options['where'])) $options['where'] = $this->checkCondition($options['where']);
		if (isset($options['order'])) $options['order'] = $this->checkOrder($options['order']);
	}

	protected function checkCondition($where)
	{
		if (is_array($where)) {
			$view = array();
			foreach ($where as $name => $value) {
				if (array_key_exists($name, $this->mapFields)) {
					$view[$this->mapFields[$name]] = $value;
					unset($where[$name]);
				}
			}
			$where = array_merge($where, $view);
		}
		return $where;
	}

	protected function checkOrder($order = '')
	{
		if (is_string($order) && !empty($order)) {
			$orders = explode(',', $order);
			$_order = array();
			foreach ($orders as $order) {
				$array = explode(' ', trim($order));
				$field = $array[0];
				$sort = isset($array[1]) ? $array[1] : 'ASC';
				if (array_key_exists($field, $this->mapFields)) {
					$field = $this->mapFields[$field];
				}
				$_order[] = $field . ' ' . $sort;
			}
			$order = implode(',', $_order);
		}
		return $order;
	}

	protected function checkGroup($group = '')
	{
		if (!empty($group)) {
			$groups = explode(',', $group);
			$_group = array();
			foreach ($groups as $field) {
				if (array_key_exists($field, $this->mapFields)) {
					$field = $this->mapFields[$field];
				}
				$_group[] = $field;
			}
			$group = implode(',', $_group);
		}
		return $group;
	}

	protected function checkFields($fields = '')
	{
		if (empty($fields) || '*' == $fields) {
			$fields = $this->fields;
		}
		if (!is_array($fields)) $fields = explode(',', $fields);
		$array = array();
		foreach ($fields as $field) {
			if (array_key_exists($field, $this->mapFields)) {
				$array[] = $this->mapFields[$field] . ' AS ' . $field;
			} else {
				$array[] = $field;
			}
		}
		$fields = implode(',', $array);
		return $fields;
	}

	public function getDbFields()
	{
		return $this->fields;
	}
}