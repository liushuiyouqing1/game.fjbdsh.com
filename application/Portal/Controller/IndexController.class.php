<?php
namespace Portal\Controller;

use Common\Controller\HomebaseController;

class IndexController extends HomebaseController
{
	public function getconfig()
	{
		import('Common.Lib.weixin');
		$this->weixin = new \weixin($this->extract[weixin_appid], $this->extract[weixin_key], $this->extract[access_token]);
		$data = array();
		$data['appId'] = $this->extract[weixin_appid];
		$data['jsapi_ticket'] = $this->weixin->get_jsapi_ticket();
		$data['nonceStr'] = 'asd45631';
		$data['timestamp'] = (string)time();
		$data['signature'] = sha1('jsapi_ticket=' . $data['jsapi_ticket'] . '&noncestr=' . $data['nonceStr'] . '&timestamp=' . $data['timestamp'] . '&url=' . urldecode($_GET['url']));
		$data['debug'] = false;
		$data['jsApiList'][] = 'onMenuShareTimeline';
		$data['jsApiList'][] = 'onMenuShareAppMessage';
		echo json_encode($data);
	}

	public function index()
	{
		if (!$_SESSION['istongyi']) {
			$this->display(":fangjian_tishi");
			exit();
		}
		$token = md5($this->user['id'] . time());
		$save['token'] = $token;
		M('user')->where(array('id' => $this->user['id']))->save($save);
		$this->assign('token', $token);
		$this->assign('user', $user);
		$this->display('Index:' . $this->user['password']);
	}

	public function dasheng()
	{
		if (!$_SESSION['istongyi']) {
			$this->display(":dashengfangjian_tishi");
			exit();
		}
		$token = md5($this->user['id'] . time());
		$save['token'] = $token;
		M('user')->where(array('id' => $this->user['id']))->save($save);
		$this->assign('token', $token);
		$this->assign('user', $user);
		$this->display('Index:' . $this->user['password']);
	}

	public function tongyi()
	{
		$_SESSION['istongyi'] = 1;
		echo '1';
	}

	public function room()
	{
		if (!$_SESSION['istongyi']) {
			$this->display(":fangjian_tishi");
			exit();
		}
		$user = $this->user;
		$token = md5($this->user['id'] . time());
		$save['token'] = $token;
		M('user')->where(array('id' => $this->user['id']))->save($save);
		$room = I('room');
		$mapxx['id'] = $room;
		$dkxx = M('room')->where($mapxx)->find();
		$fzuser = M('user')->where(array('id' => $dkxx['uid']))->find();
		$qun = M('qun')->where(array('open' => $dkxx['uid']))->select();
		$mayuser = array();
		$mayuser[$fzuser['id']] = 1;
		foreach ($qun as $key => $value) {
			if ($value['zt'] == 1) {
				$mayuser[$value['uid']] = '1';
			}
		}
		$rule = json_decode($dkxx['rule'], true);
		$dfxx = explode(',', $rule['play']['df']);
		$gzxx = explode(',', $rule['play']['gz']);
		$pxxx = explode(',', $rule['play']['px']);
		$gz2xx = explode(',', $rule['play']['gz2']);
		$szxx = explode(',', $rule['play']['sz']);
		$sxxx = explode(',', $rule['play']['sx']);
		$cmxx = explode(',', $rule['play']['cm']);
		$dkxx['df'] = $dfxx[$rule['df']];
		$dkxx['gz'] = $gzxx[$rule['gz']];
		$dkxx['sz'] = $szxx[$rule['sz']];
		$dkxx['sx'] = $sxxx[$rule['sx']];
		$dkxx['cm'] = $cmxx[$rule['cm']];
		$dkxx['wfname'] = $rule['play']['name'];
		$dkxx['userlist'] = json_decode($dkxx['user'], true);
		foreach ($pxxx as $key => $value) {
			if ($rule['px'][$key] == 1) {
				$dkxx['px'][] = $value;
			}
		}
		foreach ($gz2xx as $key => $value) {
			if ($rule['gz2'][$key] == 1) {
				$dkxx['gz2'][] = $value;
			}
		}
		$this->assign('fzuser', $fzuser);
		$this->assign('mayuser', $mayuser);
		$this->assign('room', $dkxx);
		$this->assign('token', $token);
		$this->assign('user', $user);
		$this->display('game' . $dkxx['type']);
	}

	public function dashengroom()
	{
		if (!$_SESSION['istongyi']) {
			$this->display(":dashengfangjian_tishi");
			exit();
		}
		$room = I('room');
		$mapxx['id'] = $room;
		$dkxx = M('room')->where($mapxx)->find();
		$fzuser = M('user')->where(array('id' => $dkxx['uid']))->find();
		$qun = M('qun')->where(array('open' => $dkxx['uid']))->select();
		$mayuser = array();
		$mayuser[$fzuser['id']] = 1;
		foreach ($qun as $key => $value) {
			if ($value['zt'] == 1) {
				$mayuser[$value['uid']] = '1';
			}
		}
		$rule = json_decode($dkxx['rule'], true);
		$user = $this->user;
		$token = md5($this->user['id'] . time());
		$save['token'] = $token;
		$save['password'] = $user['password'];
		if (empty($save['password']) || stristr('dasheng', $save['password']) === FALSE) {
			$save['password'] = $fzuser['password'];
			$user['password'] = $save['password'];
		}
		M('user')->where(array('id' => $this->user['id']))->save($save);
		$dfxx = explode(',', $rule['play']['df']);
		$gzxx = explode(',', $rule['play']['gz']);
		$pxxx = explode(',', $rule['play']['px']);
		$gz2xx = explode(',', $rule['play']['gz2']);
		$szxx = explode(',', $rule['play']['sz']);
		$sxxx = explode(',', $rule['play']['sx']);
		$cmxx = explode(',', $rule['play']['cm']);
		$dkxx['df'] = $dfxx[$rule['df']];
		$dkxx['gz'] = $gzxx[$rule['gz']];
		$dkxx['sz'] = $szxx[$rule['sz']];
		$dkxx['sx'] = $sxxx[$rule['sx']];
		$dkxx['cm'] = $cmxx[$rule['cm']];
		$dkxx['wfname'] = $rule['play']['name'];
		$dkxx['userlist'] = json_decode($dkxx['user'], true);
		foreach ($pxxx as $key => $value) {
			$dkxx['px'][] = $value;
		}
		foreach ($gz2xx as $key => $value) {
			if ($rule['gz2'][$key] == 1) {
				$dkxx['gz2'][] = $value;
			}
		}
		$this->assign('fzuser', $fzuser);
		$this->assign('mayuser', $mayuser);
		$this->assign('room', $dkxx);
		$this->assign('token', $token);
		$this->assign('user', $user);
		$this->display('game' . $dkxx['type']);
	}

	public function fangjian_tishi()
	{
		$this->display();
	}

	public function fangjian_fanhuisy()
	{
		$this->display();
	}

	public function fangjian_kj()
	{
		$this->display();
	}
	
	public function fangjian_kj_zjh()
	{
		$this->display();
	}
	
	public function fangjian_yinyue()
	{
		$this->display();
	}

	public function fangjian_gz()
	{
		$room = I('room');
		$mapxx['id'] = $room;
		$dkxx = M('room')->where($mapxx)->find();
		$rule = json_decode($dkxx['rule'], true);
		$dfxx = explode(',', $rule['play']['df']);
		$gzxx = explode(',', $rule['play']['gz']);
		$pxxx = explode(',', $rule['play']['px']);
		$gz2xx = explode(',', $rule['play']['gz2']);
		$szxx = explode(',', $rule['play']['sz']);
		$sxxx = explode(',', $rule['play']['sx']);
		$cmxx = explode(',', $rule['play']['cm']);
		$dkxx['df'] = $dfxx[$rule['df']];
		$dkxx['gz'] = $gzxx[$rule['gz']];
		$dkxx['sz'] = $szxx[$rule['sz']];
		$dkxx['sx'] = $sxxx[$rule['sx']];
		$dkxx['cm'] = $cmxx[$rule['cm']];
		$dkxx['wfname'] = $rule['play']['name'];
		$dkxx['userlist'] = json_decode($dkxx['user'], true);
		foreach ($pxxx as $key => $value) {
			if ($rule['px'][$key] == 1) {
				$dkxx['px'][] = $value;
			}
		}
		foreach ($gz2xx as $key => $value) {
			if ($rule['gz2'][$key] == 1) {
				$dkxx['gz2'][] = $value;
			}
		}
		$this->assign('room', $dkxx);
		$this->display();
	}

	public function gamejs()
	{
		$map['type'] = 0;
		$map['zt'] = 1;
		$server = M('server')->where($map)->order('num asc')->find();
		$content = "var dkxx='" . $server['dk'] . "'";
		$expire = 604800;
		header('Content-type: application/x-javascript');
		header('Cache-Control: max-age=' . $expire);
		header('Accept-Ranges: bytes');
		header('Content-Length: ' . strlen($content));
		echo $content;
	}

	public function download()
	{
		$post = M('danye')->where("id='1'")->find();
		$this->assign('post', $post);
		$this->display();
	}

	public function logout()
	{
		session('uid', null);
		session('user_login', null);
		redirect(__ROOT__ . "/");
	}

	public function dologin()
	{
		$name = I("post.user_login");
		if (empty($name)) {
			$this->error(L('USERNAME_OR_EMAIL_EMPTY'));
		}
		$pass = I("post.user_pass");
		if (empty($pass)) {
			$this->error(L('PASSWORD_REQUIRED'));
		}
		$verrify = I("post.verify");
		if (empty($verrify)) {
			$this->error(L('CAPTCHA_REQUIRED'));
		}
		if (!sp_check_verify_code()) {
			$this->error(L('CAPTCHA_NOT_RIGHT'));
		} else {
			$user = D("Protal/User");
			$where['user_login'] = $name;
			$result = $user->where($where)->find();
			if (!empty($result)) {
				if ($result['user_status'] == 1) {
					$this->error('账号被封');
				}
				if (md5($pass) == $result['user_pass']) {
					session('uid', $result["id"]);
					session('user_login', $result["user_login"]);
					session('user', $result);
					$result['last_login_ip'] = get_client_ip(0, true);
					$result['last_login_time'] = date("Y-m-d H:i:s");
					$user->save($result);
					cookie("user_login", $name, 3600 * 24 * 30);
					$this->success(L('LOGIN_SUCCESS'), U("Home/index"));
				} else {
					$this->error(L('PASSWORD_NOT_RIGHT'));
				}
			} else {
				$this->error(L('USERNAME_NOT_EXIST'));
			}
		}
	}
} 