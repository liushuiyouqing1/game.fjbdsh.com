<?php

class KaixinSDK extends ThinkOauth
{
	protected $GetRequestCodeURL = 'http://api.kaixin001.com/oauth2/authorize';
	protected $GetAccessTokenURL = 'https://api.kaixin001.com/oauth2/access_token';
	protected $ApiBase = 'https://api.kaixin001.com/';

	public function call($api, $param = '', $method = 'GET', $multi = false)
	{
		$params = array('access_token' => $this->Token['access_token'],);
		$data = $this->http($this->url($api, '.json'), $this->param($params, $param), $method);
		return json_decode($data, true);
	}

	protected function parseToken($result, $extend)
	{
		$data = json_decode($result, true);
		if ($data['access_token'] && $data['expires_in'] && $data['refresh_token']) {
			$this->Token = $data;
			$data['openid'] = $this->openid();
			return $data;
		} else throw new Exception("获取开心网ACCESS_TOKEN出错：{$data['error']}");
	}

	public function openid()
	{
		if (isset($this->Token['openid'])) return $this->Token['openid'];
		$data = $this->call('users/me');
		if (!empty($data['uid'])) return $data['uid']; else throw new Exception('没有获取到开心网用户ID！');
	}
}