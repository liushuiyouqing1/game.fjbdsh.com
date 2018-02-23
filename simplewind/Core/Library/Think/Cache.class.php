<?php
namespace Think;
class Cache
{
	protected $handler;
	protected $options = array();

	public function connect($type = '', $options = array())
	{
		if (empty($type)) $type = C('DATA_CACHE_TYPE');
		$class = strpos($type, '\\') ? $type : 'Think\\Cache\\Driver\\' . ucwords(strtolower($type));
		if (class_exists($class)) $cache = new $class($options); else E(L('_CACHE_TYPE_INVALID_') . ':' . $type);
		return $cache;
	}

	static function getInstance($type = '', $options = array())
	{
		static $_instance = array();
		$guid = $type . to_guid_string($options);
		if (!isset($_instance[$guid])) {
			$obj = new Cache();
			$_instance[$guid] = $obj->connect($type, $options);
		}
		return $_instance[$guid];
	}

	public function __get($name)
	{
		return $this->get($name);
	}

	public function __set($name, $value)
	{
		return $this->set($name, $value);
	}

	public function __unset($name)
	{
		$this->rm($name);
	}

	public function setOptions($name, $value)
	{
		$this->options[$name] = $value;
	}

	public function getOptions($name)
	{
		return $this->options[$name];
	}

	protected function queue($key)
	{
		static $_handler = array('file' => array('F', 'F'), 'xcache' => array('xcache_get', 'xcache_set'), 'apc' => array('apc_fetch', 'apc_store'),);
		$queue = isset($this->options['queue']) ? $this->options['queue'] : 'file';
		$fun = isset($_handler[$queue]) ? $_handler[$queue] : $_handler['file'];
		$queue_name = isset($this->options['queue_name']) ? $this->options['queue_name'] : 'think_queue';
		$value = $fun[0]($queue_name);
		if (!$value) {
			$value = array();
		}
		if (false === array_search($key, $value)) array_push($value, $key);
		if (count($value) > $this->options['length']) {
			$key = array_shift($value);
			$this->rm($key);
			if (APP_DEBUG) {
				N($queue_name . '_out_times', 1);
			}
		}
		return $fun[1]($queue_name, $value);
	}

	public function __call($method, $args)
	{
		if (method_exists($this->handler, $method)) {
			return call_user_func_array(array($this->handler, $method), $args);
		} else {
			E(__CLASS__ . ':' . $method . L('_METHOD_NOT_EXIST_'));
			return;
		}
	}
}