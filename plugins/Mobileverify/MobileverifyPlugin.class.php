<?php
namespace plugins\Mobileverify;

use Common\Lib\Plugin;

class MobileverifyPlugin extends Plugin
{
	public $info = array('name' => 'Mobileverify', 'title' => '手机验证码', 'description' => '手机验证码', 'status' => 1, 'author' => 'ThinkCMF', 'version' => '1.0');
	public $has_admin = 1;

	public function install()
	{
		return true;
	}

	public function uninstall()
	{
		return true;
	}

	public function send_mobile_verify_code($param)
	{
		$to = $param['mobile'];
		$config = $this->getConfig();
		$expire_minute = intval($config['expire_minute']);
		$expire_minute = empty($expire_minute) ? 30 : $expire_minute;
		$expire_time = time() + $expire_minute * 60;
		$code = sp_get_mobile_code($param['mobile'], $expire_time);
		$result = false;
		if ($code !== false) {
			import("CCPRestSmsSDK", './plugins/Mobileverify/Lib', ".php");
			$datas = array($code, $expire_minute);
			$tempId = $config['template_id'];
			$accountSid = $config['account_sid'];
			$accountToken = $config['auth_token'];
			$appId = $config['app_id'];
			$serverIP = 'app.cloopen.com';
			$serverPort = '8883';
			$softVersion = '2013-12-26';
			$rest = new \YunTongXunREST($serverIP, $serverPort, $softVersion);
			$rest->setAccount($accountSid, $accountToken);
			$rest->setAppId($appId);
			$reponse = $rest->sendTemplateSMS($to, $datas, $tempId);
			$reponse = json_decode(json_encode($reponse), true);
			if (empty($reponse)) {
				$result = array('error' => 1, 'error_msg' => '云通讯返回结果错误');
			} else {
				if ($reponse['statusCode'] != 0) {
					$result = array('error' => 1, 'error_msg' => $reponse['statusMsg']);
				} else {
					$result = array('error' => 0, 'error_msg' => '发送成功！');
				}
			}
		} else {
			$result = array('error' => 1, 'error_msg' => '发送次数过多，不能再发送');
		}
		if ($result['error'] === 0) {
			sp_mobile_code_log($to, $code, $expire_time);
		}
		return $result;
	}
}