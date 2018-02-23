<?php
namespace Think\Cache\Driver;

use Think\Cache;

defined('THINK_PATH') or exit();

class Redis extends Cache
{
	public function __construct($options = array())
	{
		if (!extension_loaded('redis')) {
			E(L('_NOT_SUPPORT_') . ':redis');
		}
		$options = array_merge(array('host' => C('REDIS_HOST') ?: '127.0.0.1', 'port' => C('REDIS_PORT') ?: 6379, 'timeout' => C('DATA_CACHE_TIMEOUT') ?: false, 'persistent' => false,), $options);
		$this->options = $options;
		$this->options['expire'] = isset($options['expire']) ? $options['expire'] : C('DATA_CACHE_TIME');
		$this->options['prefix'] = isset($options['prefix']) ? $options['prefix'] : C('DATA_CACHE_PREFIX');
		$this->options['length'] = isset($options['length']) ? $options['length'] : 0;
		$func = $options['persistent'] ? 'pconnect' : 'connect';
		$this->handler = new \Redis;
		$options['timeout'] === false ? $this->handler->$func($options['host'], $options['port']) : $this->handler->$func($options['host'], $options['port'], $options['timeout']);
	}

	public function get($name)
	{
		N('cache_read', 1);
		$value = $this->handler->get($this->options['prefix'] . $name);
		$jsonData = json_decode($value, true);
		return ($jsonData === NULL) ? $value : $jsonData;
	}

	public function set($name, $value, $expire = null)
	{
		N('cache_write', 1);
		if (is_null($expire)) {
			$expire = $this->options['expire'];
		}
		$name = $this->options['prefix'] . $name;
		$value = (is_object($value) || is_array($value)) ? json_encode($value) : $value;
		if (is_int($expire) && $expire) {
			$result = $this->handler->setex($name, $expire, $value);
		} else {
			$result = $this->handler->set($name, $value);
		}
		if ($result && $this->options['length'] > 0) {
			$this->queue($name);
		}
		return $result;
	}

	public function rm($name)
	{
		return $this->handler->delete($this->options['prefix'] . $name);
	}

	public function clear()
	{
		return $this->handler->flushDB();
	}
} 