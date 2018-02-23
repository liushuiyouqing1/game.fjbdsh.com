<?php namespace Think\Session\Driver;
class Db
{
	protected $lifeTime = '';
	protected $sessionTable = '';
	protected $hander = array();

	public function open($savePath, $sessName)
	{
		$this->lifeTime = C('SESSION_EXPIRE') ? C('SESSION_EXPIRE') : ini_get('session.gc_maxlifetime');
		$this->sessionTable = C('SESSION_TABLE') ? C('SESSION_TABLE') : C("DB_PREFIX") . "session";
		$host = explode(',', C('DB_HOST'));
		$port = explode(',', C('DB_PORT'));
		$name = explode(',', C('DB_NAME'));
		$user = explode(',', C('DB_USER'));
		$pwd = explode(',', C('DB_PWD'));
		if (1 == C('DB_DEPLOY_TYPE')) {
			if (C('DB_RW_SEPARATE')) {
				$w = floor(mt_rand(0, C('DB_MASTER_NUM') - 1));
				if (is_numeric(C('DB_SLAVE_NO'))) {
					$r = C('DB_SLAVE_NO');
				} else {
					$r = floor(mt_rand(C('DB_MASTER_NUM'), count($host) - 1));
				}
				$hander = mysql_connect($host[$w] . (isset($port[$w]) ? ':' . $port[$w] : ':' . $port[0]), isset($user[$w]) ? $user[$w] : $user[0], isset($pwd[$w]) ? $pwd[$w] : $pwd[0]);
				$dbSel = mysql_select_db(isset($name[$w]) ? $name[$w] : $name[0], $hander);
				if (!$hander || !$dbSel) return false;
				$this->hander[0] = $hander;
				$hander = mysql_connect($host[$r] . (isset($port[$r]) ? ':' . $port[$r] : ':' . $port[0]), isset($user[$r]) ? $user[$r] : $user[0], isset($pwd[$r]) ? $pwd[$r] : $pwd[0]);
				$dbSel = mysql_select_db(isset($name[$r]) ? $name[$r] : $name[0], $hander);
				if (!$hander || !$dbSel) return false;
				$this->hander[1] = $hander;
				return true;
			}
		}
		$r = floor(mt_rand(0, count($host) - 1));
		$hander = mysql_connect($host[$r] . (isset($port[$r]) ? ':' . $port[$r] : ':' . $port[0]), isset($user[$r]) ? $user[$r] : $user[0], isset($pwd[$r]) ? $pwd[$r] : $pwd[0]);
		$dbSel = mysql_select_db(isset($name[$r]) ? $name[$r] : $name[0], $hander);
		if (!$hander || !$dbSel) return false;
		$this->hander = $hander;
		return true;
	}

	public function close()
	{
		if (is_array($this->hander)) {
			$this->gc($this->lifeTime);
			return (mysql_close($this->hander[0]) && mysql_close($this->hander[1]));
		}
		$this->gc($this->lifeTime);
		return mysql_close($this->hander);
	}

	public function read($sessID)
	{
		$hander = is_array($this->hander) ? $this->hander[1] : $this->hander;
		$res = mysql_query('SELECT session_data AS data FROM ' . $this->sessionTable . " WHERE session_id = '$sessID'   AND session_expire >" . time(), $hander);
		if ($res) {
			$row = mysql_fetch_assoc($res);
			return $row['data'];
		}
		return "";
	}

	public function write($sessID, $sessData)
	{
		$hander = is_array($this->hander) ? $this->hander[0] : $this->hander;
		$expire = time() + $this->lifeTime;
		$sessData = addslashes($sessData);
		mysql_query('REPLACE INTO  ' . $this->sessionTable . " (  session_id, session_expire, session_data)  VALUES( '$sessID', '$expire',  '$sessData')", $hander);
		if (mysql_affected_rows($hander)) return true;
		return false;
	}

	public function destroy($sessID)
	{
		$hander = is_array($this->hander) ? $this->hander[0] : $this->hander;
		mysql_query('DELETE FROM ' . $this->sessionTable . " WHERE session_id = '$sessID'", $hander);
		if (mysql_affected_rows($hander)) return true;
		return false;
	}

	public function gc($sessMaxLifeTime)
	{
		$hander = is_array($this->hander) ? $this->hander[0] : $this->hander;
		mysql_query('DELETE FROM ' . $this->sessionTable . ' WHERE session_expire < ' . time(), $hander);
		return mysql_affected_rows($hander);
	}
}