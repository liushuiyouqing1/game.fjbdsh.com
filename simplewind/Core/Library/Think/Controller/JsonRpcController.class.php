<?php
namespace Think\Controller;
class JsonRpcController
{
	public function __construct()
	{
		if (method_exists($this, '_initialize')) $this->_initialize();
		Vendor('jsonRPC.jsonRPCServer');
		\jsonRPCServer::handle($this);
	}

	public function __call($method, $args)
	{
	}
} 