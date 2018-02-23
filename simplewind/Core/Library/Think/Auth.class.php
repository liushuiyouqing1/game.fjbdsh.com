<?php
namespace Think;
class Auth
{
	protected $_config = array('AUTH_ON' => true, 'AUTH_TYPE' => 1, 'AUTH_GROUP' => 'auth_group', 'AUTH_GROUP_ACCESS' => 'auth_group_access', 'AUTH_RULE' => 'auth_rule', 'AUTH_USER' => 'member');

	public function __construct()
	{
		$prefix = C('DB_PREFIX');
		$this->_config['AUTH_GROUP'] = $prefix . $this->_config['AUTH_GROUP'];
		$this->_config['AUTH_RULE'] = $prefix . $this->_config['AUTH_RULE'];
		$this->_config['AUTH_USER'] = $prefix . $this->_config['AUTH_USER'];
		$this->_config['AUTH_GROUP_ACCESS'] = $prefix . $this->_config['AUTH_GROUP_ACCESS'];
		if (C('AUTH_CONFIG')) {
			$this->_config = array_merge($this->_config, C('AUTH_CONFIG'));
		}
	}

	public function check($name, $uid, $type = 1, $mode = 'url', $relation = 'or')
	{
		if (!$this->_config['AUTH_ON']) return true;
		$authList = $this->getAuthList($uid, $type);
		if (is_string($name)) {
			$name = strtolower($name);
			if (strpos($name, ',') !== false) {
				$name = explode(',', $name);
			} else {
				$name = array($name);
			}
		}
		$list = array();
		if ($mode == 'url') {
			$REQUEST = unserialize(strtolower(serialize($_REQUEST)));
		}
		foreach ($authList as $auth) {
			$query = preg_replace('/^.+\?/U', '', $auth);
			if ($mode == 'url' && $query != $auth) {
				parse_str($query, $param);
				$intersect = array_intersect_assoc($REQUEST, $param);
				$auth = preg_replace('/\?.*$/U', '', $auth);
				if (in_array($auth, $name) && $intersect == $param) {
					$list[] = $auth;
				}
			} else if (in_array($auth, $name)) {
				$list[] = $auth;
			}
		}
		if ($relation == 'or' and !empty($list)) {
			return true;
		}
		$diff = array_diff($name, $list);
		if ($relation == 'and' and empty($diff)) {
			return true;
		}
		return false;
	}

	public function getGroups($uid)
	{
		static $groups = array();
		if (isset($groups[$uid])) return $groups[$uid];
		$user_groups = M()->table($this->_config['AUTH_GROUP_ACCESS'] . ' a')->where("a.uid='$uid' and g.status='1'")->join($this->_config['AUTH_GROUP'] . " g on a.group_id=g.id")->field('uid,group_id,title,rules')->select();
		$groups[$uid] = $user_groups ?: array();
		return $groups[$uid];
	}

	protected function getAuthList($uid, $type)
	{
		static $_authList = array();
		$t = implode(',', (array)$type);
		if (isset($_authList[$uid . $t])) {
			return $_authList[$uid . $t];
		}
		if ($this->_config['AUTH_TYPE'] == 2 && isset($_SESSION['_AUTH_LIST_' . $uid . $t])) {
			return $_SESSION['_AUTH_LIST_' . $uid . $t];
		}
		$groups = $this->getGroups($uid);
		$ids = array();
		foreach ($groups as $g) {
			$ids = array_merge($ids, explode(',', trim($g['rules'], ',')));
		}
		$ids = array_unique($ids);
		if (empty($ids)) {
			$_authList[$uid . $t] = array();
			return array();
		}
		$map = array('id' => array('in', $ids), 'type' => $type, 'status' => 1,);
		$rules = M()->table($this->_config['AUTH_RULE'])->where($map)->field('condition,name')->select();
		$authList = array();
		foreach ($rules as $rule) {
			if (!empty($rule['condition'])) {
				$user = $this->getUserInfo($uid);
				$command = preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', $rule['condition']);
				@(eval('$condition=(' . $command . ');'));
				if ($condition) {
					$authList[] = strtolower($rule['name']);
				}
			} else {
				$authList[] = strtolower($rule['name']);
			}
		}
		$_authList[$uid . $t] = $authList;
		if ($this->_config['AUTH_TYPE'] == 2) {
			$_SESSION['_AUTH_LIST_' . $uid . $t] = $authList;
		}
		return array_unique($authList);
	}

	protected function getUserInfo($uid)
	{
		static $userinfo = array();
		if (!isset($userinfo[$uid])) {
			$userinfo[$uid] = M()->where(array('uid' => $uid))->table($this->_config['AUTH_USER'])->find();
		}
		return $userinfo[$uid];
	}
} 