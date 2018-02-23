<?php
namespace Think\Db\Driver;

use Think\Db\Driver;
use PDO;

class Sqlsrv extends Driver
{
	protected $selectSql = 'SELECT T1.* FROM (SELECT thinkphp.*, ROW_NUMBER() OVER (%ORDER%) AS ROW_NUMBER FROM (SELECT %DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING% %UNION%) AS thinkphp) AS T1 %LIMIT%%COMMENT%';
	protected $options = array(PDO::ATTR_CASE => PDO::CASE_LOWER, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_STRINGIFY_FETCHES => false, PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8,);

	protected function parseDsn($config)
	{
		$dsn = 'sqlsrv:Database=' . $config['database'] . ';Server=' . $config['hostname'];
		if (!empty($config['hostport'])) {
			$dsn .= ',' . $config['hostport'];
		}
		return $dsn;
	}

	public function getFields($tableName)
	{
		list($tableName) = explode(' ', $tableName);
		$result = $this->query("SELECT   column_name,   data_type,   column_default,   is_nullable
        FROM    information_schema.tables AS t
        JOIN    information_schema.columns AS c
        ON  t.table_catalog = c.table_catalog
        AND t.table_schema  = c.table_schema
        AND t.table_name    = c.table_name
        WHERE   t.table_name = '$tableName'");
		$info = array();
		if ($result) {
			foreach ($result as $key => $val) {
				$info[$val['column_name']] = array('name' => $val['column_name'], 'type' => $val['data_type'], 'notnull' => (bool)($val['is_nullable'] === ''), 'default' => $val['column_default'], 'primary' => false, 'autoinc' => false,);
			}
		}
		return $info;
	}

	public function getTables($dbName = '')
	{
		$result = $this->query("SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_TYPE = 'BASE TABLE'
            ");
		$info = array();
		foreach ($result as $key => $val) {
			$info[$key] = current($val);
		}
		return $info;
	}

	protected function parseOrder($order)
	{
		return !empty($order) ? ' ORDER BY ' . $order : ' ORDER BY rand()';
	}

	protected function parseKey(&$key)
	{
		$key = trim($key);
		if (!is_numeric($key) && !preg_match('/[,\'\"\*\(\)\[.\s]/', $key)) {
			$key = '[' . $key . ']';
		}
		return $key;
	}

	public function parseLimit($limit)
	{
		if (empty($limit)) return '';
		$limit = explode(',', $limit);
		if (count($limit) > 1) $limitStr = '(T1.ROW_NUMBER BETWEEN ' . $limit[0] . ' + 1 AND ' . $limit[0] . ' + ' . $limit[1] . ')'; else $limitStr = '(T1.ROW_NUMBER BETWEEN 1 AND ' . $limit[0] . ")";
		return 'WHERE ' . $limitStr;
	}

	public function update($data, $options)
	{
		$this->model = $options['model'];
		$this->parseBind(!empty($options['bind']) ? $options['bind'] : array());
		$sql = 'UPDATE ' . $this->parseTable($options['table']) . $this->parseSet($data) . $this->parseWhere(!empty($options['where']) ? $options['where'] : '') . $this->parseLock(isset($options['lock']) ? $options['lock'] : false) . $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
		return $this->execute($sql, !empty($options['fetch_sql']) ? true : false);
	}

	public function delete($options = array())
	{
		$this->model = $options['model'];
		$this->parseBind(!empty($options['bind']) ? $options['bind'] : array());
		$sql = 'DELETE FROM ' . $this->parseTable($options['table']) . $this->parseWhere(!empty($options['where']) ? $options['where'] : '') . $this->parseLock(isset($options['lock']) ? $options['lock'] : false) . $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
		return $this->execute($sql, !empty($options['fetch_sql']) ? true : false);
	}
}