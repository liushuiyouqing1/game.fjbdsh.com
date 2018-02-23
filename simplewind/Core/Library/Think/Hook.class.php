<?php
namespace Think;
class Hook
{
	static private $tags = array();

	static public function add($tag, $name)
	{
		if (!isset(self::$tags[$tag])) {
			self::$tags[$tag] = array();
		}
		if (is_array($name)) {
			self::$tags[$tag] = array_merge(self::$tags[$tag], $name);
		} else {
			self::$tags[$tag][] = $name;
		}
	}

	static public function import($data, $recursive = true)
	{
		if (!$recursive) {
			self::$tags = array_merge(self::$tags, $data);
		} else {
			foreach ($data as $tag => $val) {
				if (!isset(self::$tags[$tag])) self::$tags[$tag] = array();
				if (!empty($val['_overlay'])) {
					unset($val['_overlay']);
					self::$tags[$tag] = $val;
				} else {
					self::$tags[$tag] = array_merge(self::$tags[$tag], $val);
				}
			}
		}
	}

	static public function get($tag = '')
	{
		if (empty($tag)) {
			return self::$tags;
		} else {
			return self::$tags[$tag];
		}
	}

	static public function listen($tag, &$params = NULL)
	{
		if (isset(self::$tags[$tag])) {
			if (APP_DEBUG) {
				G($tag . 'Start');
				trace('[ ' . $tag . ' ] --START--', '', 'INFO');
			}
			foreach (self::$tags[$tag] as $name) {
				APP_DEBUG && G($name . '_start');
				$result = self::exec($name, $tag, $params);
				if (APP_DEBUG) {
					G($name . '_end');
					trace('Run ' . $name . ' [ RunTime:' . G($name . '_start', $name . '_end', 6) . 's ]', '', 'INFO');
				}
				if (false === $result) {
					return;
				}
			}
			if (APP_DEBUG) {
				trace('[ ' . $tag . ' ] --END-- [ RunTime:' . G($tag . 'Start', $tag . 'End', 6) . 's ]', '', 'INFO');
			}
		}
		return;
	}

	static public function listen_one($tag, &$params = NULL)
	{
		if (isset(self::$tags[$tag])) {
			if (APP_DEBUG) {
				G($tag . 'Start');
				trace('[ ' . $tag . ' ] --START--', '', 'INFO');
			}
			if (count(self::$tags[$tag]) > 0) {
				$name = self::$tags[$tag][0];
				APP_DEBUG && G($name . '_start');
				$result = self::exec($name, $tag, $params);
				if (APP_DEBUG) {
					G($name . '_end');
					trace('Run ' . $name . ' [ RunTime:' . G($name . '_start', $name . '_end', 6) . 's ]', '', 'INFO');
				}
				return $result;
			}
			if (APP_DEBUG) {
				trace('[ ' . $tag . ' ] --END-- [ RunTime:' . G($tag . 'Start', $tag . 'End', 6) . 's ]', '', 'INFO');
			}
		}
		return false;
	}

	static public function exec($name, $tag, &$params = NULL)
	{
		if ('Behavior' == substr($name, -8)) {
			$class = $name;
			$tag = 'run';
		} else {
			$class = "plugins\\{$name}\\{$name}Plugin";
		}
		if (class_exists($class)) {
			$addon = new $class();
			return $addon->$tag($params);
		}
	}
} 