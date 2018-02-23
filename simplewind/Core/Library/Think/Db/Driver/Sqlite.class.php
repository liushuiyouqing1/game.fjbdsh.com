<?php
namespace Think\Db\Driver;

use Think\Db\Driver;

class Sqlite extends Driver
{
	protected function parseDsn($config)
	{
		$dsn = 'sqlite:' . $config['database'];
		return $dsn;
	}

	public function getFields($tableName)
	{
		list($tableName) = explode(' ', $tableName);
		$result = $this->query('PRAGMA table_info( ' . $tableName . ' )');
		$info = array();
		if ($result) {
			foreach ($result as $key => $val) {
				$info[$val['field']] = array('name' => $val['field'], 'type' => $val['type'], 'notnull' => (bool)($val['null'] === ''), 'default' => $val['default'], 'primary' => (strtolower($val['dey']) == 'pri'), 'autoinc' => (strtolower($val['extra']) == 'auto_increment'),);
			}
		}
		return $info;
	}

	public function getTables($dbName = '')
	{
		$result = $this->query("SELECT name FROM sqlite_master WHERE type='table' " . "UNION ALL SELECT name FROM sqlite_temp_master " . "WHERE type='table' ORDER BY name");
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
			if (count($limit) > 1) {
				$limitStr .= ' LIMIT ' . $limit[1] . ' OFFSET ' . $limit[0] . ' ';
			} else {
				$limitStr .= ' LIMIT ' . $limit[0] . ' ';
			}
		}
		return $limitStr;
	}
} 