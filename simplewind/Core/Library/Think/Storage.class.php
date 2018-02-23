<?php
namespace Think;
class Storage
{
	static protected $handler;

	static public function connect($type = 'File', $options = array())
	{
		$class = 'Think\\Storage\\Driver\\' . ucwords($type);
		self::$handler = new $class($options);
	}

	static public function __callstatic($method, $args)
	{
		if (method_exists(self::$handler, $method)) {
			return call_user_func_array(array(self::$handler, $method), $args);
		}
	}
} 