<?php
namespace Think\Cache\Driver;

use Think\Cache;

defined('THINK_PATH') or exit();

class Eaccelerator extends Cache
{
	public function __construct($options = array())
	{
		$this->options['expire'] = isset($options['expire']) ? $options['expire'] : C('DATA_CACHE_TIME');
		$this->options['prefix'] = isset($options['prefix']) ? $options['prefix'] : C('DATA_CACHE_PREFIX');
		$this->options['length'] = isset($options['length']) ? $options['length'] : 0;
	}

	public function get($name)
	{
		N('cache_read', 1);
		return eaccelerator_get($this->options['prefix'] . $name);
	}

	public function set($name, $value, $expire = null)
	{
		N('cache_write', 1);
		if (is_null($expire)) {
			$expire = $this->options['expire'];
		}
		$name = $this->options['prefix'] . $name;
		eaccelerator_lock($name);
		if (eaccelerator_put($name, $value, $expire)) {
			if ($this->options['length'] > 0) {
				$this->queue($name);
			}
			return true;
		}
		return false;
	}

	public function rm($name)
	{
		return eaccelerator_rm($this->options['prefix'] . $name);
	}
}