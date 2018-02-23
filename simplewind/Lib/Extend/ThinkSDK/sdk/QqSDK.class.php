<?php

class QqSDK extends ThinkOauth
{
	protected $GetRequestCodeURL = 'https://graph.qq.com/oauth2.0/authorize';
	protected $GetAccessTokenURL = 'https://graph.qq.com/oauth2.0/token';
	protected $Authorize = 'scope=get_user_info,add_share';
	protected $ApiBase = 'https://graph.qq.com/';

	public function call($api, $param = '', $method = 'GET', $multi = false)
	{
		$params = array('oauth_consumer_key' => $this->AppKey, 'access_token' => $this->Token['access_token'], 'openid' => $this->openid(), 'format' => 'json');
		$data = $this->http($this->url($api), $this->param($params, $param), $method);
		return json_decode($data, true);
	}

	protected function parseToken($result, $extend)
	{
		parse_str($result, $data);
		if ($data['access_token'] && $data['expires_in']) {
			$this->Token = $data;
			$data['openid'] = $this->openid();
			return $data;
		} else throw new Exception("获取腾讯QQ ACCESS_TOKEN 出错：{$result}");
	}

	public function openid()
	{
		$data = $this->Token;
		if (isset($data['openid'])) return $data['openid']; elseif ($data['access_token']) {
			$data = $this->http($this->url('oauth2.0/me'), array('access_token' => $data['access_token']));
			$data = json_decode(trim(substr($data, 9), " );\n"), true);
			if (isset($data['openid'])) return $data['openid']; else throw new Exception("获取用户openid出错：{$data['error_description']}");
		} else {
			throw new Exception('没有获取到openid！');
		}
	}
}