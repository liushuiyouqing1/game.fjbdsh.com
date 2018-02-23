<?php
namespace Behavior;

use Think\Log;

class ChromeShowPageTraceBehavior
{
	protected $tracePageTabs = array('BASE' => '基本', 'FILE' => '文件', 'INFO' => '流程', 'ERR|NOTIC' => '错误', 'SQL' => 'SQL', 'DEBUG' => '调试');

	public function run(&$params)
	{
		if (C('SHOW_PAGE_TRACE')) $this->showTrace();
	}

	private function showTrace()
	{
		$files = get_included_files();
		$info = array();
		foreach ($files as $key => $file) {
			$info[] = $file . ' ( ' . number_format(filesize($file) / 1024, 2) . ' KB )';
		}
		$trace = array();
		$base = array('请求信息' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']) . ' ' . $_SERVER['SERVER_PROTOCOL'] . ' ' . $_SERVER['REQUEST_METHOD'] . ' : ' . __SELF__, '运行时间' => $this->showTime(), '吞吐率' => number_format(1 / G('beginTime', 'viewEndTime'), 2) . 'req/s', '内存开销' => MEMORY_LIMIT_ON ? number_format((memory_get_usage() - $GLOBALS['_startUseMems']) / 1024, 2) . ' kb' : '不支持', '查询信息' => N('db_query') . ' queries ' . N('db_write') . ' writes ', '文件加载' => count(get_included_files()), '缓存信息' => N('cache_read') . ' gets ' . N('cache_write') . ' writes ', '配置加载' => count(c()), '会话信息' => 'SESSION_ID=' . session_id(),);
		$traceFile = COMMON_PATH . 'Conf/trace.php';
		if (is_file($traceFile)) {
			$base = array_merge($base, include $traceFile);
		}
		$debug = trace();
		$tabs = C('TRACE_PAGE_TABS', null, $this->tracePageTabs);
		foreach ($tabs as $name => $title) {
			switch (strtoupper($name)) {
				case 'BASE':
					$trace[$title] = $base;
					break;
				case 'FILE':
					$trace[$title] = $info;
					break;
				default:
					$name = strtoupper($name);
					if (strpos($name, '|')) {
						$array = explode('|', $name);
						$result = array();
						foreach ($array as $name) {
							$result += isset($debug[$name]) ? $debug[$name] : array();
						}
						$trace[$title] = $result;
					} else {
						$trace[$title] = isset($debug[$name]) ? $debug[$name] : '';
					}
			}
		}
		chrome_debug('TRACE信息:' . __SELF__, 'group');
		foreach ($trace as $title => $log) {
			'错误' == $title ? chrome_debug($title, 'group') : chrome_debug($title, 'groupCollapsed');
			foreach ($log as $i => $logstr) {
				chrome_debug($i . '.' . $logstr, 'log');
			}
			chrome_debug('', 'groupEnd');
		}
		chrome_debug('', 'groupEnd');
		if ($save = C('PAGE_TRACE_SAVE')) {
			if (is_array($save)) {
				$tabs = C('TRACE_PAGE_TABS', null, $this->tracePageTabs);
				$array = array();
				foreach ($save as $tab) {
					$array[] = $tabs[$tab];
				}
			}
			$content = date('[ c ]') . ' ' . get_client_ip() . ' ' . $_SERVER['REQUEST_URI'] . "\r\n";
			foreach ($trace as $key => $val) {
				if (!isset($array) || in_array($key, $array)) {
					$content .= '[ ' . $key . " ]\r\n";
					if (is_array($val)) {
						foreach ($val as $k => $v) {
							$content .= (!is_numeric($k) ? $k . ':' : '') . print_r($v, true) . "\r\n";
						}
					} else {
						$content .= print_r($val, true) . "\r\n";
					}
					$content .= "\r\n";
				}
			}
			error_log(str_replace('<br/>', "\r\n", $content), 3, LOG_PATH . date('y_m_d') . '_trace.log');
		}
		unset($files, $info, $base);
	}

	private function showTime()
	{
		G('beginTime', $GLOBALS['_beginTime']);
		G('viewEndTime');
		return G('beginTime', 'viewEndTime') . 's ( Load:' . G('beginTime', 'loadTime') . 's Init:' . G('loadTime', 'initTime') . 's Exec:' . G('initTime', 'viewStartTime') . 's Template:' . G('viewStartTime', 'viewEndTime') . 's )';
	}
}

if (!function_exists('chrome_debug')) {
	function chrome_debug($msg, $type = 'trace', $trace_level = 1)
	{
		if ('trace' == $type) {
			ChromePhp::groupCollapsed($msg);
			$traces = debug_backtrace(false);
			$traces = array_reverse($traces);
			$max = count($traces) - $trace_level;
			for ($i = 0; $i < $max; $i++) {
				$trace = $traces[$i];
				$fun = isset($trace['class']) ? $trace['class'] . '::' . $trace['function'] : $trace['function'];
				$file = isset($trace['file']) ? $trace['file'] : 'unknown file';
				$line = isset($trace['line']) ? $trace['line'] : 'unknown line';
				$trace_msg = '#' . $i . '  ' . $fun . ' called at [' . $file . ':' . $line . ']';
				if (!empty($trace['args'])) {
					ChromePhp::groupCollapsed($trace_msg);
					ChromePhp::log($trace['args']);
					ChromePhp::groupEnd();
				} else {
					ChromePhp::log($trace_msg);
				}
			}
			ChromePhp::groupEnd();
		} else {
			if (method_exists('Behavior\ChromePhp', $type)) {
				call_user_func(array('Behavior\ChromePhp', $type), $msg);
			} else {
				call_user_func_array(array('Behavior\ChromePhp', 'log'), func_get_args());
			}
		}
	}

	class ChromePhp
	{
		const VERSION = '4.1.0';
		const HEADER_NAME = 'X-ChromeLogger-Data';
		const BACKTRACE_LEVEL = 'backtrace_level';
		const LOG = 'log';
		const WARN = 'warn';
		const ERROR = 'error';
		const GROUP = 'group';
		const INFO = 'info';
		const GROUP_END = 'groupEnd';
		const GROUP_COLLAPSED = 'groupCollapsed';
		const TABLE = 'table';
		protected $_php_version;
		protected $_timestamp;
		protected $_json = array('version' => self::VERSION, 'columns' => array('log', 'backtrace', 'type'), 'rows' => array());
		protected $_backtraces = array();
		protected $_error_triggered = false;
		protected $_settings = array(self::BACKTRACE_LEVEL => 1);
		protected static $_instance;
		protected $_processed = array();

		private function __construct()
		{
			$this->_php_version = phpversion();
			$this->_timestamp = $this->_php_version >= 5.1 ? $_SERVER['REQUEST_TIME'] : time();
			$this->_json['request_uri'] = $_SERVER['REQUEST_URI'];
		}

		public static function getInstance()
		{
			if (self::$_instance === null) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		public static function log()
		{
			$args = func_get_args();
			return self::_log('', $args);
		}

		public static function warn()
		{
			$args = func_get_args();
			return self::_log(self::WARN, $args);
		}

		public static function error()
		{
			$args = func_get_args();
			return self::_log(self::ERROR, $args);
		}

		public static function group()
		{
			$args = func_get_args();
			return self::_log(self::GROUP, $args);
		}

		public static function info()
		{
			$args = func_get_args();
			return self::_log(self::INFO, $args);
		}

		public static function groupCollapsed()
		{
			$args = func_get_args();
			return self::_log(self::GROUP_COLLAPSED, $args);
		}

		public static function groupEnd()
		{
			$args = func_get_args();
			return self::_log(self::GROUP_END, $args);
		}

		public static function table()
		{
			$args = func_get_args();
			return self::_log(self::TABLE, $args);
		}

		protected static function _log($type, array $args)
		{
			if (count($args) == 0 && $type != self::GROUP_END) {
				return;
			}
			$logger = self::getInstance();
			$logger->_processed = array();
			$logs = array();
			foreach ($args as $arg) {
				$logs[] = $logger->_convert($arg);
			}
			$backtrace = debug_backtrace(false);
			$level = $logger->getSetting(self::BACKTRACE_LEVEL);
			$backtrace_message = 'unknown';
			if (isset($backtrace[$level]['file']) && isset($backtrace[$level]['line'])) {
				$backtrace_message = $backtrace[$level]['file'] . ' : ' . $backtrace[$level]['line'];
			}
			$logger->_addRow($logs, $backtrace_message, $type);
		}

		protected function _convert($object)
		{
			if (!is_object($object)) {
				return $object;
			}
			$this->_processed[] = $object;
			$object_as_array = array();
			$object_as_array['___class_name'] = get_class($object);
			$object_vars = get_object_vars($object);
			foreach ($object_vars as $key => $value) {
				if ($value === $object || in_array($value, $this->_processed, true)) {
					$value = 'recursion - parent object [' . get_class($value) . ']';
				}
				$object_as_array[$key] = $this->_convert($value);
			}
			$reflection = new ReflectionClass($object);
			foreach ($reflection->getProperties() as $property) {
				if (array_key_exists($property->getName(), $object_vars)) {
					continue;
				}
				$type = $this->_getPropertyKey($property);
				if ($this->_php_version >= 5.3) {
					$property->setAccessible(true);
				}
				try {
					$value = $property->getValue($object);
				} catch (ReflectionException $e) {
					$value = 'only PHP 5.3 can access private/protected properties';
				}
				if ($value === $object || in_array($value, $this->_processed, true)) {
					$value = 'recursion - parent object [' . get_class($value) . ']';
				}
				$object_as_array[$type] = $this->_convert($value);
			}
			return $object_as_array;
		}

		protected function _getPropertyKey(ReflectionProperty $property)
		{
			$static = $property->isStatic() ? ' static' : '';
			if ($property->isPublic()) {
				return 'public' . $static . ' ' . $property->getName();
			}
			if ($property->isProtected()) {
				return 'protected' . $static . ' ' . $property->getName();
			}
			if ($property->isPrivate()) {
				return 'private' . $static . ' ' . $property->getName();
			}
		}

		protected function _addRow(array $logs, $backtrace, $type)
		{
			if (in_array($backtrace, $this->_backtraces)) {
				$backtrace = null;
			}
			if ($type == self::GROUP || $type == self::GROUP_END || $type == self::GROUP_COLLAPSED) {
				$backtrace = null;
			}
			if ($backtrace !== null) {
				$this->_backtraces[] = $backtrace;
			}
			$row = array($logs, $backtrace, $type);
			$this->_json['rows'][] = $row;
			$this->_writeHeader($this->_json);
		}

		protected function _writeHeader($data)
		{
			header(self::HEADER_NAME . ': ' . $this->_encode($data));
		}

		protected function _encode($data)
		{
			return base64_encode(utf8_encode(json_encode($data)));
		}

		public function addSetting($key, $value)
		{
			$this->_settings[$key] = $value;
		}

		public function addSettings(array $settings)
		{
			foreach ($settings as $key => $value) {
				$this->addSetting($key, $value);
			}
		}

		public function getSetting($key)
		{
			if (!isset($this->_settings[$key])) {
				return null;
			}
			return $this->_settings[$key];
		}
	}
} 