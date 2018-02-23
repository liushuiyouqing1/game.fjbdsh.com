<?php
namespace Think\Cache\Driver;

use Think\Cache;

defined('THINK_PATH') or exit();

class Xcache extends Cache
{
	public function __construct($options = array())
	{
		if (!function_exists('xcache_info')) {
			E(L('_NOT_SUPPORT_') . ':Xcache');
		}
		$this->options['expire'] = isset($options['expire']) ? $options['expire'] : C('DATA_CACHE_TIME');
		$this->options['prefix'] = isset($options['prefix']) ? $options['prefix'] : C('DATA_CACHE_PREFIX');
		$this->options['length'] = isset($options['length']) ? $options['length'] : 0;
	}

	public function get($name)
	{
		N('cache_read', 1);
		$name = $this->options['prefix'] . $name;
		if (xcache_isset($name)) {
			return xcache_get($name);
		}
		return false;
	}

	public function set($name, $value, $expire = null)
	{
		N('cache_write', 1);
		if (is_null($expire)) {
			$expire = $this->options['expire'];
		}
		$name = $this->options['prefix'] . $name;
		if (xcache_set($name, $value, $expire)) {
			if ($this->options['length'] > 0) {
				$this->queue($name);
			}
			return true;
		}
		return false;
	}

	public function rm($name)
	{
		return xcache_unset($this->options['prefix'] . $name);
	}

	public function clear()
	{
		return xcache_clear_cache(1, -1);
	}
} 