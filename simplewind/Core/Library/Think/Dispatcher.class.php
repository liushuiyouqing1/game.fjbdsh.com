<?php
namespace Think;
class Dispatcher
{
	static public function dispatch()
	{
		$varPath = C('VAR_PATHINFO');
		$varAddon = C('VAR_ADDON');
		$varModule = C('VAR_MODULE');
		$varController = C('VAR_CONTROLLER');
		$varAction = C('VAR_ACTION');
		$urlCase = C('URL_CASE_INSENSITIVE');
		if (isset($_GET[$varPath])) {
			$_SERVER['PATH_INFO'] = $_GET[$varPath];
			unset($_GET[$varPath]);
		} elseif (IS_CLI) {
			$_SERVER['PATH_INFO'] = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '';
		}
		if (C('APP_SUB_DOMAIN_DEPLOY')) {
			$rules = C('APP_SUB_DOMAIN_RULES');
			if (isset($rules[$_SERVER['HTTP_HOST']])) {
				define('APP_DOMAIN', $_SERVER['HTTP_HOST']);
				$rule = $rules[APP_DOMAIN];
			} else {
				if (strpos(C('APP_DOMAIN_SUFFIX'), '.')) {
					$domain = array_slice(explode('.', $_SERVER['HTTP_HOST']), 0, -3);
				} else {
					$domain = array_slice(explode('.', $_SERVER['HTTP_HOST']), 0, -2);
				}
				if (!empty($domain)) {
					$subDomain = implode('.', $domain);
					define('SUB_DOMAIN', $subDomain);
					$domain2 = array_pop($domain);
					if ($domain) {
						$domain3 = array_pop($domain);
					}
					if (isset($rules[$subDomain])) {
						$rule = $rules[$subDomain];
					} elseif (isset($rules['*.' . $domain2]) && !empty($domain3)) {
						$rule = $rules['*.' . $domain2];
						$panDomain = $domain3;
					} elseif (isset($rules['*']) && !empty($domain2) && 'www' != $domain2) {
						$rule = $rules['*'];
						$panDomain = $domain2;
					}
				}
			}
			if (!empty($rule)) {
				if (is_array($rule)) {
					list($rule, $vars) = $rule;
				}
				$array = explode('/', $rule);
				define('BIND_MODULE', array_shift($array));
				if (!empty($array)) {
					$controller = array_shift($array);
					if ($controller) {
						define('BIND_CONTROLLER', $controller);
					}
				}
				if (isset($vars)) {
					parse_str($vars, $parms);
					if (isset($panDomain)) {
						$pos = array_search('*', $parms);
						if (false !== $pos) {
							$parms[$pos] = $panDomain;
						}
					}
					$_GET = array_merge($_GET, $parms);
				}
			}
		}
		if (!isset($_SERVER['PATH_INFO'])) {
			$types = explode(',', C('URL_PATHINFO_FETCH'));
			foreach ($types as $type) {
				if (0 === strpos($type, ':')) {
					$_SERVER['PATH_INFO'] = call_user_func(substr($type, 1));
					break;
				} elseif (!empty($_SERVER[$type])) {
					$_SERVER['PATH_INFO'] = (0 === strpos($_SERVER[$type], $_SERVER['SCRIPT_NAME'])) ? substr($_SERVER[$type], strlen($_SERVER['SCRIPT_NAME'])) : $_SERVER[$type];
					break;
				}
			}
		}
		$depr = C('URL_PATHINFO_DEPR');
		define('MODULE_PATHINFO_DEPR', $depr);
		if (empty($_SERVER['PATH_INFO'])) {
			$_SERVER['PATH_INFO'] = '';
			define('__INFO__', '');
			define('__EXT__', '');
		} else {
			define('__INFO__', trim($_SERVER['PATH_INFO'], '/'));
			define('__EXT__', strtolower(pathinfo($_SERVER['PATH_INFO'], PATHINFO_EXTENSION)));
			$_SERVER['PATH_INFO'] = __INFO__;
			if (!defined('BIND_MODULE') && (!C('URL_ROUTER_ON') || !Route::check())) {
				if (__INFO__ && C('MULTI_MODULE')) {
					$paths = explode($depr, __INFO__, 2);
					$allowList = C('MODULE_ALLOW_LIST');
					$module = preg_replace('/\.' . __EXT__ . '$/i', '', $paths[0]);
					if (empty($allowList) || (is_array($allowList) && in_array_case($module, $allowList))) {
						$_GET[$varModule] = $module;
						$_SERVER['PATH_INFO'] = isset($paths[1]) ? $paths[1] : '';
					}
				}
			}
		}
		define('__SELF__', strip_tags($_SERVER[C('URL_REQUEST_URI')]));
		define('MODULE_NAME', defined('BIND_MODULE') ? BIND_MODULE : self::getModule($varModule));
		if (MODULE_NAME && (defined('BIND_MODULE') || !in_array_case(MODULE_NAME, C('MODULE_DENY_LIST'))) && is_dir(APP_PATH . MODULE_NAME)) {
			define('MODULE_PATH', APP_PATH . MODULE_NAME . '/');
			C('CACHE_PATH', CACHE_PATH . MODULE_NAME . '/');
			C('LOG_PATH', realpath(LOG_PATH) . '/' . MODULE_NAME . '/');
			Hook::listen('module_check');
			if (is_file(MODULE_PATH . 'Conf/config' . CONF_EXT)) C(load_config(MODULE_PATH . 'Conf/config' . CONF_EXT));
			if ('common' != APP_MODE && is_file(MODULE_PATH . 'Conf/config_' . APP_MODE . CONF_EXT)) C(load_config(MODULE_PATH . 'Conf/config_' . APP_MODE . CONF_EXT));
			if (APP_STATUS && is_file(MODULE_PATH . 'Conf/' . APP_STATUS . CONF_EXT)) C(load_config(MODULE_PATH . 'Conf/' . APP_STATUS . CONF_EXT));
			if (is_file(MODULE_PATH . 'Conf/alias.php')) Think::addMap(include MODULE_PATH . 'Conf/alias.php');
			if (is_file(MODULE_PATH . 'Conf/tags.php')) Hook::import(include MODULE_PATH . 'Conf/tags.php');
			if (is_file(MODULE_PATH . 'Common/function.php')) include MODULE_PATH . 'Common/function.php';
			$urlCase = C('URL_CASE_INSENSITIVE');
			load_ext_file(MODULE_PATH);
		} else {
			E(L('_MODULE_NOT_EXIST_') . ':' . MODULE_NAME);
		}
		if (!defined('__APP__')) {
			$urlMode = C('URL_MODEL');
			if ($urlMode == URL_COMPAT) {
				define('PHP_FILE', _PHP_FILE_ . '?' . $varPath . '=');
			} elseif ($urlMode == URL_REWRITE) {
				$url = dirname(_PHP_FILE_);
				if ($url == '/' || $url == '\\') $url = '';
				define('PHP_FILE', $url);
			} else {
				define('PHP_FILE', _PHP_FILE_);
			}
			define('__APP__', strip_tags(PHP_FILE));
		}
		$moduleName = defined('MODULE_ALIAS') ? MODULE_ALIAS : MODULE_NAME;
		define('__MODULE__', (defined('BIND_MODULE') || !C('MULTI_MODULE')) ? __APP__ : __APP__ . '/' . ($urlCase ? strtolower($moduleName) : $moduleName));
		if ('' != $_SERVER['PATH_INFO'] && (!C('URL_ROUTER_ON') || !Route::check())) {
			Hook::listen('path_info');
			if (C('URL_DENY_SUFFIX') && preg_match('/\.(' . trim(C('URL_DENY_SUFFIX'), '.') . ')$/i', $_SERVER['PATH_INFO'])) {
				send_http_status(404);
				exit;
			}
			$_SERVER['PATH_INFO'] = preg_replace(C('URL_HTML_SUFFIX') ? '/\.(' . trim(C('URL_HTML_SUFFIX'), '.') . ')$/i' : '/\.' . __EXT__ . '$/i', '', $_SERVER['PATH_INFO']);
			$depr = C('URL_PATHINFO_DEPR');
			$paths = explode($depr, trim($_SERVER['PATH_INFO'], $depr));
			if (!defined('BIND_CONTROLLER')) {
				if (C('CONTROLLER_LEVEL') > 1) {
					$_GET[$varController] = implode('/', array_slice($paths, 0, C('CONTROLLER_LEVEL')));
					$paths = array_slice($paths, C('CONTROLLER_LEVEL'));
				} else {
					$_GET[$varController] = array_shift($paths);
				}
			}
			if (!defined('BIND_ACTION')) {
				$_GET[$varAction] = array_shift($paths);
			}
			$var = array();
			if (C('URL_PARAMS_BIND') && 1 == C('URL_PARAMS_BIND_TYPE')) {
				$var = $paths;
			} else {
				preg_replace_callback('/(\w+)\/([^\/]+)/', function ($match) use (&$var) {
					$var[$match[1]] = strip_tags($match[2]);
				}, implode('/', $paths));
			}
			$_GET = array_merge($var, $_GET);
		}
		define('CONTROLLER_PATH', self::getSpace($varAddon, $urlCase));
		define('CONTROLLER_NAME', defined('BIND_CONTROLLER') ? BIND_CONTROLLER : self::getController($varController, $urlCase));
		define('ACTION_NAME', defined('BIND_ACTION') ? BIND_ACTION : self::getAction($varAction, $urlCase));
		$controllerName = defined('CONTROLLER_ALIAS') ? CONTROLLER_ALIAS : CONTROLLER_NAME;
		define('__CONTROLLER__', __MODULE__ . $depr . (defined('BIND_CONTROLLER') ? '' : ($urlCase ? parse_name($controllerName) : $controllerName)));
		define('__ACTION__', __CONTROLLER__ . $depr . (defined('ACTION_ALIAS') ? ACTION_ALIAS : ACTION_NAME));
		$_REQUEST = array_merge($_POST, $_GET, $_COOKIE);
	}

	static private function getSpace($var, $urlCase)
	{
		$space = !empty($_GET[$var]) ? strip_tags($_GET[$var]) : '';
		unset($_GET[$var]);
		return $space;
	}

	static private function getController($var, $urlCase)
	{
		$controller = (!empty($_GET[$var]) ? $_GET[$var] : C('DEFAULT_CONTROLLER'));
		unset($_GET[$var]);
		if ($maps = C('URL_CONTROLLER_MAP')) {
			if (isset($maps[strtolower($controller)])) {
				define('CONTROLLER_ALIAS', strtolower($controller));
				return ucfirst($maps[CONTROLLER_ALIAS]);
			} elseif (array_search(strtolower($controller), $maps)) {
				return '';
			}
		}
		if ($urlCase) {
			$controller = parse_name($controller, 1);
		}
		return strip_tags(ucfirst($controller));
	}

	static private function getAction($var, $urlCase)
	{
		$action = !empty($_POST[$var]) ? $_POST[$var] : (!empty($_GET[$var]) ? $_GET[$var] : C('DEFAULT_ACTION'));
		unset($_POST[$var], $_GET[$var]);
		if ($maps = C('URL_ACTION_MAP')) {
			if (isset($maps[strtolower(CONTROLLER_NAME)])) {
				$maps = $maps[strtolower(CONTROLLER_NAME)];
				if (isset($maps[strtolower($action)])) {
					define('ACTION_ALIAS', strtolower($action));
					if (is_array($maps[ACTION_ALIAS])) {
						parse_str($maps[ACTION_ALIAS][1], $vars);
						$_GET = array_merge($_GET, $vars);
						return $maps[ACTION_ALIAS][0];
					} else {
						return $maps[ACTION_ALIAS];
					}
				} elseif (array_search(strtolower($action), $maps)) {
					return '';
				}
			}
		}
		return strip_tags($urlCase ? strtolower($action) : $action);
	}

	static private function getModule($var)
	{
		$module = (!empty($_GET[$var]) ? $_GET[$var] : C('DEFAULT_MODULE'));
		unset($_GET[$var]);
		if ($maps = C('URL_MODULE_MAP')) {
			if (isset($maps[strtolower($module)])) {
				define('MODULE_ALIAS', strtolower($module));
				return ucfirst($maps[MODULE_ALIAS]);
			} elseif (array_search(strtolower($module), $maps)) {
				return '';
			}
		}
		return strip_tags(ucfirst($module));
	}
} 