<?php
namespace Think\Db\Driver;

use Think\Db\Driver;

class Oracle extends Driver
{
	private $table = '';
	protected $selectSql = 'SELECT * FROM (SELECT thinkphp.*, rownum AS numrow FROM (SELECT  %DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%) thinkphp ) %LIMIT%%COMMENT%';

	protected function parseDsn($config)
	{
		$dsn = 'oci:dbname=//' . $config['hostname'] . ($config['hostport'] ? ':' . $config['hostport'] : '') . '/' . $config['database'];
		if (!empty($config['charset'])) {
			$dsn .= ';charset=' . $config['charset'];
		}
		return $dsn;
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
		$flag = false;
		if (preg_match("/^\s*(INSERT\s+INTO)\s+(\w+)\s+/i", $str, $match)) {
			$this->table = C("DB_SEQUENCE_PREFIX") . str_ireplace(C("DB_PREFIX"), "", $match[2]);
			$flag = (boolean)$this->query("SELECT * FROM user_sequences WHERE sequence_name='" . strtoupper($this->table) . "'");
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
		$result = $this->PDOStatement->execute();
		$this->debug(false);
		if (false === $result) {
			$this->error();
			return false;
		} else {
			$this->numRows = $this->PDOStatement->rowCount();
			if ($flag || preg_match("/^\s*(INSERT\s+INTO|REPLACE\s+INTO)\s+/i", $str)) {
				$this->lastInsID = $this->_linkID->lastInsertId();
			}
			return $this->numRows;
		}
	}

	public function getFields($tableName)
	{
		list($tableName) = explode(' ', $tableName);
		$result = $this->query("select a.column_name,data_type,decode(nullable,'Y',0,1) notnull,data_default,decode(a.column_name,b.column_name,1,0) pk " . "from user_tab_columns a,(select column_name from user_constraints c,user_cons_columns col " . "where c.constraint_name=col.constraint_name and c.constraint_type='P'and c.table_name='" . strtoupper($tableName) . "') b where table_name='" . strtoupper($tableName) . "' and a.column_name=b.column_name(+)");
		$info = array();
		if ($result) {
			foreach ($result as $key => $val) {
				$info[strtolower($val['column_name'])] = array('name' => strtolower($val['column_name']), 'type' => strtolower($val['data_type']), 'notnull' => $val['notnull'], 'default' => $val['data_default'], 'primary' => $val['pk'], 'autoinc' => $val['pk'],);
			}
		}
		return $info;
	}

	public function getTables($dbName = '')
	{
		$result = $this->query("select table_name from user_tables");
		$info = array();
		foreach ($result as $key => $val) {
			$info[$key] = current($val);
		}
		return $info;
	}

	public function escapeString($str)
	{
		return str_ireplace("'", "''", $str);
	}

	public function parseLimit($limit)
	{
		$limitStr = '';
		if (!empty($limit)) {
			$limit = explode(',', $limit);
			if (count($limit) > 1) $limitStr = "(numrow>" . $limit[0] . ") AND (numrow<=" . ($limit[0] + $limit[1]) . ")"; else $limitStr = "(numrow>0 AND numrow<=" . $limit[0] . ")";
		}
		return $limitStr ? ' WHERE ' . $limitStr : '';
	}

	protected function parseLock($lock = false)
	{
		if (!$lock) return '';
		return ' FOR UPDATE NOWAIT ';
	}
} 