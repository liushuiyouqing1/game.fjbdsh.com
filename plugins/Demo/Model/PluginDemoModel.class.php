<?php
namespace plugins\Demo\Model;

use Common\Model\CommonModel;

class PluginDemoModel extends CommonModel
{
	protected $_validate = array();

	protected function _before_write(&$data)
	{
		parent::_before_write($data);
	}

	function test()
	{
		echo "hello";
	}
}