<?php
namespace Org\Util;

use Think\Db;

class Rbac
{
	static public function authenticate($map, $model = '')
	{
		if (empty($model)) $model = C('USER_AUTH_MODEL');
		return M($model)->where($map)->find();
	}

	static function saveAccessList($authId = null)
	{
		if (null === $authId) $authId = $_SESSION[C('USER_AUTH_KEY')];
		if (C('USER_AUTH_TYPE') != 2 && !$_SESSION[C('ADMIN_AUTH_KEY')]) $_SESSION['_ACCESS_LIST'] = self::getAccessList($authId);
		return;
	}

	static function getRecordAccessList($authId = null, $module = '')
	{
		if (null === $authId) $authId = $_SESSION[C('USER_AUTH_KEY')];
		if (empty($module)) $module = CONTROLLER_NAME;
		$accessList = self::getModuleAccessList($authId, $module);
		return $accessList;
	}

	static function checkAccess()
	{
		if (C('USER_AUTH_ON')) {
			$_module = array();
			$_action = array();
			if ("" != C('REQUIRE_AUTH_MODULE')) {
				$_module['yes'] = explode(',', strtoupper(C('REQUIRE_AUTH_MODULE')));
			} else {
				$_module['no'] = explode(',', strtoupper(C('NOT_AUTH_MODULE')));
			}
			if ((!empty($_module['no']) && !in_array(strtoupper(CONTROLLER_NAME), $_module['no'])) || (!empty($_module['yes']) && in_array(strtoupper(CONTROLLER_NAME), $_module['yes']))) {
				if ("" != C('REQUIRE_AUTH_ACTION')) {
					$_action['yes'] = explode(',', strtoupper(C('REQUIRE_AUTH_ACTION')));
				} else {
					$_action['no'] = explode(',', strtoupper(C('NOT_AUTH_ACTION')));
				}
				if ((!empty($_action['no']) && !in_array(strtoupper(ACTION_NAME), $_action['no'])) || (!empty($_action['yes']) && in_array(strtoupper(ACTION_NAME), $_action['yes']))) {
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
		return false;
	}

	static public function checkLogin()
	{
		if (self::checkAccess()) {
			if (!$_SESSION[C('USER_AUTH_KEY')]) {
				if (C('GUEST_AUTH_ON')) {
					if (!isset($_SESSION['_ACCESS_LIST'])) self::saveAccessList(C('GUEST_AUTH_ID'));
				} else {
					redirect(PHP_FILE . C('USER_AUTH_GATEWAY'));
				}
			}
		}
		return true;
	}

	static public function AccessDecision($appName = MODULE_NAME)
	{
		if (self::checkAccess()) {
			$accessGuid = md5($appName . CONTROLLER_NAME . ACTION_NAME);
			if (empty($_SESSION[C('ADMIN_AUTH_KEY')])) {
				if (C('USER_AUTH_TYPE') == 2) {
					$accessList = self::getAccessList($_SESSION[C('USER_AUTH_KEY')]);
				} else {
					if ($_SESSION[$accessGuid]) {
						return true;
					}
					$accessList = $_SESSION['_ACCESS_LIST'];
				}
				if (!isset($accessList[strtoupper($appName)][strtoupper(CONTROLLER_NAME)][strtoupper(ACTION_NAME)])) {
					$_SESSION[$accessGuid] = false;
					return false;
				} else {
					$_SESSION[$accessGuid] = true;
				}
			} else {
				return true;
			}
		}
		return true;
	}

	static public function getAccessList($authId)
	{
		$db = Db::getInstance(C('RBAC_DB_DSN'));
		$table = array('role' => C('RBAC_ROLE_TABLE'), 'user' => C('RBAC_USER_TABLE'), 'access' => C('RBAC_ACCESS_TABLE'), 'node' => C('RBAC_NODE_TABLE'));
		$sql = "select node.id,node.name from " . $table['role'] . " as role," . $table['user'] . " as user," . $table['access'] . " as access ," . $table['node'] . " as node " . "where user.user_id='{$authId}' and user.role_id=role.id and ( access.role_id=role.id  or (access.role_id=role.pid and role.pid!=0 ) ) and role.status=1 and access.node_id=node.id and node.level=1 and node.status=1";
		$apps = $db->query($sql);
		$access = array();
		foreach ($apps as $key => $app) {
			$appId = $app['id'];
			$appName = $app['name'];
			$access[strtoupper($appName)] = array();
			$sql = "select node.id,node.name from " . $table['role'] . " as role," . $table['user'] . " as user," . $table['access'] . " as access ," . $table['node'] . " as node " . "where user.user_id='{$authId}' and user.role_id=role.id and ( access.role_id=role.id  or (access.role_id=role.pid and role.pid!=0 ) ) and role.status=1 and access.node_id=node.id and node.level=2 and node.pid={$appId} and node.status=1";
			$modules = $db->query($sql);
			$publicAction = array();
			foreach ($modules as $key => $module) {
				$moduleId = $module['id'];
				$moduleName = $module['name'];
				if ('PUBLIC' == strtoupper($moduleName)) {
					$sql = "select node.id,node.name from " . $table['role'] . " as role," . $table['user'] . " as user," . $table['access'] . " as access ," . $table['node'] . " as node " . "where user.user_id='{$authId}' and user.role_id=role.id and ( access.role_id=role.id  or (access.role_id=role.pid and role.pid!=0 ) ) and role.status=1 and access.node_id=node.id and node.level=3 and node.pid={$moduleId} and node.status=1";
					$rs = $db->query($sql);
					foreach ($rs as $a) {
						$publicAction[$a['name']] = $a['id'];
					}
					unset($modules[$key]);
					break;
				}
			}
			foreach ($modules as $key => $module) {
				$moduleId = $module['id'];
				$moduleName = $module['name'];
				$sql = "select node.id,node.name from " . $table['role'] . " as role," . $table['user'] . " as user," . $table['access'] . " as access ," . $table['node'] . " as node " . "where user.user_id='{$authId}' and user.role_id=role.id and ( access.role_id=role.id  or (access.role_id=role.pid and role.pid!=0 ) ) and role.status=1 and access.node_id=node.id and node.level=3 and node.pid={$moduleId} and node.status=1";
				$rs = $db->query($sql);
				$action = array();
				foreach ($rs as $a) {
					$action[$a['name']] = $a['id'];
				}
				$action += $publicAction;
				$access[strtoupper($appName)][strtoupper($moduleName)] = array_change_key_case($action, CASE_UPPER);
			}
		}
		return $access;
	}

	static public function getModuleAccessList($authId, $module)
	{
		$db = Db::getInstance(C('RBAC_DB_DSN'));
		$table = array('role' => C('RBAC_ROLE_TABLE'), 'user' => C('RBAC_USER_TABLE'), 'access' => C('RBAC_ACCESS_TABLE'));
		$sql = "select access.node_id from " . $table['role'] . " as role," . $table['user'] . " as user," . $table['access'] . " as access " . "where user.user_id='{$authId}' and user.role_id=role.id and ( access.role_id=role.id  or (access.role_id=role.pid and role.pid!=0 ) ) and role.status=1 and  access.module='{$module}' and access.status=1";
		$rs = $db->query($sql);
		$access = array();
		foreach ($rs as $node) {
			$access[] = $node['node_id'];
		}
		return $access;
	}
}