<?php
$GLOBALS['_beginTime'] = microtime(TRUE);
define('MEMORY_LIMIT_ON', function_exists('memory_get_usage'));
if (MEMORY_LIMIT_ON) $GLOBALS['_startUseMems'] = memory_get_usage();
const THINK_VERSION = '3.2.3';
const URL_COMMON = 0;
const URL_PATHINFO = 1;
const URL_REWRITE = 2;
const URL_COMPAT = 3;
const EXT = '.class.php';
defined('THINK_PATH') or define('THINK_PATH', __DIR__ . '/');
defined('APP_PATH') or define('APP_PATH', dirname($_SERVER['SCRIPT_FILENAME']) . '/');
defined('APP_STATUS') or define('APP_STATUS', '');
defined('APP_DEBUG') or define('APP_DEBUG', false);
if (function_exists('saeAutoLoader')) {
	defined('APP_MODE') or define('APP_MODE', 'sae');
	defined('STORAGE_TYPE') or define('STORAGE_TYPE', 'Sae');
} else {
	defined('APP_MODE') or define('APP_MODE', 'common');
	defined('STORAGE_TYPE') or define('STORAGE_TYPE', 'File');
}
defined('RUNTIME_PATH') or define('RUNTIME_PATH', APP_PATH . 'Runtime/');
defined('LIB_PATH') or define('LIB_PATH', realpath(THINK_PATH . 'Library') . '/');
defined('CORE_PATH') or define('CORE_PATH', LIB_PATH . 'Think/');
defined('BEHAVIOR_PATH') or define('BEHAVIOR_PATH', LIB_PATH . 'Behavior/');
defined('MODE_PATH') or define('MODE_PATH', THINK_PATH . 'Mode/');
defined('VENDOR_PATH') or define('VENDOR_PATH', LIB_PATH . 'Vendor/');
defined('COMMON_PATH') or define('COMMON_PATH', APP_PATH . 'Common/');
defined('CONF_PATH') or define('CONF_PATH', COMMON_PATH . 'Conf/');
defined('LANG_PATH') or define('LANG_PATH', COMMON_PATH . 'Lang/');
defined('HTML_PATH') or define('HTML_PATH', APP_PATH . 'Html/');
defined('LOG_PATH') or define('LOG_PATH', RUNTIME_PATH . 'Logs/');
defined('TEMP_PATH') or define('TEMP_PATH', RUNTIME_PATH . 'Temp/');
defined('DATA_PATH') or define('DATA_PATH', RUNTIME_PATH . 'Data/');
defined('CACHE_PATH') or define('CACHE_PATH', RUNTIME_PATH . 'Cache/');
defined('CONF_EXT') or define('CONF_EXT', '.php');
defined('CONF_PARSE') or define('CONF_PARSE', '');
defined('ADDON_PATH') or define('ADDON_PATH', APP_PATH . 'Addon');
if (version_compare(PHP_VERSION, '5.4.0', '<')) {
	ini_set('magic_quotes_runtime', 0);
	define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc() ? true : false);
} else {
	define('MAGIC_QUOTES_GPC', false);
}
define('IS_CGI', (0 === strpos(PHP_SAPI, 'cgi') || false !== strpos(PHP_SAPI, 'fcgi')) ? 1 : 0);
define('IS_WIN', strstr(PHP_OS, 'WIN') ? 1 : 0);
define('IS_CLI', PHP_SAPI == 'cli' ? 1 : 0);
if (!IS_CLI) {
	if (!defined('_PHP_FILE_')) {
		if (IS_CGI) {
			$_temp = explode('.php', $_SERVER['PHP_SELF']);
			define('_PHP_FILE_', rtrim(str_replace($_SERVER['HTTP_HOST'], '', $_temp[0] . '.php'), '/'));
		} else {
			define('_PHP_FILE_', rtrim($_SERVER['SCRIPT_NAME'], '/'));
		}
	}
	if (!defined('__ROOT__')) {
		$_root = rtrim(dirname(_PHP_FILE_), '/');
		define('__ROOT__', (($_root == '/' || $_root == '\\') ? '' : $_root));
	}
}
require CORE_PATH . 'Think' . EXT;
Think\Think::start();