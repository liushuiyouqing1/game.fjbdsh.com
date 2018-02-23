<?php
namespace Aliyun\Common\Utilities;
class AssertUtils
{
	public static function assertContains($needle, array $array)
	{
		if (is_array($needle)) {
			foreach ($needle as $key) {
				if (!array_key_exists($key, $array)) {
					throw new \InvalidArgumentException("[{$key}] was not be contained.");
				}
			}
			return;
		}
		if (is_string($needle)) {
			if (!array_key_exists($needle, $array)) {
				throw new \InvalidArgumentException("[{$needle}] was not be contained.");
			}
			return;
		}
		self::makeError('assertConatins can only used for string or array');
	}

	public static function assertSet($needle, array $array)
	{
		if (is_array($needle)) {
			foreach ($needle as $key) {
				if (!isset($array[$key])) {
					throw new \InvalidArgumentException("Key [{$key}] was not set.");
				}
			}
			return;
		}
		if (is_string($needle)) {
			if (!isset($array[$needle])) {
				throw new \InvalidArgumentException("Key [{$needle}] was not set.");
			}
		}
	}

	public static function assertNotNull($value, $name)
	{
		if (!isset($value)) {
			throw new \InvalidArgumentException("'{$name}' cannot be null.");
		}
	}

	public static function assertNotEmpty($value, $name)
	{
		if (empty($value)) {
			throw new \InvalidArgumentException("[{$name}] cannot be empty.");
		}
	}

	public static function assertString($value, $name)
	{
		if (!is_string($value)) {
			throw new \InvalidArgumentException("[{$name}] must be string.");
		}
	}

	public static function assertNumber($value, $name)
	{
		if (!is_numeric($value)) {
			throw new \InvalidArgumentException("[{$name}] must be a number.");
		}
	}

	public static function assertArray($value, $name)
	{
		if (!is_array($value)) {
			throw new \InvalidArgumentException("[{$name}] must be array.");
		}
	}

	public static function makeError($msg)
	{
		echo 'Error: ' . $msg;
		die();
	}
} 