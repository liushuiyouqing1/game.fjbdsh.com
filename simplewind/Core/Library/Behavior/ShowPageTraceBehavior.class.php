<?php
namespace Behavior;

use Think\Log;

class ShowPageTraceBehavior
{
	protected $tracePageTabs = array('BASE' => '基本', 'FILE' => '文件', 'INFO' => '流程', 'ERR|NOTIC' => '错误', 'SQL' => 'SQL', 'DEBUG' => '调试');

	public function run(&$params)
	{
		if (!IS_AJAX && !IS_CLI && C('SHOW_PAGE_TRACE')) {
			echo $this->showTrace();
		}
	}

	private function showTrace()
	{
		$files = get_included_files();
		$info = array();
		foreach ($files as $key => $file) {
			$info[] = $file . ' ( ' . number_format(filesize($file) / 1024, 2) . ' KB )';
		}
		$trace = array();
		$base = array('请求信息' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']) . ' ' . $_SERVER['SERVER_PROTOCOL'] . ' ' . $_SERVER['REQUEST_METHOD'] . ' : ' . __SELF__, '运行时间' => $this->showTime(), '吞吐率' => number_format(1 / G('beginTime', 'viewEndTime'), 2) . 'req/s', '内存开销' => MEMORY_LIMIT_ON ? number_format((memory_get_usage() - $GLOBALS['_startUseMems']) / 1024, 2) . ' kb' : '不支持', '查询信息' => N('db_query') . ' queries ' . N('db_write') . ' writes ', '文件加载' => count(get_included_files()), '缓存信息' => N('cache_read') . ' gets ' . N('cache_write') . ' writes ', '配置加载' => count(C()), '会话信息' => 'SESSION_ID=' . session_id(),);
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
						$names = explode('|', $name);
						$result = array();
						foreach ($names as $name) {
							$result += isset($debug[$name]) ? $debug[$name] : array();
						}
						$trace[$title] = $result;
					} else {
						$trace[$title] = isset($debug[$name]) ? $debug[$name] : '';
					}
			}
		}
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
				if (!isset($array) || in_array_case($key, $array)) {
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
			error_log(str_replace('<br/>', "\r\n", $content), 3, C('LOG_PATH') . date('y_m_d') . '_trace.log');
		}
		unset($files, $info, $base);
		ob_start();
		include C('TMPL_TRACE_FILE') ? C('TMPL_TRACE_FILE') : THINK_PATH . 'Tpl/page_trace.tpl';
		return ob_get_clean();
	}

	private function showTime()
	{
		G('beginTime', $GLOBALS['_beginTime']);
		G('viewEndTime');
		return G('beginTime', 'viewEndTime') . 's ( Load:' . G('beginTime', 'loadTime') . 's Init:' . G('loadTime', 'initTime') . 's Exec:' . G('initTime', 'viewStartTime') . 's Template:' . G('viewStartTime', 'viewEndTime') . 's )';
	}
} 