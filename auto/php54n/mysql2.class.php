<?php

class Mysql
{
	private $conn;

	function __construct($hostname, $username, $password, $dbname, $charset = "utf8")
	{
		$conn = @mysql_connect($hostname, $username, $password);
		if (!$conn) {
			echo '连接失败，请联系管理员';
			exit;
		}
		$this->hostname = $hostname;
		$this->username = $username;
		$this->password = $password;
		$this->dbname = $dbname;
		$this->conn = $conn;
		$res = mysql_select_db($dbname);
		if (!$res) {
			echo '连接失败，请联系管理员';
			exit;
		}
		mysql_set_charset($charset);
	}

	function __destruct()
	{
		mysql_colse($this->conn);
	}

	function getAll($sql)
	{
		$this->conn = @mysql_connect($this->hostname, $this->username, $this->password);
		mysql_select_db($this->dbname);
		mysql_set_charset("utf8");
		$result = mysql_query($sql, $this->conn);
		$data = array();
		if ($result && mysql_num_rows($result) > 0) {
			while ($row = mysql_fetch_assoc($result)) {
				$data[] = $row;
			}
		}
		return $data;
	}

	function getOne($sql)
	{
		$this->conn = @mysql_connect($this->hostname, $this->username, $this->password);
		mysql_select_db($this->dbname);
		mysql_set_charset("utf8");
		$result = mysql_query($sql, $this->conn);
		$data = array();
		if ($result && mysql_num_rows($result) > 0) {
			$data = mysql_fetch_assoc($result);
		}
		return $data;
	}

	function insert($table, $data)
	{
		$this->conn = @mysql_connect($this->hostname, $this->username, $this->password);
		mysql_select_db($this->dbname);
		mysql_set_charset("utf8");
		$str = '';
		$str .= "INSERT INTO `$table` ";
		$str .= "(`" . implode("`,`", array_keys($data)) . "`) ";
		$str .= " VALUES ";
		$str .= "('" . implode("','", $data) . "')";
		$res = mysql_query($str, $this->conn);
		if ($res && mysql_affected_rows() > 0) {
			return mysql_insert_id();
		} else {
			return false;
		}
	}

	function update($table, $data, $where)
	{
		$this->conn = @mysql_connect($this->hostname, $this->username, $this->password);
		mysql_select_db($this->dbname);
		mysql_set_charset("utf8");
		$sql = 'UPDATE ' . $table . ' SET ';
		foreach ($data as $key => $value) {
			$sql .= "`{$key}`='{$value}',";
		}
		$sql = rtrim($sql, ',');
		$sql .= " WHERE $where";
		$res = mysql_query($sql, $this->conn);
		if ($res && mysql_affected_rows()) {
			return mysql_affected_rows();
		} else {
			return false;
		}
	}

	function del($table, $where)
	{
		$this->conn = @mysql_connect($this->hostname, $this->username, $this->password);
		mysql_select_db($this->dbname);
		mysql_set_charset("utf8");
		$sql = "DELETE FROM `{$table}` WHERE {$where}";
		$res = mysql_query($sql, $this->conn);
		if ($res && mysql_affected_rows()) {
			return mysql_affected_rows();
		} else {
			return false;
		}
	}
}