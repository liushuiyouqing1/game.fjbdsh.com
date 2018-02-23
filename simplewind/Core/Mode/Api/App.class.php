<?php
namespace Think;
class App
{
	static public function init()
	{
		define('NOW_TIME', $_SERVER['REQUEST_TIME']);
		define('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);
		define('IS_GET', REQUEST_METHOD == 'GET' ? true : false);
		define('IS_POST', REQUEST_METHOD == 'POST' ? true : false);
		define('IS_PUT', REQUEST_METHOD == 'PUT' ? true : false);
		define('IS_DELETE', REQUEST_METHOD == 'DELETE' ? true : false);
		define('IS_AJAX', ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || !empty($_POST[C('VAR_AJAX_SUBMIT')]) || !empty($_GET[C('VAR_AJAX_SUBMIT')])) ? true : false);
		Dispatcher::dispatch();
		if (C('REQUEST_VARS_FILTER')) {
			array_walk_recursive($_GET, 'think_filter');
			array_walk_recursive($_POST, 'think_filter');
			array_walk_recursive($_REQUEST, 'think_filter');
		}
		C('LOG_PATH', realpath(LOG_PATH) . '/');
		C('TMPL_EXCEPTION_FILE', realpath(C('TMPL_EXCEPTION_FILE')));
		return;
	}

	static public function exec()
	{
		if (!preg_match('/^[A-Za-z](\/|\w)*$/', CONTROLLER_NAME)) {
			$module = false;
		} else {
			$module = A(CONTROLLER_NAME);
		}
		if (!$module) {
			$module = A('Empty');
			if (!$module) {
				E(L('_CONTROLLER_NOT_EXIST_') . ':' . CONTROLLER_NAME);
			}
		}
		$action = ACTION_NAME;
		try {
			if (!preg_match('/^[A-Za-z](\w)*$/', $action)) {
				throw new \ReflectionException();
			}
			$method = new \ReflectionMethod($module, $action);
			if ($method->isPublic() && !$method->isStatic()) {
				$class = new \ReflectionClass($module);
				if (C('URL_PARAMS_BIND') && $method->getNumberOfParameters() > 0) {
					switch ($_SERVER['REQUEST_METHOD']) {
						case 'POST':
							$vars = array_merge($_GET, $_POST);
							break;
						case 'PUT':
							parse_str(file_get_contents('php://input'), $vars);
							break;
						default:
							$vars = $_GET;
					}
					$params = $method->getParameters();
					$paramsBindType = C('URL_PARAMS_BIND_TYPE');
					foreach ($params as $param) {
						$name = $param->getName();
						if (1 == $paramsBindType && !empty($vars)) {
							$args[] = array_shift($vars);
						} elseif (0 == $paramsBindType && isset($vars[$name])) {
							$args[] = $vars[$name];
						} elseif ($param->isDefaultValueAvailable()) {
							$args[] = $param->getDefaultValue();
						} else {
							E(L('_PARAM_ERROR_') . ':' . $name);
						}
					}
					array_walk_recursive($args, 'think_filter');
					$method->invokeArgs($module, $args);
				} else {
					$method->invoke($module);
				}
			} else {
				throw new \ReflectionException();
			}
		} catch (\ReflectionException $e) {
			$method = new \ReflectionMethod($module, '__call');
			$method->invokeArgs($module, array($action, ''));
		}
		return;
	}

	static public function run()
	{
		App::init();
		if (!IS_CLI) {
			session(C('SESSION_OPTIONS'));
		}
		G('initTime');
		App::exec();
		return;
	}
}