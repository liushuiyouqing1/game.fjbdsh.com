<?php
namespace Behavior;
class BrowserCheckBehavior
{
	public function run(&$params)
	{
		if ($_SERVER['REQUEST_METHOD'] == 'GET') {
			$guid = md5($_SERVER['PHP_SELF']);
			$refleshTime = C('LIMIT_REFLESH_TIMES', null, 10);
			if (cookie('_last_visit_time_' . $guid) && cookie('_last_visit_time_' . $guid) > time() - $refleshTime) {
				header('HTTP/1.1 304 Not Modified');
				exit;
			} else {
				cookie('_last_visit_time_' . $guid, $_SERVER['REQUEST_TIME']);
			}
		}
	}
}