<?php
namespace plugins\Demo;

use Common\Lib\Plugin;

class DemoPlugin extends Plugin
{
	public $info = array('name' => 'Demo', 'title' => '插件演示', 'description' => '插件演示', 'status' => 1, 'author' => 'ThinkCMF', 'version' => '1.0');
	public $has_admin = 1;

	public function install()
	{
		return true;
	}

	public function uninstall()
	{
		return true;
	}

	public function footer($param)
	{
		$config = $this->getConfig();
		$this->assign($config);
		$this->display('widget');
	}
}