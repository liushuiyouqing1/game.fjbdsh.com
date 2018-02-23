<?php
namespace Think\Controller;
class RpcController
{
	protected $allowMethodList = '';
	protected $debug = false;

	public function __construct()
	{
		if (method_exists($this, '_initialize')) $this->_initialize();
		Vendor('phpRPC.phprpc_server');
		$server = new \PHPRPC_Server();
		if ($this->allowMethodList) {
			$methods = $this->allowMethodList;
		} else {
			$methods = get_class_methods($this);
			$methods = array_diff($methods, array('__construct', '__call', '_initialize'));
		}
		$server->add($methods, $this);
		if (APP_DEBUG || $this->debug) {
			$server->setDebugMode(true);
		}
		$server->setEnableGZIP(true);
		$server->start();
		echo $server->comment();
	}

	public function __call($method, $args)
	{
	}
} 