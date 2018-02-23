<?php
namespace Think\Controller;
class HproseController
{
	protected $allowMethodList = '';
	protected $crossDomain = false;
	protected $P3P = false;
	protected $get = true;
	protected $debug = false;

	public function __construct()
	{
		if (method_exists($this, '_initialize')) $this->_initialize();
		Vendor('Hprose.HproseHttpServer');
		$server = new \HproseHttpServer();
		if ($this->allowMethodList) {
			$methods = $this->allowMethodList;
		} else {
			$methods = get_class_methods($this);
			$methods = array_diff($methods, array('__construct', '__call', '_initialize'));
		}
		$server->addMethods($methods, $this);
		if (APP_DEBUG || $this->debug) {
			$server->setDebugEnabled(true);
		}
		$server->setCrossDomainEnabled($this->crossDomain);
		$server->setP3PEnabled($this->P3P);
		$server->setGetEnabled($this->get);
		$server->start();
	}

	public function __call($method, $args)
	{
	}
} 