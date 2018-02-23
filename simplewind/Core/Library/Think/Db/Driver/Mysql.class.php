<?php
namespace Think\Db\Driver;

use Think\Db\Driver;

class Mysql extends Driver
{
	protected function parseDsn($config)
	{
		$dsn = 'mysql:dbname=' . $config['database'] . ';host=' . $config['hostname'];
		if (!empty($config['hostport'])) {
			$dsn .= ';port=' . $config['hostport'];
		} elseif (!empty($config['socket'])) {
			$dsn .= ';unix_socket=' . $config['socket'];
		}
		if (!empty($config['charset'])) {
			$this->options[\PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $config['charset'];
			$dsn .= ';charset=' . $config['charset'];
		}
		return $dsn;
	}

	public function getFields($tableName)
	{
		$this->initConnect(true);
		list($tableName) = explode(' ', $tableName);
		if (strpos($tableName, '.')) {
			list($dbName, $tableName) = explode('.', $tableName);
			$sql = 'SHOW COLUMNS FROM `' . $dbName . '`.`' . $tableName . '`';
		} else {
			$sql = 'SHOW COLUMNS FROM `' . $tableName . '`';
		}
		$result = $this->query($sql);
		$info = array();
		if ($result) {
			foreach ($result as $key => $val) {
				if (\PDO::CASE_LOWER != $this->_linkID->getAttribute(\PDO::ATTR_CASE)) {
					$val = array_change_key_case($val, CASE_LOWER);
				}
				$info[$val['field']] = array('name' => $val['field'], 'type' => $val['type'], 'notnull' => (bool)($val['null'] === ''), 'default' => $val['default'], 'primary' => (strtolower($val['key']) == 'pri'), 'autoinc' => (strtolower($val['extra']) == 'auto_increment'),);
			}
		}
		return $info;
	}

	public function getTables($dbName = '')
	{
		$sql = !empty($dbName) ? 'SHOW TABLES FROM ' . $dbName : 'SHOW TABLES ';
		$result = $this->query($sql);
		$info = array();
		foreach ($result as $key => $val) {
			$info[$key] = current($val);
		}
		return $info;
	}

	protected function parseKey(&$key)
	{
		$key = trim($key);
		if (!is_numeric($key) && !preg_match('/[,\'\"\*\(\)`.\s]/', $key)) {
			$key = '`' . $key . '`';
		}
		return $key;
	}

	public function insertAll($dataSet, $options = array(), $replace = false)
	{
		$values = array();
		$this->model = $options['model'];
		if (!is_array($dataSet[0])) return false;
		$this->parseBind(!empty($options['bind']) ? $options['bind'] : array());
		$fields = array_map(array($this, 'parseKey'), array_keys($dataSet[0]));
		foreach ($dataSet as $data) {
			$value = array();
			foreach ($data as $key => $val) {
				if (is_array($val) && 'exp' == $val[0]) {
					$value[] = $val[1];
				} elseif (is_null($val)) {
					$value[] = 'NULL';
				} elseif (is_scalar($val)) {
					if (0 === strpos($val, ':') && in_array($val, array_keys($this->bind))) {
						$value[] = $this->parseValue($val);
					} else {
						$name = count($this->bind);
						$value[] = ':' . $name;
						$this->bindParam($name, $val);
					}
				}
			}
			$values[] = '(' . implode(',', $value) . ')';
		}
		$replace = (is_numeric($replace) && $replace > 0) ? true : $replace;
		$sql = (true === $replace ? 'REPLACE' : 'INSERT') . ' INTO ' . $this->parseTable($options['table']) . ' (' . implode(',', $fields) . ') VALUES ' . implode(',', $values) . $this->parseDuplicate($replace);
		$sql .= $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
		return $this->execute($sql, !empty($options['fetch_sql']) ? true : false);
	}

	protected function parseDuplicate($duplicate)
	{
		if (is_bool($duplicate) || empty($duplicate)) return '';
		if (is_string($duplicate)) {
			$duplicate = explode(',', $duplicate);
		} elseif (is_object($duplicate)) {
			$duplicate = get_class_vars($duplicate);
		}
		$updates = array();
		foreach ((array)$duplicate as $key => $val) {
			if (is_numeric($key)) {
				$updates[] = $this->parseKey($val) . "=VALUES(" . $this->parseKey($val) . ")";
			} else {
				if (is_scalar($val)) $val = array('value', $val);
				if (!isset($val[1])) continue;
				switch ($val[0]) {
					case 'exp':
						$updates[] = $this->parseKey($key) . "=($val[1])";
						break;
					case 'value':
					default:
						$name = count($this->bind);
						$updates[] = $this->parseKey($key) . "=:" . $name;
						$this->bindParam($name, $val[1]);
						break;
				}
			}
		}
		if (empty($updates)) return '';
		return " ON DUPLICATE KEY UPDATE " . join(', ', $updates);
	}

	public function procedure($str, $fetchSql = false)
	{
		$this->initConnect(false);
		$this->_linkID->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
		if (!$this->_linkID) return false;
		$this->queryStr = $str;
		if ($fetchSql) {
			return $this->queryStr;
		}
		if (!empty($this->PDOStatement)) $this->free();
		$this->queryTimes++;
		N('db_query', 1);
		$this->debug(true);
		$this->PDOStatement = $this->_linkID->prepare($str);
		if (false === $this->PDOStatement) {
			$this->error();
			return false;
		}
		try {
			$result = $this->PDOStatement->execute();
			$this->debug(false);
			do {
				$result = $this->PDOStatement->fetchAll(\PDO::FETCH_ASSOC);
				if ($result) {
					$resultArr[] = $result;
				}
			} while ($this->PDOStatement->nextRowset());
			$this->_linkID->setAttribute(\PDO::ATTR_ERRMODE, $this->options[\PDO::ATTR_ERRMODE]);
			return $resultArr;
		} catch (\PDOException $e) {
			$this->error();
			$this->_linkID->setAttribute(\PDO::ATTR_ERRMODE, $this->options[\PDO::ATTR_ERRMODE]);
			return false;
		}
	}
} 