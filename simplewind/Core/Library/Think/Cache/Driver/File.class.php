<?php
namespace Think\Cache\Driver;

use Think\Cache;

defined('THINK_PATH') or exit();

class File extends Cache
{
	public function __construct($options = array())
	{
		if (!empty($options)) {
			$this->options = $options;
		}
		$this->options['temp'] = !empty($options['temp']) ? $options['temp'] : C('DATA_CACHE_PATH');
		$this->options['prefix'] = isset($options['prefix']) ? $options['prefix'] : C('DATA_CACHE_PREFIX');
		$this->options['expire'] = isset($options['expire']) ? $options['expire'] : C('DATA_CACHE_TIME');
		$this->options['length'] = isset($options['length']) ? $options['length'] : 0;
		if (substr($this->options['temp'], -1) != '/') $this->options['temp'] .= '/';
		$this->init();
	}

	private function init()
	{
		if (!is_dir($this->options['temp'])) {
			mkdir($this->options['temp']);
		}
	}

	private function filename($name)
	{
		$name = md5(C('DATA_CACHE_KEY') . $name);
		if (C('DATA_CACHE_SUBDIR')) {
			$dir = '';
			for ($i = 0; $i < C('DATA_PATH_LEVEL'); $i++) {
				$dir .= $name{$i} . '/';
			}
			if (!is_dir($this->options['temp'] . $dir)) {
				mkdir($this->options['temp'] . $dir, 0755, true);
			}
			$filename = $dir . $this->options['prefix'] . $name . '.php';
		} else {
			$filename = $this->options['prefix'] . $name . '.php';
		}
		return $this->options['temp'] . $filename;
	}

	public function get($name)
	{
		$filename = $this->filename($name);
		if (!is_file($filename)) {
			return false;
		}
		N('cache_read', 1);
		$content = file_get_contents($filename);
		if (false !== $content) {
			$expire = (int)substr($content, 8, 12);
			if ($expire != 0 && time() > filemtime($filename) + $expire) {
				unlink($filename);
				return false;
			}
			if (C('DATA_CACHE_CHECK')) {
				$check = substr($content, 20, 32);
				$content = substr($content, 52, -3);
				if ($check != md5($content)) {
					return false;
				}
			} else {
				$content = substr($content, 20, -3);
			}
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
		N('cache_write', 1);
		if (is_null($expire)) {
			$expire = $this->options['expire'];
		}
		$filename = $this->filename($name);
		$data = serialize($value);
		if (C('DATA_CACHE_COMPRESS') && function_exists('gzcompress')) {
			$data = gzcompress($data, 3);
		}
		if (C('DATA_CACHE_CHECK')) {
			$check = md5($data);
		} else {
			$check = '';
		}
		$data = "<?php\n//" . sprintf('%012d', $expire) . $check . $data . "\n?>";
		$result = file_put_contents($filename, $data);
		if ($result) {
			if ($this->options['length'] > 0) {
				$this->queue($name);
			}
			clearstatcache();
			return true;
		} else {
			return false;
		}
	}

	public function rm($name)
	{
		return unlink($this->filename($name));
	}

	public function clear()
	{
		$path = $this->options['temp'];
		$files = scandir($path);
		if ($files) {
			foreach ($files as $file) {
				if ($file != '.' && $file != '..' && is_dir($path . $file)) {
					array_map('unlink', glob($path . $file . '/*.*'));
				} elseif (is_file($path . $file)) {
					unlink($path . $file);
				}
			}
			return true;
		}
		return false;
	}
}