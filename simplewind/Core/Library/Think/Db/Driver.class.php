<?php
namespace Think\Db;

use Think\Config;
use Think\Debug;
use Think\Log;
use PDO;

abstract class Driver
{
	protected $PDOStatement = null;
	protected $model = '_think_';
	protected $queryStr = '';
	protected $modelSql = array();
	protected $lastInsID = null;
	protected $numRows = 0;
	protected $transTimes = 0;
	protected $error = '';
	protected $linkID = array();
	protected $_linkID = null;
	protected $config = array('type' => '', 'hostname' => '127.0.0.1', 'database' => '', 'username' => '', 'password' => '', 'hostport' => '', 'dsn' => '', 'params' => array(), 'charset' => 'utf8', 'prefix' => '', 'debug' => false, 'deploy' => 0, 'rw_separate' => false, 'master_num' => 1, 'slave_no' => '', 'db_like_fields' => '',);
	protected $exp = array('eq' => '=', 'neq' => '<>', 'gt' => '>', 'egt' => '>=', 'lt' => '<', 'elt' => '<=', 'notlike' => 'NOT LIKE', 'like' => 'LIKE', 'in' => 'IN', 'notin' => 'NOT IN', 'not in' => 'NOT IN', 'between' => 'BETWEEN', 'not between' => 'NOT BETWEEN', 'notbetween' => 'NOT BETWEEN');
	protected $selectSql = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%FORCE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT% %UNION%%LOCK%%COMMENT%';
	protected $queryTimes = 0;
	protected $executeTimes = 0;
	protected $options = array(PDO::ATTR_CASE => PDO::CASE_LOWER, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL, PDO::ATTR_STRINGIFY_FETCHES => false,);
	protected $bind = array();

	public function __construct($config = '')
	{
		if (!empty($config)) {
			$this->config = array_merge($this->config, $config);
			if (is_array($this->config['params'])) {
				$this->options = $this->config['params'] + $this->options;
			}
		}
	}

	public function connect($config = '', $linkNum = 0, $autoConnection = false)
	{
		if (!isset($this->linkID[$linkNum])) {
			if (empty($config)) $config = $this->config;
			try {
				if (empty($config['dsn'])) {
					$config['dsn'] = $this->parseDsn($config);
				}
				if (version_compare(PHP_VERSION, '5.3.6', '<=')) {
					$this->options[PDO::ATTR_EMULATE_PREPARES] = false;
				}
				$this->linkID[$linkNum] = new PDO($config['dsn'], $config['username'], $config['password'], $this->options);
				$this->linkID[$linkNum]->query('set names utf8mb4');
			} catch (\PDOException $e) {
				if ($autoConnection) {
					trace($e->getMessage(), '', 'ERR');
					return $this->connect($autoConnection, $linkNum);
				} elseif ($config['debug']) {
					E($e->getMessage());
				}
			}
		}
		return $this->linkID[$linkNum];
	}

	protected function parseDsn($config)
	{
	}

	public function free()
	{
		$this->PDOStatement = null;
	}

	public function query($str, $fetchSql = false)
	{
		$this->initConnect(false);
		if (!$this->_linkID) return false;
		$this->queryStr = $str;
		if (!empty($this->bind)) {
			$that = $this;
			$this->queryStr = strtr($this->queryStr, array_map(function ($val) use ($that) {
				return '\'' . $that->escapeString($val) . '\'';
			}, $this->bind));
		}
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
		foreach ($this->bind as $key => $val) {
			if (is_array($val)) {
				$this->PDOStatement->bindValue($key, $val[0], $val[1]);
			} else {
				$this->PDOStatement->bindValue($key, $val);
			}
		}
		$this->bind = array();
		try {
			$result = $this->PDOStatement->execute();
			$this->debug(false);
			if (false === $result) {
				$this->error();
				return false;
			} else {
				return $this->getResult();
			}
		} catch (\PDOException $e) {
			$this->error();
			return false;
		}
	}

	public function execute($str, $fetchSql = false)
	{
		$this->initConnect(true);
		if (!$this->_linkID) return false;
		$this->queryStr = $str;
		if (!empty($this->bind)) {
			$that = $this;
			$this->queryStr = strtr($this->queryStr, array_map(function ($val) use ($that) {
				return '\'' . $that->escapeString($val) . '\'';
			}, $this->bind));
		}
		if ($fetchSql) {
			return $this->queryStr;
		}
		if (!empty($this->PDOStatement)) $this->free();
		$this->executeTimes++;
		N('db_write', 1);
		$this->debug(true);
		$this->PDOStatement = $this->_linkID->prepare($str);
		if (false === $this->PDOStatement) {
			$this->error();
			return false;
		}
		foreach ($this->bind as $key => $val) {
			if (is_array($val)) {
				$this->PDOStatement->bindValue($key, $val[0], $val[1]);
			} else {
				$this->PDOStatement->bindValue($key, $val);
			}
		}
		$this->bind = array();
		try {
			$result = $this->PDOStatement->execute();
			$this->debug(false);
			if (false === $result) {
				$this->error();
				return false;
			} else {
				$this->numRows = $this->PDOStatement->rowCount();
				if (preg_match("/^\s*(INSERT\s+INTO|REPLACE\s+INTO)\s+/i", $str)) {
					$this->lastInsID = $this->_linkID->lastInsertId();
				}
				return $this->numRows;
			}
		} catch (\PDOException $e) {
			$this->error();
			return false;
		}
	}

	public function startTrans()
	{
		$this->initConnect(true);
		if (!$this->_linkID) return false;
		if ($this->transTimes == 0) {
			$this->_linkID->beginTransaction();
		}
		$this->transTimes++;
		return;
	}

	public function commit()
	{
		if ($this->transTimes > 0) {
			$result = $this->_linkID->commit();
			$this->transTimes = 0;
			if (!$result) {
				$this->error();
				return false;
			}
		}
		return true;
	}

	public function rollback()
	{
		if ($this->transTimes > 0) {
			$result = $this->_linkID->rollback();
			$this->transTimes = 0;
			if (!$result) {
				$this->error();
				return false;
			}
		}
		return true;
	}

	private function getResult()
	{
		$result = $this->PDOStatement->fetchAll(PDO::FETCH_ASSOC);
		$this->numRows = count($result);
		return $result;
	}

	public function getQueryTimes($execute = false)
	{
		return $execute ? $this->queryTimes + $this->executeTimes : $this->queryTimes;
	}

	public function getExecuteTimes()
	{
		return $this->executeTimes;
	}

	public function close()
	{
		$this->_linkID = null;
	}

	public function error()
	{
		if ($this->PDOStatement) {
			$error = $this->PDOStatement->errorInfo();
			$this->error = $error[1] . ':' . $error[2];
		} else {
			$this->error = '';
		}
		if ('' != $this->queryStr) {
			$this->error .= "\n [ SQL语句 ] : " . $this->queryStr;
		}
		trace($this->error, '', 'ERR');
		if ($this->config['debug']) {
			E($this->error);
		} else {
			return $this->error;
		}
	}

	protected function parseLock($lock = false)
	{
		return $lock ? ' FOR UPDATE ' : '';
	}

	protected function parseSet($data)
	{
		foreach ($data as $key => $val) {
			if (is_array($val) && 'exp' == $val[0]) {
				$set[] = $this->parseKey($key) . '=' . $val[1];
			} elseif (is_null($val)) {
				$set[] = $this->parseKey($key) . '=NULL';
			} elseif (is_scalar($val)) {
				if (0 === strpos($val, ':') && in_array($val, array_keys($this->bind))) {
					$set[] = $this->parseKey($key) . '=' . $this->escapeString($val);
				} else {
					$name = count($this->bind);
					$set[] = $this->parseKey($key) . '=:' . $name;
					$this->bindParam($name, $val);
				}
			}
		}
		return ' SET ' . implode(',', $set);
	}

	protected function bindParam($name, $value)
	{
		$this->bind[':' . $name] = $value;
	}

	protected function parseKey(&$key)
	{
		return $key;
	}

	protected function parseValue($value)
	{
		if (is_string($value)) {
			$value = strpos($value, ':') === 0 && in_array($value, array_keys($this->bind)) ? $this->escapeString($value) : '\'' . $this->escapeString($value) . '\'';
		} elseif (isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp') {
			$value = $this->escapeString($value[1]);
		} elseif (is_array($value)) {
			$value = array_map(array($this, 'parseValue'), $value);
		} elseif (is_bool($value)) {
			$value = $value ? '1' : '0';
		} elseif (is_null($value)) {
			$value = 'null';
		}
		return $value;
	}

	protected function parseField($fields)
	{
		if (is_string($fields) && '' !== $fields) {
			$fields = explode(',', $fields);
		}
		if (is_array($fields)) {
			$array = array();
			foreach ($fields as $key => $field) {
				if (!is_numeric($key)) $array[] = $this->parseKey($key) . ' AS ' . $this->parseKey($field); else $array[] = $this->parseKey($field);
			}
			$fieldsStr = implode(',', $array);
		} else {
			$fieldsStr = '*';
		}
		return $fieldsStr;
	}

	protected function parseTable($tables)
	{
		if (is_array($tables)) {
			$array = array();
			foreach ($tables as $table => $alias) {
				if (!is_numeric($table)) $array[] = $this->parseKey($table) . ' ' . $this->parseKey($alias); else $array[] = $this->parseKey($alias);
			}
			$tables = $array;
		} elseif (is_string($tables)) {
			$tables = explode(',', $tables);
			array_walk($tables, array(&$this, 'parseKey'));
		}
		return implode(',', $tables);
	}

	protected function parseWhere($where)
	{
		$whereStr = '';
		if (is_string($where)) {
			$whereStr = $where;
		} else {
			$operate = isset($where['_logic']) ? strtoupper($where['_logic']) : '';
			if (in_array($operate, array('AND', 'OR', 'XOR'))) {
				$operate = ' ' . $operate . ' ';
				unset($where['_logic']);
			} else {
				$operate = ' AND ';
			}
			if (is_array($where) && !empty($where)) {
				foreach ($where as $key => $val) {
					if (is_numeric($key)) {
						$key = '_complex';
					}
					if (0 === strpos($key, '_')) {
						$whereStr .= $this->parseThinkWhere($key, $val);
					} else {
						$multi = is_array($val) && isset($val['_multi']);
						$key = trim($key);
						if (strpos($key, '|')) {
							$array = explode('|', $key);
							$str = array();
							foreach ($array as $m => $k) {
								$v = $multi ? $val[$m] : $val;
								$str[] = $this->parseWhereItem($this->parseKey($k), $v);
							}
							$whereStr .= '( ' . implode(' OR ', $str) . ' )';
						} elseif (strpos($key, '&')) {
							$array = explode('&', $key);
							$str = array();
							foreach ($array as $m => $k) {
								$v = $multi ? $val[$m] : $val;
								$str[] = '(' . $this->parseWhereItem($this->parseKey($k), $v) . ')';
							}
							$whereStr .= '( ' . implode(' AND ', $str) . ' )';
						} else {
							$whereStr .= $this->parseWhereItem($this->parseKey($key), $val);
						}
					}
					$whereStr .= $operate;
				}
			}
			$whereStr = substr($whereStr, 0, -strlen($operate));
		}
		return empty($whereStr) ? '' : ' WHERE ' . $whereStr;
	}

	protected function parseWhereItem($key, $val)
	{
		$whereStr = '';
		if (is_array($val)) {
			if (is_string($val[0])) {
				$exp = strtolower($val[0]);
				if (preg_match('/^(eq|neq|gt|egt|lt|elt)$/', $exp)) {
					$whereStr .= $key . ' ' . $this->exp[$exp] . ' ' . $this->parseValue($val[1]);
				} elseif (preg_match('/^(notlike|like)$/', $exp)) {
					if (is_array($val[1])) {
						$likeLogic = isset($val[2]) ? strtoupper($val[2]) : 'OR';
						if (in_array($likeLogic, array('AND', 'OR', 'XOR'))) {
							$like = array();
							foreach ($val[1] as $item) {
								$like[] = $key . ' ' . $this->exp[$exp] . ' ' . $this->parseValue($item);
							}
							$whereStr .= '(' . implode(' ' . $likeLogic . ' ', $like) . ')';
						}
					} else {
						$whereStr .= $key . ' ' . $this->exp[$exp] . ' ' . $this->parseValue($val[1]);
					}
				} elseif ('bind' == $exp) {
					$whereStr .= $key . ' = :' . $val[1];
				} elseif ('exp' == $exp) {
					$whereStr .= $key . ' ' . $val[1];
				} elseif (preg_match('/^(notin|not in|in)$/', $exp)) {
					if (isset($val[2]) && 'exp' == $val[2]) {
						$whereStr .= $key . ' ' . $this->exp[$exp] . ' ' . $val[1];
					} else {
						if (is_string($val[1])) {
							$val[1] = explode(',', $val[1]);
						}
						$zone = implode(',', $this->parseValue($val[1]));
						$whereStr .= $key . ' ' . $this->exp[$exp] . ' (' . $zone . ')';
					}
				} elseif (preg_match('/^(notbetween|not between|between)$/', $exp)) {
					$data = is_string($val[1]) ? explode(',', $val[1]) : $val[1];
					$whereStr .= $key . ' ' . $this->exp[$exp] . ' ' . $this->parseValue($data[0]) . ' AND ' . $this->parseValue($data[1]);
				} else {
					E(L('_EXPRESS_ERROR_') . ':' . $val[0]);
				}
			} else {
				$count = count($val);
				$rule = isset($val[$count - 1]) ? (is_array($val[$count - 1]) ? strtoupper($val[$count - 1][0]) : strtoupper($val[$count - 1])) : '';
				if (in_array($rule, array('AND', 'OR', 'XOR'))) {
					$count = $count - 1;
				} else {
					$rule = 'AND';
				}
				for ($i = 0; $i < $count; $i++) {
					$data = is_array($val[$i]) ? $val[$i][1] : $val[$i];
					if ('exp' == strtolower($val[$i][0])) {
						$whereStr .= $key . ' ' . $data . ' ' . $rule . ' ';
					} else {
						$whereStr .= $this->parseWhereItem($key, $val[$i]) . ' ' . $rule . ' ';
					}
				}
				$whereStr = '( ' . substr($whereStr, 0, -4) . ' )';
			}
		} else {
			$likeFields = $this->config['db_like_fields'];
			if ($likeFields && preg_match('/^(' . $likeFields . ')$/i', $key)) {
				$whereStr .= $key . ' LIKE ' . $this->parseValue('%' . $val . '%');
			} else {
				$whereStr .= $key . ' = ' . $this->parseValue($val);
			}
		}
		return $whereStr;
	}

	protected function parseThinkWhere($key, $val)
	{
		$whereStr = '';
		switch ($key) {
			case '_string':
				$whereStr = $val;
				break;
			case '_complex':
				$whereStr = substr($this->parseWhere($val), 6);
				break;
			case '_query':
				parse_str($val, $where);
				if (isset($where['_logic'])) {
					$op = ' ' . strtoupper($where['_logic']) . ' ';
					unset($where['_logic']);
				} else {
					$op = ' AND ';
				}
				$array = array();
				foreach ($where as $field => $data) $array[] = $this->parseKey($field) . ' = ' . $this->parseValue($data);
				$whereStr = implode($op, $array);
				break;
		}
		return '( ' . $whereStr . ' )';
	}

	protected function parseLimit($limit)
	{
		return !empty($limit) ? ' LIMIT ' . $limit . ' ' : '';
	}

	protected function parseJoin($join)
	{
		$joinStr = '';
		if (!empty($join)) {
			$joinStr = ' ' . implode(' ', $join) . ' ';
		}
		return $joinStr;
	}

	protected function parseOrder($order)
	{
		if (is_array($order)) {
			$array = array();
			foreach ($order as $key => $val) {
				if (is_numeric($key)) {
					$array[] = $this->parseKey($val);
				} else {
					$array[] = $this->parseKey($key) . ' ' . $val;
				}
			}
			$order = implode(',', $array);
		}
		return !empty($order) ? ' ORDER BY ' . $order : '';
	}

	protected function parseGroup($group)
	{
		return !empty($group) ? ' GROUP BY ' . $group : '';
	}

	protected function parseHaving($having)
	{
		return !empty($having) ? ' HAVING ' . $having : '';
	}

	protected function parseComment($comment)
	{
		return !empty($comment) ? ' /* ' . $comment . ' */' : '';
	}

	protected function parseDistinct($distinct)
	{
		return !empty($distinct) ? ' DISTINCT ' : '';
	}

	protected function parseUnion($union)
	{
		if (empty($union)) return '';
		if (isset($union['_all'])) {
			$str = 'UNION ALL ';
			unset($union['_all']);
		} else {
			$str = 'UNION ';
		}
		foreach ($union as $u) {
			$sql[] = $str . (is_array($u) ? $this->buildSelectSql($u) : $u);
		}
		return implode(' ', $sql);
	}

	protected function parseBind($bind)
	{
		$this->bind = array_merge($this->bind, $bind);
	}

	protected function parseForce($index)
	{
		if (empty($index)) return '';
		if (is_array($index)) $index = join(",", $index);
		return sprintf(" FORCE INDEX ( %s ) ", $index);
	}

	protected function parseDuplicate($duplicate)
	{
		return '';
	}

	public function insert($data, $options = array(), $replace = false)
	{
		$values = $fields = array();
		$this->model = $options['model'];
		$this->parseBind(!empty($options['bind']) ? $options['bind'] : array());
		foreach ($data as $key => $val) {
			if (is_array($val) && 'exp' == $val[0]) {
				$fields[] = $this->parseKey($key);
				$values[] = $val[1];
			} elseif (is_null($val)) {
				$fields[] = $this->parseKey($key);
				$values[] = 'NULL';
			} elseif (is_scalar($val)) {
				$fields[] = $this->parseKey($key);
				if (0 === strpos($val, ':') && in_array($val, array_keys($this->bind))) {
					$values[] = $this->parseValue($val);
				} else {
					$name = count($this->bind);
					$values[] = ':' . $name;
					$this->bindParam($name, $val);
				}
			}
		}
		$replace = (is_numeric($replace) && $replace > 0) ? true : $replace;
		$sql = (true === $replace ? 'REPLACE' : 'INSERT') . ' INTO ' . $this->parseTable($options['table']) . ' (' . implode(',', $fields) . ') VALUES (' . implode(',', $values) . ')' . $this->parseDuplicate($replace);
		$sql .= $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
		return $this->execute($sql, !empty($options['fetch_sql']) ? true : false);
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
			$values[] = 'SELECT ' . implode(',', $value);
		}
		$sql = 'INSERT INTO ' . $this->parseTable($options['table']) . ' (' . implode(',', $fields) . ') ' . implode(' UNION ALL ', $values);
		$sql .= $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
		return $this->execute($sql, !empty($options['fetch_sql']) ? true : false);
	}

	public function selectInsert($fields, $table, $options = array())
	{
		$this->model = $options['model'];
		$this->parseBind(!empty($options['bind']) ? $options['bind'] : array());
		if (is_string($fields)) $fields = explode(',', $fields);
		array_walk($fields, array($this, 'parseKey'));
		$sql = 'INSERT INTO ' . $this->parseTable($table) . ' (' . implode(',', $fields) . ') ';
		$sql .= $this->buildSelectSql($options);
		return $this->execute($sql, !empty($options['fetch_sql']) ? true : false);
	}

	public function update($data, $options)
	{
		$this->model = $options['model'];
		$this->parseBind(!empty($options['bind']) ? $options['bind'] : array());
		$table = $this->parseTable($options['table']);
		$sql = 'UPDATE ' . $table . $this->parseSet($data);
		if (strpos($table, ',')) {
			$sql .= $this->parseJoin(!empty($options['join']) ? $options['join'] : '');
		}
		$sql .= $this->parseWhere(!empty($options['where']) ? $options['where'] : '');
		if (!strpos($table, ',')) {
			$sql .= $this->parseOrder(!empty($options['order']) ? $options['order'] : '') . $this->parseLimit(!empty($options['limit']) ? $options['limit'] : '');
		}
		$sql .= $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
		return $this->execute($sql, !empty($options['fetch_sql']) ? true : false);
	}

	public function delete($options = array())
	{
		$this->model = $options['model'];
		$this->parseBind(!empty($options['bind']) ? $options['bind'] : array());
		$table = $this->parseTable($options['table']);
		$sql = 'DELETE FROM ' . $table;
		if (strpos($table, ',')) {
			if (!empty($options['using'])) {
				$sql .= ' USING ' . $this->parseTable($options['using']) . ' ';
			}
			$sql .= $this->parseJoin(!empty($options['join']) ? $options['join'] : '');
		}
		$sql .= $this->parseWhere(!empty($options['where']) ? $options['where'] : '');
		if (!strpos($table, ',')) {
			$sql .= $this->parseOrder(!empty($options['order']) ? $options['order'] : '') . $this->parseLimit(!empty($options['limit']) ? $options['limit'] : '');
		}
		$sql .= $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
		return $this->execute($sql, !empty($options['fetch_sql']) ? true : false);
	}

	public function select($options = array())
	{
		$this->model = $options['model'];
		$this->parseBind(!empty($options['bind']) ? $options['bind'] : array());
		$sql = $this->buildSelectSql($options);
		$result = $this->query($sql, !empty($options['fetch_sql']) ? true : false);
		return $result;
	}

	public function buildSelectSql($options = array())
	{
		if (isset($options['page'])) {
			list($page, $listRows) = $options['page'];
			$page = $page > 0 ? $page : 1;
			$listRows = $listRows > 0 ? $listRows : (is_numeric($options['limit']) ? $options['limit'] : 20);
			$offset = $listRows * ($page - 1);
			$options['limit'] = $offset . ',' . $listRows;
		}
		$sql = $this->parseSql($this->selectSql, $options);
		return $sql;
	}

	public function parseSql($sql, $options = array())
	{
		$sql = str_replace(array('%TABLE%', '%DISTINCT%', '%FIELD%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%', '%UNION%', '%LOCK%', '%COMMENT%', '%FORCE%'), array($this->parseTable($options['table']), $this->parseDistinct(isset($options['distinct']) ? $options['distinct'] : false), $this->parseField(!empty($options['field']) ? $options['field'] : '*'), $this->parseJoin(!empty($options['join']) ? $options['join'] : ''), $this->parseWhere(!empty($options['where']) ? $options['where'] : ''), $this->parseGroup(!empty($options['group']) ? $options['group'] : ''), $this->parseHaving(!empty($options['having']) ? $options['having'] : ''), $this->parseOrder(!empty($options['order']) ? $options['order'] : ''), $this->parseLimit(!empty($options['limit']) ? $options['limit'] : ''), $this->parseUnion(!empty($options['union']) ? $options['union'] : ''), $this->parseLock(isset($options['lock']) ? $options['lock'] : false), $this->parseComment(!empty($options['comment']) ? $options['comment'] : ''), $this->parseForce(!empty($options['force']) ? $options['force'] : '')), $sql);
		return $sql;
	}

	public function getLastSql($model = '')
	{
		return $model ? $this->modelSql[$model] : $this->queryStr;
	}

	public function getLastInsID()
	{
		return $this->lastInsID;
	}

	public function getError()
	{
		return $this->error;
	}

	public function escapeString($str)
	{
		return addslashes($str);
	}

	public function setModel($model)
	{
		$this->model = $model;
	}

	protected function debug($start)
	{
		if ($this->config['debug']) {
			if ($start) {
				G('queryStartTime');
			} else {
				$this->modelSql[$this->model] = $this->queryStr;
				G('queryEndTime');
				trace($this->queryStr . ' [ RunTime:' . G('queryStartTime', 'queryEndTime') . 's ]', '', 'SQL');
			}
		}
	}

	protected function initConnect($master = true)
	{
		if (!empty($this->config['deploy'])) $this->_linkID = $this->multiConnect($master); else if (!$this->_linkID) $this->_linkID = $this->connect();
	}

	protected function multiConnect($master = false)
	{
		$_config['username'] = explode(',', $this->config['username']);
		$_config['password'] = explode(',', $this->config['password']);
		$_config['hostname'] = explode(',', $this->config['hostname']);
		$_config['hostport'] = explode(',', $this->config['hostport']);
		$_config['database'] = explode(',', $this->config['database']);
		$_config['dsn'] = explode(',', $this->config['dsn']);
		$_config['charset'] = explode(',', $this->config['charset']);
		$m = floor(mt_rand(0, $this->config['master_num'] - 1));
		if ($this->config['rw_separate']) {
			if ($master) $r = $m; else {
				if (is_numeric($this->config['slave_no'])) {
					$r = $this->config['slave_no'];
				} else {
					$r = floor(mt_rand($this->config['master_num'], count($_config['hostname']) - 1));
				}
			}
		} else {
			$r = floor(mt_rand(0, count($_config['hostname']) - 1));
		}
		if ($m != $r) {
			$db_master = array('username' => isset($_config['username'][$m]) ? $_config['username'][$m] : $_config['username'][0], 'password' => isset($_config['password'][$m]) ? $_config['password'][$m] : $_config['password'][0], 'hostname' => isset($_config['hostname'][$m]) ? $_config['hostname'][$m] : $_config['hostname'][0], 'hostport' => isset($_config['hostport'][$m]) ? $_config['hostport'][$m] : $_config['hostport'][0], 'database' => isset($_config['database'][$m]) ? $_config['database'][$m] : $_config['database'][0], 'dsn' => isset($_config['dsn'][$m]) ? $_config['dsn'][$m] : $_config['dsn'][0], 'charset' => isset($_config['charset'][$m]) ? $_config['charset'][$m] : $_config['charset'][0],);
		}
		$db_config = array('username' => isset($_config['username'][$r]) ? $_config['username'][$r] : $_config['username'][0], 'password' => isset($_config['password'][$r]) ? $_config['password'][$r] : $_config['password'][0], 'hostname' => isset($_config['hostname'][$r]) ? $_config['hostname'][$r] : $_config['hostname'][0], 'hostport' => isset($_config['hostport'][$r]) ? $_config['hostport'][$r] : $_config['hostport'][0], 'database' => isset($_config['database'][$r]) ? $_config['database'][$r] : $_config['database'][0], 'dsn' => isset($_config['dsn'][$r]) ? $_config['dsn'][$r] : $_config['dsn'][0], 'charset' => isset($_config['charset'][$r]) ? $_config['charset'][$r] : $_config['charset'][0],);
		return $this->connect($db_config, $r, $r == $m ? false : $db_master);
	}

	public function __destruct()
	{
		if ($this->PDOStatement) {
			$this->free();
		}
		$this->close();
	}
} 