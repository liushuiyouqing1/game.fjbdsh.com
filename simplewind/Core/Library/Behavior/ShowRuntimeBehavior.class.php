<?php
namespace Behavior;
class ShowRuntimeBehavior
{
	public function run(&$content)
	{
		if (C('SHOW_RUN_TIME')) {
			if (false !== strpos($content, '{__NORUNTIME__}')) {
				$content = str_replace('{__NORUNTIME__}', '', $content);
			} else {
				$runtime = $this->showTime();
				if (strpos($content, '{__RUNTIME__}')) $content = str_replace('{__RUNTIME__}', $runtime, $content); else $content .= $runtime;
			}
		} else {
			$content = str_replace(array('{__NORUNTIME__}', '{__RUNTIME__}'), '', $content);
		}
	}

	private function showTime()
	{
		G('beginTime', $GLOBALS['_beginTime']);
		G('viewEndTime');
		$showTime = 'Process: ' . G('beginTime', 'viewEndTime') . 's ';
		if (C('SHOW_ADV_TIME')) {
			$showTime .= '( Load:' . G('beginTime', 'loadTime') . 's Init:' . G('loadTime', 'initTime') . 's Exec:' . G('initTime', 'viewStartTime') . 's Template:' . G('viewStartTime', 'viewEndTime') . 's )';
		}
		if (C('SHOW_DB_TIMES')) {
			$showTime .= ' | DB :' . N('db_query') . ' queries ' . N('db_write') . ' writes ';
		}
		if (C('SHOW_CACHE_TIMES')) {
			$showTime .= ' | Cache :' . N('cache_read') . ' gets ' . N('cache_write') . ' writes ';
		}
		if (MEMORY_LIMIT_ON && C('SHOW_USE_MEM')) {
			$showTime .= ' | UseMem:' . number_format((memory_get_usage() - $GLOBALS['_startUseMems']) / 1024) . ' kb';
		}
		if (C('SHOW_LOAD_FILE')) {
			$showTime .= ' | LoadFile:' . count(get_included_files());
		}
		if (C('SHOW_FUN_TIMES')) {
			$fun = get_defined_functions();
			$showTime .= ' | CallFun:' . count($fun['user']) . ',' . count($fun['internal']);
		}
		return $showTime;
	}
}