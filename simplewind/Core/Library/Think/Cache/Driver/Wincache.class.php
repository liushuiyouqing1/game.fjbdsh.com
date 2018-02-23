<?php
namespace Think\Cache\Driver;

use Think\Cache;

defined('THINK_PATH') or exit();

class Wincache extends Cache
{
	public function __construct($options = array())
	{
		if (!function_exists('wincache_ucache_info')) {
			E(L('_NOT_SUPPORT_') . ':WinCache');
		}
		$this->options['expire'] = isset($options['expire']) ? $options['expire'] : C('DATA_CACHE_TIME');
		$this->options['prefix'] = isset($options['prefix']) ? $options['prefix'] : C('DATA_CACHE_PREFIX');
		$this->options['length'] = isset($options['length']) ? $options['length'] : 0;
	}

	public function get($name)
	{
		N('cache_read', 1);
		$name = $this->options['prefix'] . $name;
		return wincache_ucache_exists($name) ? wincache_ucache_get($name) : false;
	}

	public function set($name, $value, $expire = null)
	{
		N('cache_write', 1);
		if (is_null($expire)) {
			$expire = $this->options['expire'];
		}
		$name = $this->options['prefix'] . $name;
		if (wincache_ucache_set($name, $value, $expire)) {
			if ($this->options['length'] > 0) {
				$this->queue($name);
			}
			return true;
		}
		return false;
	}

	public function rm($name)
	{
		return wincache_ucache_delete($this->options['prefix'] . $name);
	}

	public function clear()
	{
		return wincache_ucache_clear();
	}
} 