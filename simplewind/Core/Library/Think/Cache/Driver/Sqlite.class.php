<?php
namespace Think\Cache\Driver;

use Think\Cache;

defined('THINK_PATH') or exit();

class Sqlite extends Cache
{
	public function __construct($options = array())
	{
		if (!extension_loaded('sqlite')) {
			E(L('_NOT_SUPPORT_') . ':sqlite');
		}
		if (empty($options)) {
			$options = array('db' => ':memory:', 'table' => 'sharedmemory',);
		}
		$this->options = $options;
		$this->options['prefix'] = isset($options['prefix']) ? $options['prefix'] : C('DATA_CACHE_PREFIX');
		$this->options['length'] = isset($options['length']) ? $options['length'] : 0;
		$this->options['expire'] = isset($options['expire']) ? $options['expire'] : C('DATA_CACHE_TIME');
		$func = $this->options['persistent'] ? 'sqlite_popen' : 'sqlite_open';
		$this->handler = $func($this->options['db']);
	}

	public function get($name)
	{
		N('cache_read', 1);
		$name = $this->options['prefix'] . sqlite_escape_string($name);
		$sql = 'SELECT value FROM ' . $this->options['table'] . ' WHERE var=\'' . $name . '\' AND (expire=0 OR expire >' . time() . ') LIMIT 1';
		$result = sqlite_query($this->handler, $sql);
		if (sqlite_num_rows($result)) {
			$content = sqlite_fetch_single($result);
			if (C('DATA_CACHE_COMPRESS') && function_exists('gzcompress')) {
				$content = gzuncompress($content);
			}
			return unserialize($content);
		}
		return false;
	}

	public function set($name, $value, $expire = null)
	{
		N('cache_write', 1);
		$name = $this->options['prefix'] . sqlite_escape_string($name);
		$value = sqlite_escape_string(serialize($value));
		if (is_null($expire)) {
			$expire = $this->options['expire'];
		}
		$expire = ($expire == 0) ? 0 : (time() + $expire);
		if (C('DATA_CACHE_COMPRESS') && function_exists('gzcompress')) {
			$value = gzcompress($value, 3);
		}
		$sql = 'REPLACE INTO ' . $this->options['table'] . ' (var, value,expire) VALUES (\'' . $name . '\', \'' . $value . '\', \'' . $expire . '\')';
		if (sqlite_query($this->handler, $sql)) {
			if ($this->options['length'] > 0) {
				$this->queue($name);
			}
			return true;
		}
		return false;
	}

	public function rm($name)
	{
		$name = $this->options['prefix'] . sqlite_escape_string($name);
		$sql = 'DELETE FROM ' . $this->options['table'] . ' WHERE var=\'' . $name . '\'';
		sqlite_query($this->handler, $sql);
		return true;
	}

	public function clear()
	{
		$sql = 'DELETE FROM ' . $this->options['table'];
		sqlite_query($this->handler, $sql);
		return;
	}
} 