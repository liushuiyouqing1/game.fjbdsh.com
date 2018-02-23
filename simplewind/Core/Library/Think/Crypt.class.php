<?php
namespace Think;
class Crypt
{
	private static $handler = '';

	public static function init($type = '')
	{
		$type = $type ?: C('DATA_CRYPT_TYPE');
		$class = strpos($type, '\\') ? $type : 'Think\\Crypt\\Driver\\' . ucwords(strtolower($type));
		self::$handler = $class;
	}

	public static function encrypt($data, $key, $expire = 0)
	{
		if (empty(self::$handler)) {
			self::init();
		}
		$class = self::$handler;
		return $class::encrypt($data, $key, $expire);
	}

	public static function decrypt($data, $key)
	{
		if (empty(self::$handler)) {
			self::init();
		}
		$class = self::$handler;
		return $class::decrypt($data, $key);
	}
}