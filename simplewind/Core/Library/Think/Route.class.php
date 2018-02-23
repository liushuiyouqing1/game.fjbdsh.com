<?php
namespace Think;
class Route
{
	public static function check()
	{
		$depr = C('URL_PATHINFO_DEPR');
		$regx = preg_replace('/\.' . __EXT__ . '$/i', '', trim($_SERVER['PATH_INFO'], $depr));
		if ('/' != $depr) {
			$regx = str_replace($depr, '/', $regx);
		}
		$maps = C('URL_MAP_RULES');
		if (isset($maps[$regx])) {
			$var = self::parseUrl($maps[$regx]);
			$_GET = array_merge($var, $_GET);
			return true;
		}
		$routes = C('URL_ROUTE_RULES');
		if (!empty($routes)) {
			foreach ($routes as $rule => $route) {
				if (is_numeric($rule)) {
					$rule = array_shift($route);
				}
				if (is_array($route) && isset($route[2])) {
					$options = $route[2];
					if (isset($options['ext']) && __EXT__ != $options['ext']) {
						continue;
					}
					if (isset($options['method']) && REQUEST_METHOD != strtoupper($options['method'])) {
						continue;
					}
					if (!empty($options['callback']) && is_callable($options['callback'])) {
						if (false === call_user_func($options['callback'])) {
							continue;
						}
					}
				}
				if (0 === strpos($rule, '/') && preg_match($rule, $regx, $matches)) {
					if ($route instanceof \Closure) {
						$result = self::invokeRegx($route, $matches);
						return is_bool($result) ? $result : exit;
					} else {
						return self::parseRegex($matches, $route, $regx);
					}
				} else {
					$len1 = substr_count($regx, '/');
					$len2 = substr_count($rule, '/');
					if ($len1 >= $len2 || strpos($rule, '[')) {
						if ('$' == substr($rule, -1, 1)) {
							if ($len1 != $len2) {
								continue;
							} else {
								$rule = substr($rule, 0, -1);
							}
						}
						$match = self::checkUrlMatch($regx, $rule);
						if (false !== $match) {
							if ($route instanceof \Closure) {
								$result = self::invokeRule($route, $match);
								return is_bool($result) ? $result : exit;
							} else {
								return self::parseRule($rule, $route, $regx);
							}
						}
					}
				}
			}
		}
		return false;
	}

	private static function checkUrlMatch($regx, $rule)
	{
		$m1 = explode('/', $regx);
		$m2 = explode('/', $rule);
		$var = array();
		foreach ($m2 as $key => $val) {
			if (0 === strpos($val, '[:')) {
				$val = substr($val, 1, -1);
			}
			if (':' == substr($val, 0, 1)) {
				if ($pos = strpos($val, '|')) {
					$val = substr($val, 1, $pos - 1);
				}
				if (strpos($val, '\\')) {
					$type = substr($val, -1);
					if ('d' == $type) {
						if (isset($m1[$key]) && !is_numeric($m1[$key])) return false;
					}
					$name = substr($val, 1, -2);
				} elseif ($pos = strpos($val, '^')) {
					$array = explode('-', substr(strstr($val, '^'), 1));
					if (in_array($m1[$key], $array)) {
						return false;
					}
					$name = substr($val, 1, $pos - 1);
				} else {
					$name = substr($val, 1);
				}
				$var[$name] = isset($m1[$key]) ? $m1[$key] : '';
			} elseif (0 !== strcasecmp($val, $m1[$key])) {
				return false;
			}
		}
		return $var;
	}

	private static function parseUrl($url)
	{
		$var = array();
		if (false !== strpos($url, '?')) {
			$info = parse_url($url);
			$path = explode('/', $info['path']);
			parse_str($info['query'], $var);
		} elseif (strpos($url, '/')) {
			$path = explode('/', $url);
		} else {
			parse_str($url, $var);
		}
		if (isset($path)) {
			$var[C('VAR_ACTION')] = array_pop($path);
			if (!empty($path)) {
				$var[C('VAR_CONTROLLER')] = array_pop($path);
			}
			if (!empty($path)) {
				$var[C('VAR_MODULE')] = array_pop($path);
			}
		}
		return $var;
	}

	private static function parseRule($rule, $route, $regx)
	{
		$url = is_array($route) ? $route[0] : $route;
		$paths = explode('/', $regx);
		$matches = array();
		$rule = explode('/', $rule);
		foreach ($rule as $item) {
			$fun = '';
			if (0 === strpos($item, '[:')) {
				$item = substr($item, 1, -1);
			}
			if (0 === strpos($item, ':')) {
				if ($pos = strpos($item, '|')) {
					$fun = substr($item, $pos + 1);
					$item = substr($item, 0, $pos);
				}
				if ($pos = strpos($item, '^')) {
					$var = substr($item, 1, $pos - 1);
				} elseif (strpos($item, '\\')) {
					$var = substr($item, 1, -2);
				} else {
					$var = substr($item, 1);
				}
				$matches[$var] = !empty($fun) ? $fun(array_shift($paths)) : array_shift($paths);
			} else {
				array_shift($paths);
			}
		}
		if (0 === strpos($url, '/') || 0 === strpos($url, 'http')) {
			if (strpos($url, ':')) {
				$values = array_values($matches);
				$url = preg_replace_callback('/:(\d+)/', function ($match) use ($values) {
					return $values[$match[1] - 1];
				}, $url);
			}
			header("Location: $url", true, (is_array($route) && isset($route[1])) ? $route[1] : 301);
			exit;
		} else {
			$var = self::parseUrl($url);
			$values = array_values($matches);
			foreach ($var as $key => $val) {
				if (0 === strpos($val, ':')) {
					$var[$key] = $values[substr($val, 1) - 1];
				}
			}
			$var = array_merge($matches, $var);
			if (!empty($paths)) {
				preg_replace_callback('/(\w+)\/([^\/]+)/', function ($match) use (&$var) {
					$var[strtolower($match[1])] = strip_tags($match[2]);
				}, implode('/', $paths));
			}
			if (is_array($route) && isset($route[1])) {
				if (is_array($route[1])) {
					$params = $route[1];
				} else {
					parse_str($route[1], $params);
				}
				$var = array_merge($var, $params);
			}
			$_GET = array_merge($var, $_GET);
		}
		return true;
	}

	private static function parseRegex($matches, $route, $regx)
	{
		$url = is_array($route) ? $route[0] : $route;
		$url = preg_replace_callback('/:(\d+)/', function ($match) use ($matches) {
			return $matches[$match[1]];
		}, $url);
		if (0 === strpos($url, '/') || 0 === strpos($url, 'http')) {
			header("Location: $url", true, (is_array($route) && isset($route[1])) ? $route[1] : 301);
			exit;
		} else {
			$var = self::parseUrl($url);
			foreach ($var as $key => $val) {
				if (strpos($val, '|')) {
					list($val, $fun) = explode('|', $val);
					$var[$key] = $fun($val);
				}
			}
			$regx = substr_replace($regx, '', 0, strlen($matches[0]));
			if ($regx) {
				preg_replace_callback('/(\w+)\/([^\/]+)/', function ($match) use (&$var) {
					$var[strtolower($match[1])] = strip_tags($match[2]);
				}, $regx);
			}
			if (is_array($route) && isset($route[1])) {
				if (is_array($route[1])) {
					$params = $route[1];
				} else {
					parse_str($route[1], $params);
				}
				$var = array_merge($var, $params);
			}
			$_GET = array_merge($var, $_GET);
		}
		return true;
	}

	static private function invokeRegx($closure, $var = array())
	{
		$reflect = new \ReflectionFunction($closure);
		$params = $reflect->getParameters();
		$args = array();
		array_shift($var);
		foreach ($params as $param) {
			if (!empty($var)) {
				$args[] = array_shift($var);
			} elseif ($param->isDefaultValueAvailable()) {
				$args[] = $param->getDefaultValue();
			}
		}
		return $reflect->invokeArgs($args);
	}

	static private function invokeRule($closure, $var = array())
	{
		$reflect = new \ReflectionFunction($closure);
		$params = $reflect->getParameters();
		$args = array();
		foreach ($params as $param) {
			$name = $param->getName();
			if (isset($var[$name])) {
				$args[] = $var[$name];
			} elseif ($param->isDefaultValueAvailable()) {
				$args[] = $param->getDefaultValue();
			}
		}
		return $reflect->invokeArgs($args);
	}
}