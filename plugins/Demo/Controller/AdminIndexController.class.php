<?php
namespace plugins\Demo\Controller;

use Api\Controller\PluginController;

class AdminIndexController extends PluginController
{
	function _initialize()
	{
		$adminid = sp_get_current_admin_id();
		if (!empty($adminid)) {
			$this->assign("adminid", $adminid);
		} else {
		}
	}

	function index()
	{
		$users_model = D("Users");
		$users = $users_model->limit(0, 5)->select();
		$this->assign("users", $users);
		$this->display(":admin_index");
	}
} 