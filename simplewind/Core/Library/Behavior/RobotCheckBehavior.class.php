<?php
namespace Behavior;
class RobotCheckBehavior
{
	public function run(&$params)
	{
		if (C('LIMIT_ROBOT_VISIT', null, true) && self::isRobot()) {
			exit('Access Denied');
		}
	}

	static private function isRobot()
	{
		static $_robot = null;
		if (is_null($_robot)) {
			$spiders = 'Bot|Crawl|Spider|slurp|sohu-search|lycos|robozilla';
			$browsers = 'MSIE|Netscape|Opera|Konqueror|Mozilla';
			if (preg_match("/($browsers)/", $_SERVER['HTTP_USER_AGENT'])) {
				$_robot = false;
			} elseif (preg_match("/($spiders)/", $_SERVER['HTTP_USER_AGENT'])) {
				$_robot = true;
			} else {
				$_robot = false;
			}
		}
		return $_robot;
	}
}