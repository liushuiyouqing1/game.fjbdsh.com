<?php
namespace Behavior;
class CheckActionRouteBehavior
{
	public function run(&$config)
	{
		$regx = trim($_SERVER['PATH_INFO'], '/');
		if (empty($regx)) return;
		$routes = $config['routes'];
		if (!empty($routes)) {
			$depr = C('URL_PATHINFO_DEPR');
			$regx = str_replace($depr, '/', $regx);
			$regx = substr_replace($regx, '', 0, strlen(__URL__));
			foreach ($routes as $rule => $route) {
				if (0 === strpos($rule, '/') && preg_match($rule, $regx, $matches)) {
					return C('ACTION_NAME', $this->parseRegex($matches, $route, $regx));
				} else {
					$len1 = substr_count($regx, '/');
					$len2 = substr_count($rule, '/');
					if ($len1 >= $len2) {
						if ('$' == substr($rule, -1, 1)) {
							if ($len1 != $len2) {
								continue;
							} else {
								$rule = substr($rule, 0, -1);
							}
						}
						$match = $this->checkUrlMatch($regx, $rule);
						if ($match) return C('ACTION_NAME', $this->parseRule($rule, $route, $regx));
					}
				}
			}
		}
	}

	private function checkUrlMatch($regx, $rule)
	{
		$m1 = explode('/', $regx);
		$m2 = explode('/', $rule);
		$match = true;
		foreach ($m2 as $key => $val) {
			if (':' == substr($val, 0, 1)) {
				if (strpos($val, '\\')) {
					$type = substr($val, -1);
					if ('d' == $type && !is_numeric($m1[$key])) {
						$match = false;
						break;
					}
				} elseif (strpos($val, '^')) {
					$array = explode('|', substr(strstr($val, '^'), 1));
					if (in_array($m1[$key], $array)) {
						$match = false;
						break;
					}
				}
			} elseif (0 !== strcasecmp($val, $m1[$key])) {
				$match = false;
				break;
			}
		}
		return $match;
	}

	private function parseUrl($url)
	{
		$var = array();
		if (false !== strpos($url, '?')) {
			$info = parse_url($url);
			$path = $info['path'];
			parse_str($info['query'], $var);
		} else {
			$path = $url;
		}
		$var[C('VAR_ACTION')] = $path;
		return $var;
	}

	private function parseRule($rule, $route, $regx)
	{
		$url = is_array($route) ? $route[0] : $route;
		$paths = explode('/', $regx);
		$matches = array();
		$rule = explode('/', $rule);
		foreach ($rule as $item) {
			if (0 === strpos($item, ':')) {
				if ($pos = strpos($item, '^')) {
					$var = substr($item, 1, $pos - 1);
				} elseif (strpos($item, '\\')) {
					$var = substr($item, 1, -2);
				} else {
					$var = substr($item, 1);
				}
				$matches[$var] = array_shift($paths);
			} else {
				array_shift($paths);
			}
		}
		if (0 === strpos($url, '/') || 0 === strpos($url, 'http')) {
			if (strpos($url, ':')) {
				$values = array_values($matches);
				$url = preg_replace('/:(\d+)/e', '$values[\\1-1]', $url);
			}
			header("Location: $url", true, (is_array($route) && isset($route[1])) ? $route[1] : 301);
			exit;
		} else {
			$var = $this->parseUrl($url);
			$values = array_values($matches);
			foreach ($var as $key => $val) {
				if (0 === strpos($val, ':')) {
					$var[$key] = $values[substr($val, 1) - 1];
				}
			}
			$var = array_merge($matches, $var);
			if ($paths) {
				preg_replace('@(\w+)\/([^\/]+)@e', '$var[strtolower(\'\\1\')]=strip_tags(\'\\2\');', implode('/', $paths));
			}
			if (is_array($route) && isset($route[1])) {
				parse_str($route[1], $params);
				$var = array_merge($var, $params);
			}
			$action = $var[C('VAR_ACTION')];
			unset($var[C('VAR_ACTION')]);
			$_GET = array_merge($var, $_GET);
			return $action;
		}
	}

	private function parseRegex($matches, $route, $regx)
	{
		$url = is_array($route) ? $route[0] : $route;
		$url = preg_replace('/:(\d+)/e', '$matches[\\1]', $url);
		if (0 === strpos($url, '/') || 0 === strpos($url, 'http')) {
			header("Location: $url", true, (is_array($route) && isset($route[1])) ? $route[1] : 301);
			exit;
		} else {
			$var = $this->parseUrl($url);
			$regx = substr_replace($regx, '', 0, strlen($matches[0]));
			if ($regx) {
				preg_replace('@(\w+)\/([^,\/]+)@e', '$var[strtolower(\'\\1\')]=strip_tags(\'\\2\');', $regx);
			}
			if (is_array($route) && isset($route[1])) {
				parse_str($route[1], $params);
				$var = array_merge($var, $params);
			}
			$action = $var[C('VAR_ACTION')];
			unset($var[C('VAR_ACTION')]);
			$_GET = array_merge($var, $_GET);
		}
		return $action;
	}
}