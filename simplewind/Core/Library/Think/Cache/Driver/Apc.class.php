<?php
namespace Think\Cache\Driver;

use Think\Cache;

defined('THINK_PATH') or exit();

class Apc extends Cache
{
	public function __construct($options = array())
	{
		if (!function_exists('apc_cache_info')) {
			E(L('_NOT_SUPPORT_') . ':Apc');
		}
		$this->options['prefix'] = isset($options['prefix']) ? $options['prefix'] : C('DATA_CACHE_PREFIX');
		$this->options['length'] = isset($options['length']) ? $options['length'] : 0;
		$this->options['expire'] = isset($options['expire']) ? $options['expire'] : C('DATA_CACHE_TIME');
	}

	public function get($name)
	{
		N('cache_read', 1);
		return apc_fetch($this->options['prefix'] . $name);
	}

	public function set($name, $value, $expire = null)
	{
		N('cache_write', 1);
		if (is_null($expire)) {
			$expire = $this->options['expire'];
		}
		$name = $this->options['prefix'] . $name;
		if ($result = apc_store($name, $value, $expire)) {
			if ($this->options['length'] > 0) {
				$this->queue($name);
			}
		}
		return $result;
	}

	public function rm($name)
	{
		return apc_delete($this->options['prefix'] . $name);
	}

	public function clear()
	{
		return apc_clear_cache();
	}
} 