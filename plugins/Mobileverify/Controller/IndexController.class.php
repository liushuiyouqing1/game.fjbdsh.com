<?php
namespace plugins\Mobileverify\Controller;

use Api\Controller\PluginController;

class IndexController extends PluginController
{
	function index()
	{
		$users_model = D("Users");
		$users = $users_model->limit(0, 5)->select();
		$this->assign("users", $users);
		$this->display(":index");
	}
} 