<?php
namespace Behavior;
class UpgradeNoticeBehavior
{
	protected $header_ = '';
	protected $httpCode_;
	protected $httpDesc_;
	protected $accesskey_;
	protected $secretkey_;

	public function run(&$params)
	{
		if (C('UPGRADE_NOTICE_ON') && (!S('think_upgrade_interval') || C('UPGRADE_NOTICE_DEBUG'))) {
			if (IS_SAE && C('UPGRADE_NOTICE_QUEUE') && !isset($_POST['think_upgrade_queque'])) {
				$queue = new SaeTaskQueue(C('UPGRADE_NOTICE_QUEUE'));
				$queue->addTask('http://' . $_SERVER['HTTP_HOST'] . __APP__, 'think_upgrade_queque=1');
				if (!$queue->push()) {
					trace('升级提醒队列执行失败,错误原因：' . $queue->errmsg(), '升级通知出错', 'NOTIC', true);
				}
				return;
			}
			$akey = C('UPGRADE_NOTICE_AKEY', null, '');
			$skey = C('UPGRADE_NOTICE_SKEY', null, '');
			$this->accesskey_ = $akey ? $akey : (defined('SAE_ACCESSKEY') ? SAE_ACCESSKEY : '');
			$this->secretkey_ = $skey ? $skey : (defined('SAE_SECRETKEY') ? SAE_SECRETKEY : '');
			$current_version = C('UPGRADE_CURRENT_VERSION', null, 0);
			$info = $this->send('http://sinaclouds.sinaapp.com/thinkapi/upgrade.php?v=' . $current_version);
			if ($info['version'] != $current_version) {
				if ($this->send_sms($info['msg'])) trace($info['msg'], '升级通知成功', 'NOTIC', true);
			}
			S('think_upgrade_interval', true, C('UPGRADE_NOTICE_CHECK_INTERVAL', null, 604800));
		}
	}

	private function send_sms($msg)
	{
		$timestamp = time();
		$url = 'http://inno.smsinter.sina.com.cn/sae_sms_service/sendsms.php';
		$content = "FetchUrl" . $url . "TimeStamp" . $timestamp . "AccessKey" . $this->accesskey_;
		$signature = (base64_encode(hash_hmac('sha256', $content, $this->secretkey_, true)));
		$headers = array("FetchUrl: $url", "AccessKey: " . $this->accesskey_, "TimeStamp: " . $timestamp, "Signature: $signature");
		$data = array('mobile' => C('UPGRADE_NOTICE_MOBILE', null, ''), 'msg' => $msg, 'encoding' => 'UTF-8');
		if (!$ret = $this->send('http://g.apibus.io', $data, $headers)) {
			return false;
		}
		if (isset($ret['ApiBusError'])) {
			trace('errno:' . $ret['ApiBusError']['errcode'] . ',errmsg:' . $ret['ApiBusError']['errdesc'], '升级通知出错', 'NOTIC', true);
			return false;
		}
		return true;
	}

	private function send($url, $params = array(), $headers = array())
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		if (!empty($params)) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		}
		if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$txt = curl_exec($ch);
		if (curl_errno($ch)) {
			trace(curl_error($ch), '升级通知出错', 'NOTIC', true);
			return false;
		}
		curl_close($ch);
		$ret = json_decode($txt, true);
		if (!$ret) {
			trace('接口[' . $url . ']返回格式不正确', '升级通知出错', 'NOTIC', true);
			return false;
		}
		return $ret;
	}
} 