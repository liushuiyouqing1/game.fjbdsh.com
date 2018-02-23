<?php
namespace Behavior;
class AgentCheckBehavior
{
	public function run(&$params)
	{
		$limitProxyVisit = C('LIMIT_PROXY_VISIT', null, true);
		if ($limitProxyVisit && ($_SERVER['HTTP_X_FORWARDED_FOR'] || $_SERVER['HTTP_VIA'] || $_SERVER['HTTP_PROXY_CONNECTION'] || $_SERVER['HTTP_USER_AGENT_VIA'])) {
			exit('Access Denied');
		}
	}
} 