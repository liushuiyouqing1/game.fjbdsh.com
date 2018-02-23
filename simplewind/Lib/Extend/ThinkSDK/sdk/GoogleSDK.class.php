<?php

class GoogleSDK extends ThinkOauth
{
	protected $GetRequestCodeURL = 'https://accounts.google.com/o/oauth2/auth';
	protected $GetAccessTokenURL = 'https://accounts.google.com/o/oauth2/token';
	protected $Authorize = 'scope=https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email';
	protected $ApiBase = 'https://www.googleapis.com/oauth2/v1/';

	public function call($api, $param = '', $method = 'GET', $multi = false)
	{
		$params = array();
		$header = array("Authorization: Bearer {$this->Token['access_token']}");
		$data = $this->http($this->url($api), $this->param($params, $param), $method, $header);
		return json_decode($data, true);
	}

	protected function parseToken($result, $extend)
	{
		$data = json_decode($result, true);
		if ($data['access_token'] && $data['token_type'] && $data['expires_in']) {
			$this->Token = $data;
			$data['openid'] = $this->openid();
			return $data;
		} else throw new Exception("获取 Google ACCESS_TOKEN出错：未知错误");
	}

	public function openid()
	{
		if (isset($this->Token['openid'])) return $this->Token['openid'];
		$data = $this->call('userinfo');
		if (!empty($data['id'])) return $data['id']; else throw new Exception('没有获取到 Google 用户ID！');
	}
}