<?php
namespace Think;
class Dispatcher
{
	static public function dispatch()
	{
		$varPath = C('VAR_PATHINFO');
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
				if (!empty($_SERVER[$type])) {
					$_SERVER['PATH_INFO'] = (0 === strpos($_SERVER[$type], $_SERVER['SCRIPT_NAME'])) ? substr($_SERVER[$type], strlen($_SERVER['SCRIPT_NAME'])) : $_SERVER[$type];
					break;
				}
			}
		}
		if (empty($_SERVER['PATH_INFO'])) {
			$_SERVER['PATH_INFO'] = '';
		}
		$depr = C('URL_PATHINFO_DEPR');
		define('MODULE_PATHINFO_DEPR', $depr);
		define('__INFO__', trim($_SERVER['PATH_INFO'], '/'));
		define('__EXT__', strtolower(pathinfo($_SERVER['PATH_INFO'], PATHINFO_EXTENSION)));
		$_SERVER['PATH_INFO'] = __INFO__;
		if (__INFO__ && C('MULTI_MODULE') && !defined('BIND_MODULE')) {
			$paths = explode($depr, __INFO__, 2);
			$allowList = C('MODULE_ALLOW_LIST');
			$module = preg_replace('/\.' . __EXT__ . '$/i', '', $paths[0]);
			if (empty($allowList) || (is_array($allowList) && in_array_case($module, $allowList))) {
				$_GET[$varModule] = $module;
				$_SERVER['PATH_INFO'] = isset($paths[1]) ? $paths[1] : '';
			}
		}
		define('MODULE_NAME', defined('BIND_MODULE') ? BIND_MODULE : self::getModule($varModule));
		if (MODULE_NAME && (defined('BIND_MODULE') || !in_array_case(MODULE_NAME, C('MODULE_DENY_LIST'))) && is_dir(APP_PATH . MODULE_NAME)) {
			define('MODULE_PATH', APP_PATH . MODULE_NAME . '/');
			C('CACHE_PATH', CACHE_PATH . MODULE_NAME . '/');
			if (is_file(MODULE_PATH . 'Conf/config.php')) C(include MODULE_PATH . 'Conf/config.php');
			if (is_file(MODULE_PATH . 'Conf/alias.php')) Think::addMap(include MODULE_PATH . 'Conf/alias.php');
			if (is_file(MODULE_PATH . 'Common/function.php')) include MODULE_PATH . 'Common/function.php';
		} else {
			E(L('_MODULE_NOT_EXIST_') . ':' . MODULE_NAME);
		}
		if ('' != $_SERVER['PATH_INFO'] && (!C('URL_ROUTER_ON') || !Route::check())) {
			if (C('URL_DENY_SUFFIX') && preg_match('/\.(' . trim(C('URL_DENY_SUFFIX'), '.') . ')$/i', $_SERVER['PATH_INFO'])) {
				send_http_status(404);
				exit;
			}
			$_SERVER['PATH_INFO'] = preg_replace(C('URL_HTML_SUFFIX') ? '/\.(' . trim(C('URL_HTML_SUFFIX'), '.') . ')$/i' : '/\.' . __EXT__ . '$/i', '', $_SERVER['PATH_INFO']);
			$depr = C('URL_PATHINFO_DEPR');
			$paths = explode($depr, trim($_SERVER['PATH_INFO'], $depr));
			if (!defined('BIND_CONTROLLER')) {
				$_GET[$varController] = array_shift($paths);
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
		define('CONTROLLER_NAME', defined('BIND_CONTROLLER') ? BIND_CONTROLLER : self::getController($varController, $urlCase));
		define('ACTION_NAME', defined('BIND_ACTION') ? BIND_ACTION : self::getAction($varAction, $urlCase));
		$_REQUEST = array_merge($_POST, $_GET);
	}

	static private function getController($var, $urlCase)
	{
		$controller = (!empty($_GET[$var]) ? $_GET[$var] : C('DEFAULT_CONTROLLER'));
		unset($_GET[$var]);
		if ($urlCase) {
			$controller = parse_name($controller, 1);
		}
		return strip_tags(ucfirst($controller));
	}

	static private function getAction($var, $urlCase)
	{
		$action = !empty($_POST[$var]) ? $_POST[$var] : (!empty($_GET[$var]) ? $_GET[$var] : C('DEFAULT_ACTION'));
		unset($_POST[$var], $_GET[$var]);
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
		return strip_tags(ucfirst(strtolower($module)));
	}
} 