<?php
namespace Think\Controller;
class YarController
{
	public function __construct()
	{
		if (method_exists($this, '_initialize')) $this->_initialize();
		if (!extension_loaded('yar')) E(L('_NOT_SUPPORT_') . ':yar');
		$server = new \Yar_Server($this);
		$server->handle();
	}

	public function __call($method, $args)
	{
	}
} 