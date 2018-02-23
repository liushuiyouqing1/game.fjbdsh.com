<?php
namespace Think\Db\Driver;

use Think\Db\Driver;

class Mongo extends Driver
{
	protected $_mongo = null;
	protected $_collection = null;
	protected $_dbName = '';
	protected $_collectionName = '';
	protected $_cursor = null;
	protected $comparison = array('neq' => 'ne', 'ne' => 'ne', 'gt' => 'gt', 'egt' => 'gte', 'gte' => 'gte', 'lt' => 'lt', 'elt' => 'lte', 'lte' => 'lte', 'in' => 'in', 'not in' => 'nin', 'nin' => 'nin');

	public function __construct($config = '')
	{
		if (!class_exists('mongoClient')) {
			E(L('_NOT_SUPPORT_') . ':Mongo');
		}
		if (!empty($config)) {
			$this->config = array_merge($this->config, $config);
			if (empty($this->config['params'])) {
				$this->config['params'] = array();
			}
		}
	}

	public function connect($config = '', $linkNum = 0)
	{
		if (!isset($this->linkID[$linkNum])) {
			if (empty($config)) $config = $this->config;
			$host = 'mongodb://' . ($config['username'] ? "{$config['username']}" : '') . ($config['password'] ? ":{$config['password']}@" : '') . $config['hostname'] . ($config['hostport'] ? ":{$config['hostport']}" : '') . '/' . ($config['database'] ? "{$config['database']}" : '');
			try {
				$this->linkID[$linkNum] = new \mongoClient($host, $this->config['params']);
			} catch (\MongoConnectionException $e) {
				E($e->getmessage());
			}
		}
		return $this->linkID[$linkNum];
	}

	public function switchCollection($collection, $db = '', $master = true)
	{
		if (!$this->_linkID) $this->initConnect($master);
		try {
			if (!empty($db)) {
				$this->_dbName = $db;
				$this->_mongo = $this->_linkID->selectDb($db);
			}
			if ($this->config['debug']) {
				$this->queryStr = $this->_dbName . '.getCollection(' . $collection . ')';
			}
			if ($this->_collectionName != $collection) {
				$this->queryTimes++;
				N('db_query', 1);
				$this->debug(true);
				$this->_collection = $this->_mongo->selectCollection($collection);
				$this->debug(false);
				$this->_collectionName = $collection;
			}
		} catch (MongoException $e) {
			E($e->getMessage());
		}
	}

	public function free()
	{
		$this->_cursor = null;
	}

	public function command($command = array(), $options = array())
	{
		$cache = isset($options['cache']) ? $options['cache'] : false;
		if ($cache) {
			$key = is_string($cache['key']) ? $cache['key'] : md5(serialize($command));
			$value = S($key, '', '', $cache['type']);
			if (false !== $value) {
				return $value;
			}
		}
		N('db_write', 1);
		$this->executeTimes++;
		try {
			if ($this->config['debug']) {
				$this->queryStr = $this->_dbName . '.' . $this->_collectionName . '.runCommand(';
				$this->queryStr .= json_encode($command);
				$this->queryStr .= ')';
			}
			$this->debug(true);
			$result = $this->_mongo->command($command);
			$this->debug(false);
			if ($cache && $result['ok']) {
				S($key, $result, $cache['expire'], $cache['type']);
			}
			return $result;
		} catch (\MongoCursorException $e) {
			E($e->getMessage());
		}
	}

	public function execute($code, $args = array())
	{
		$this->executeTimes++;
		N('db_write', 1);
		$this->debug(true);
		$this->queryStr = 'execute:' . $code;
		$result = $this->_mongo->execute($code, $args);
		$this->debug(false);
		if ($result['ok']) {
			return $result['retval'];
		} else {
			E($result['errmsg']);
		}
	}

	public function close()
	{
		if ($this->_linkID) {
			$this->_linkID->close();
			$this->_linkID = null;
			$this->_mongo = null;
			$this->_collection = null;
			$this->_cursor = null;
		}
	}

	public function error()
	{
		$this->error = $this->_mongo->lastError();
		trace($this->error, '', 'ERR');
		return $this->error;
	}

	public function insert($data, $options = array(), $replace = false)
	{
		if (isset($options['table'])) {
			$this->switchCollection($options['table']);
		}
		$this->model = $options['model'];
		$this->executeTimes++;
		N('db_write', 1);
		if ($this->config['debug']) {
			$this->queryStr = $this->_dbName . '.' . $this->_collectionName . '.insert(';
			$this->queryStr .= $data ? json_encode($data) : '{}';
			$this->queryStr .= ')';
		}
		try {
			$this->debug(true);
			$result = $replace ? $this->_collection->save($data) : $this->_collection->insert($data);
			$this->debug(false);
			if ($result) {
				$_id = $data['_id'];
				if (is_object($_id)) {
					$_id = $_id->__toString();
				}
				$this->lastInsID = $_id;
			}
			return $result;
		} catch (\MongoCursorException $e) {
			E($e->getMessage());
		}
	}

	public function insertAll($dataList, $options = array())
	{
		if (isset($options['table'])) {
			$this->switchCollection($options['table']);
		}
		$this->model = $options['model'];
		$this->executeTimes++;
		N('db_write', 1);
		try {
			$this->debug(true);
			$result = $this->_collection->batchInsert($dataList);
			$this->debug(false);
			return $result;
		} catch (\MongoCursorException $e) {
			E($e->getMessage());
		}
	}

	public function getMongoNextId($pk)
	{
		if ($this->config['debug']) {
			$this->queryStr = $this->_dbName . '.' . $this->_collectionName . '.find({},{' . $pk . ':1}).sort({' . $pk . ':-1}).limit(1)';
		}
		try {
			$this->debug(true);
			$result = $this->_collection->find(array(), array($pk => 1))->sort(array($pk => -1))->limit(1);
			$this->debug(false);
		} catch (\MongoCursorException $e) {
			E($e->getMessage());
		}
		$data = $result->getNext();
		return isset($data[$pk]) ? $data[$pk] + 1 : 1;
	}

	public function update($data, $options)
	{
		if (isset($options['table'])) {
			$this->switchCollection($options['table']);
		}
		$this->executeTimes++;
		N('db_write', 1);
		$this->model = $options['model'];
		$query = $this->parseWhere(isset($options['where']) ? $options['where'] : array());
		$set = $this->parseSet($data);
		if ($this->config['debug']) {
			$this->queryStr = $this->_dbName . '.' . $this->_collectionName . '.update(';
			$this->queryStr .= $query ? json_encode($query) : '{}';
			$this->queryStr .= ',' . json_encode($set) . ')';
		}
		try {
			$this->debug(true);
			if (isset($options['limit']) && $options['limit'] == 1) {
				$multiple = array("multiple" => false);
			} else {
				$multiple = array("multiple" => true);
			}
			$result = $this->_collection->update($query, $set, $multiple);
			$this->debug(false);
			return $result;
		} catch (\MongoCursorException $e) {
			E($e->getMessage());
		}
	}

	public function delete($options = array())
	{
		if (isset($options['table'])) {
			$this->switchCollection($options['table']);
		}
		$query = $this->parseWhere(isset($options['where']) ? $options['where'] : array());
		$this->model = $options['model'];
		$this->executeTimes++;
		N('db_write', 1);
		if ($this->config['debug']) {
			$this->queryStr = $this->_dbName . '.' . $this->_collectionName . '.remove(' . json_encode($query) . ')';
		}
		try {
			$this->debug(true);
			$result = $this->_collection->remove($query);
			$this->debug(false);
			return $result;
		} catch (\MongoCursorException $e) {
			E($e->getMessage());
		}
	}

	public function clear($options = array())
	{
		if (isset($options['table'])) {
			$this->switchCollection($options['table']);
		}
		$this->model = $options['model'];
		$this->executeTimes++;
		N('db_write', 1);
		if ($this->config['debug']) {
			$this->queryStr = $this->_dbName . '.' . $this->_collectionName . '.remove({})';
		}
		try {
			$this->debug(true);
			$result = $this->_collection->drop();
			$this->debug(false);
			return $result;
		} catch (\MongoCursorException $e) {
			E($e->getMessage());
		}
	}

	public function select($options = array())
	{
		if (isset($options['table'])) {
			$this->switchCollection($options['table'], '', false);
		}
		$this->model = $options['model'];
		$this->queryTimes++;
		N('db_query', 1);
		$query = $this->parseWhere(isset($options['where']) ? $options['where'] : array());
		$field = $this->parseField(isset($options['field']) ? $options['field'] : array());
		try {
			if ($this->config['debug']) {
				$this->queryStr = $this->_dbName . '.' . $this->_collectionName . '.find(';
				$this->queryStr .= $query ? json_encode($query) : '{}';
				if (is_array($field) && count($field)) {
					foreach ($field as $f => $v) $_field_array[$f] = $v ? 1 : 0;
					$this->queryStr .= $field ? ', ' . json_encode($_field_array) : ', {}';
				}
				$this->queryStr .= ')';
			}
			$this->debug(true);
			$_cursor = $this->_collection->find($query, $field);
			if (!empty($options['order'])) {
				$order = $this->parseOrder($options['order']);
				if ($this->config['debug']) {
					$this->queryStr .= '.sort(' . json_encode($order) . ')';
				}
				$_cursor = $_cursor->sort($order);
			}
			if (isset($options['page'])) {
				list($page, $length) = $options['page'];
				$page = $page > 0 ? $page : 1;
				$length = $length > 0 ? $length : (is_numeric($options['limit']) ? $options['limit'] : 20);
				$offset = $length * ((int)$page - 1);
				$options['limit'] = $offset . ',' . $length;
			}
			if (isset($options['limit'])) {
				list($offset, $length) = $this->parseLimit($options['limit']);
				if (!empty($offset)) {
					if ($this->config['debug']) {
						$this->queryStr .= '.skip(' . intval($offset) . ')';
					}
					$_cursor = $_cursor->skip(intval($offset));
				}
				if ($this->config['debug']) {
					$this->queryStr .= '.limit(' . intval($length) . ')';
				}
				$_cursor = $_cursor->limit(intval($length));
			}
			$this->debug(false);
			$this->_cursor = $_cursor;
			$resultSet = iterator_to_array($_cursor);
			return $resultSet;
		} catch (\MongoCursorException $e) {
			E($e->getMessage());
		}
	}

	public function find($options = array())
	{
		$options['limit'] = 1;
		$find = $this->select($options);
		return array_shift($find);
	}

	public function count($options = array())
	{
		if (isset($options['table'])) {
			$this->switchCollection($options['table'], '', false);
		}
		$this->model = $options['model'];
		$this->queryTimes++;
		N('db_query', 1);
		$query = $this->parseWhere(isset($options['where']) ? $options['where'] : array());
		if ($this->config['debug']) {
			$this->queryStr = $this->_dbName . '.' . $this->_collectionName;
			$this->queryStr .= $query ? '.find(' . json_encode($query) . ')' : '';
			$this->queryStr .= '.count()';
		}
		try {
			$this->debug(true);
			$count = $this->_collection->count($query);
			$this->debug(false);
			return $count;
		} catch (\MongoCursorException $e) {
			E($e->getMessage());
		}
	}

	public function group($keys, $initial, $reduce, $options = array())
	{
		if (isset($options['table']) && $this->_collectionName != $options['table']) {
			$this->switchCollection($options['table'], '', false);
		}
		$cache = isset($options['cache']) ? $options['cache'] : false;
		if ($cache) {
			$key = is_string($cache['key']) ? $cache['key'] : md5(serialize($options));
			$value = S($key, '', '', $cache['type']);
			if (false !== $value) {
				return $value;
			}
		}
		$this->model = $options['model'];
		$this->queryTimes++;
		N('db_query', 1);
		$query = $this->parseWhere(isset($options['where']) ? $options['where'] : array());
		if ($this->config['debug']) {
			$this->queryStr = $this->_dbName . '.' . $this->_collectionName . '.group({key:' . json_encode($keys) . ',cond:' . json_encode($options['condition']) . ',reduce:' . json_encode($reduce) . ',initial:' . json_encode($initial) . '})';
		}
		try {
			$this->debug(true);
			$option = array('condition' => $options['condition'], 'finalize' => $options['finalize'], 'maxTimeMS' => $options['maxTimeMS']);
			$group = $this->_collection->group($keys, $initial, $reduce, $options);
			$this->debug(false);
			if ($cache && $group['ok']) S($key, $group, $cache['expire'], $cache['type']);
			return $group;
		} catch (\MongoCursorException $e) {
			E($e->getMessage());
		}
	}

	public function getFields($collection = '')
	{
		if (!empty($collection) && $collection != $this->_collectionName) {
			$this->switchCollection($collection, '', false);
		}
		$this->queryTimes++;
		N('db_query', 1);
		if ($this->config['debug']) {
			$this->queryStr = $this->_dbName . '.' . $this->_collectionName . '.findOne()';
		}
		try {
			$this->debug(true);
			$result = $this->_collection->findOne();
			$this->debug(false);
		} catch (\MongoCursorException $e) {
			E($e->getMessage());
		}
		if ($result) {
			$info = array();
			foreach ($result as $key => $val) {
				$info[$key] = array('name' => $key, 'type' => getType($val),);
			}
			return $info;
		}
		return false;
	}

	public function getTables()
	{
		if ($this->config['debug']) {
			$this->queryStr = $this->_dbName . '.getCollenctionNames()';
		}
		$this->queryTimes++;
		N('db_query', 1);
		$this->debug(true);
		$list = $this->_mongo->listCollections();
		$this->debug(false);
		$info = array();
		foreach ($list as $collection) {
			$info[] = $collection->getName();
		}
		return $info;
	}

	public function getDB()
	{
		return $this->_mongo;
	}

	public function getCollection()
	{
		return $this->_collection;
	}

	protected function parseSet($data)
	{
		$result = array();
		foreach ($data as $key => $val) {
			if (is_array($val)) {
				switch ($val[0]) {
					case 'inc':
						$result['$inc'][$key] = (int)$val[1];
						break;
					case 'set':
					case 'unset':
					case 'push':
					case 'pushall':
					case 'addtoset':
					case 'pop':
					case 'pull':
					case 'pullall':
						$result['$' . $val[0]][$key] = $val[1];
						break;
					default:
						$result['$set'][$key] = $val;
				}
			} else {
				$result['$set'][$key] = $val;
			}
		}
		return $result;
	}

	protected function parseOrder($order)
	{
		if (is_string($order)) {
			$array = explode(',', $order);
			$order = array();
			foreach ($array as $key => $val) {
				$arr = explode(' ', trim($val));
				if (isset($arr[1])) {
					$arr[1] = $arr[1] == 'asc' ? 1 : -1;
				} else {
					$arr[1] = 1;
				}
				$order[$arr[0]] = $arr[1];
			}
		}
		return $order;
	}

	protected function parseLimit($limit)
	{
		if (strpos($limit, ',')) {
			$array = explode(',', $limit);
		} else {
			$array = array(0, $limit);
		}
		return $array;
	}

	public function parseField($fields)
	{
		if (empty($fields)) {
			$fields = array();
		}
		if (is_string($fields)) {
			$_fields = explode(',', $fields);
			$fields = array();
			foreach ($_fields as $f) $fields[$f] = true;
		} elseif (is_array($fields)) {
			$_fields = $fields;
			$fields = array();
			foreach ($_fields as $f => $v) {
				if (is_numeric($f)) $fields[$v] = true; else $fields[$f] = $v ? true : false;
			}
		}
		return $fields;
	}

	public function parseWhere($where)
	{
		$query = array();
		$return = array();
		$_logic = '$and';
		if (isset($where['_logic'])) {
			$where['_logic'] = strtolower($where['_logic']);
			$_logic = in_array($where['_logic'], array('or', 'xor', 'nor', 'and')) ? '$' . $where['_logic'] : $_logic;
			unset($where['_logic']);
		}
		foreach ($where as $key => $val) {
			if ('_id' != $key && 0 === strpos($key, '_')) {
				$parse = $this->parseThinkWhere($key, $val);
				$query = array_merge($query, $parse);
			} else {
				if (!preg_match('/^[A-Z_\|\&\-.a-z0-9]+$/', trim($key))) {
					E(L('_ERROR_QUERY_') . ':' . $key);
				}
				$key = trim($key);
				if (strpos($key, '|')) {
					$array = explode('|', $key);
					$str = array();
					foreach ($array as $k) {
						$str[] = $this->parseWhereItem($k, $val);
					}
					$query['$or'] = $str;
				} elseif (strpos($key, '&')) {
					$array = explode('&', $key);
					$str = array();
					foreach ($array as $k) {
						$str[] = $this->parseWhereItem($k, $val);
					}
					$query = array_merge($query, $str);
				} else {
					$str = $this->parseWhereItem($key, $val);
					$query = array_merge($query, $str);
				}
			}
		}
		if ($_logic == '$and') return $query;
		foreach ($query as $key => $val) $return[$_logic][] = array($key => $val);
		return $return;
	}

	protected function parseThinkWhere($key, $val)
	{
		$query = array();
		$_logic = array('or', 'xor', 'nor', 'and');
		switch ($key) {
			case '_query':
				parse_str($val, $query);
				if (isset($query['_logic']) && strtolower($query['_logic']) == 'or') {
					unset($query['_logic']);
					$query['$or'] = $query;
				}
				break;
			case '_complex':
				$__logic = strtolower($val['_logic']);
				if (isset($val['_logic']) && in_array($__logic, $_logic)) {
					unset($val['_logic']);
					$query['$' . $__logic] = $val;
				}
				break;
			case '_string':
				$query['$where'] = new \MongoCode($val);
				break;
		}
		if (isset($query['$or']) && !is_array(current($query['$or']))) {
			$val = array();
			foreach ($query['$or'] as $k => $v) $val[] = array($k => $v);
			$query['$or'] = $val;
		}
		return $query;
	}

	protected function parseWhereItem($key, $val)
	{
		$query = array();
		if (is_array($val)) {
			if (is_string($val[0])) {
				$con = strtolower($val[0]);
				if (in_array($con, array('neq', 'ne', 'gt', 'egt', 'gte', 'lt', 'lte', 'elt'))) {
					$k = '$' . $this->comparison[$con];
					$query[$key] = array($k => $val[1]);
				} elseif ('like' == $con) {
					$query[$key] = new \MongoRegex("/" . $val[1] . "/");
				} elseif ('mod' == $con) {
					$query[$key] = array('$mod' => $val[1]);
				} elseif ('regex' == $con) {
					$query[$key] = new \MongoRegex($val[1]);
				} elseif (in_array($con, array('in', 'nin', 'not in'))) {
					$data = is_string($val[1]) ? explode(',', $val[1]) : $val[1];
					$k = '$' . $this->comparison[$con];
					$query[$key] = array($k => $data);
				} elseif ('all' == $con) {
					$data = is_string($val[1]) ? explode(',', $val[1]) : $val[1];
					$query[$key] = array('$all' => $data);
				} elseif ('between' == $con) {
					$data = is_string($val[1]) ? explode(',', $val[1]) : $val[1];
					$query[$key] = array('$gte' => $data[0], '$lte' => $data[1]);
				} elseif ('not between' == $con) {
					$data = is_string($val[1]) ? explode(',', $val[1]) : $val[1];
					$query[$key] = array('$lt' => $data[0], '$gt' => $data[1]);
				} elseif ('exp' == $con) {
					$query['$where'] = new \MongoCode($val[1]);
				} elseif ('exists' == $con) {
					$query[$key] = array('$exists' => (bool)$val[1]);
				} elseif ('size' == $con) {
					$query[$key] = array('$size' => intval($val[1]));
				} elseif ('type' == $con) {
					$query[$key] = array('$type' => intval($val[1]));
				} else {
					$query[$key] = $val;
				}
				return $query;
			}
		}
		$query[$key] = $val;
		return $query;
	}
} 