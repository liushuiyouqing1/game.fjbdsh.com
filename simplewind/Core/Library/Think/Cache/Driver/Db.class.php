<?php
namespace Think\Cache\Driver;

use Think\Cache;

defined('THINK_PATH') or exit();

class Db extends Cache
{
	public function __construct($options = array())
	{
		if (empty($options)) {
			$options = array('table' => C('DATA_CACHE_TABLE'),);
		}
		$this->options = $options;
		$this->options['prefix'] = isset($options['prefix']) ? $options['prefix'] : C('DATA_CACHE_PREFIX');
		$this->options['length'] = isset($options['length']) ? $options['length'] : 0;
		$this->options['expire'] = isset($options['expire']) ? $options['expire'] : C('DATA_CACHE_TIME');
		$this->handler = \Think\Db::getInstance();
	}

	public function get($name)
	{
		$name = $this->options['prefix'] . addslashes($name);
		N('cache_read', 1);
		$result = $this->handler->query('SELECT `data`,`datacrc` FROM `' . $this->options['table'] . '` WHERE `cachekey`=\'' . $name . '\' AND (`expire` =0 OR `expire`>' . time() . ') LIMIT 0,1');
		if (false !== $result) {
			$result = $result[0];
			if (C('DATA_CACHE_CHECK')) {
				if ($result['datacrc'] != md5($result['data'])) {
					return false;
				}
			}
			$content = $result['data'];
			if (C('DATA_CACHE_COMPRESS') && function_exists('gzcompress')) {
				$content = gzuncompress($content);
			}
			$content = unserialize($content);
			return $content;
		} else {
			return false;
		}
	}

	public function set($name, $value, $expire = null)
	{
		$data = serialize($value);
		$name = $this->options['prefix'] . addslashes($name);
		N('cache_write', 1);
		if (C('DATA_CACHE_COMPRESS') && function_exists('gzcompress')) {
			$data = gzcompress($data, 3);
		}
		if (C('DATA_CACHE_CHECK')) {
			$crc = md5($data);
		} else {
			$crc = '';
		}
		if (is_null($expire)) {
			$expire = $this->options['expire'];
		}
		$expire = ($expire == 0) ? 0 : (time() + $expire);
		$result = $this->handler->query('select `cachekey` from `' . $this->options['table'] . '` where `cachekey`=\'' . $name . '\' limit 0,1');
		if (!empty($result)) {
			$result = $this->handler->execute('UPDATE ' . $this->options['table'] . ' SET data=\'' . $data . '\' ,datacrc=\'' . $crc . '\',expire=' . $expire . ' WHERE `cachekey`=\'' . $name . '\'');
		} else {
			$result = $this->handler->execute('INSERT INTO ' . $this->options['table'] . ' (`cachekey`,`data`,`datacrc`,`expire`) VALUES (\'' . $name . '\',\'' . $data . '\',\'' . $crc . '\',' . $expire . ')');
		}
		if ($result) {
			if ($this->options['length'] > 0) {
				$this->queue($name);
			}
			return true;
		} else {
			return false;
		}
	}

	public function rm($name)
	{
		$name = $this->options['prefix'] . addslashes($name);
		return $this->handler->execute('DELETE FROM `' . $this->options['table'] . '` WHERE `cachekey`=\'' . $name . '\'');
	}

	public function clear()
	{
		return $this->handler->execute('TRUNCATE TABLE `' . $this->options['table'] . '`');
	}
}