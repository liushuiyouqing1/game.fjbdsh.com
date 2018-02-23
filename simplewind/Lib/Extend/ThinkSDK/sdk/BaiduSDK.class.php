<?php

class BaiduSDK extends ThinkOauth
{
	protected $GetRequestCodeURL = 'https://openapi.baidu.com/oauth/2.0/authorize';
	protected $GetAccessTokenURL = 'https://openapi.baidu.com/oauth/2.0/token';
	protected $ApiBase = 'https://openapi.baidu.com/rest/2.0/';

	public function call($api, $param = '', $method = 'GET', $multi = false)
	{
		$params = array('access_token' => $this->Token['access_token'],);
		$data = $this->http($this->url($api), $this->param($params, $param), $method);
		return json_decode($data, true);
	}

	protected function parseToken($result, $extend)
	{
		$data = json_decode($result, true);
		if ($data['access_token'] && $data['expires_in'] && $data['refresh_token']) {
			$this->Token = $data;
			$data['openid'] = $this->openid();
			return $data;
		} else throw new Exception("获取百度ACCESS_TOKEN出错：{$data['error']}");
	}

	public function openid()
	{
		if (isset($this->Token['openid'])) return $this->Token['openid'];
		$data = $this->call('passport/users/getLoggedInUser');
		if (!empty($data['uid'])) return $data['uid']; else throw new Exception('没有获取到百度用户ID！');
	}
}