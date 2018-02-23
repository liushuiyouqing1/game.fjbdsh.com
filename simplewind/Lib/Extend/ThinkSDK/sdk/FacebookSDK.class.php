<?php

class FacebookSDK extends ThinkOauth
{
	protected $GetRequestCodeURL = 'https://www.facebook.com/dialog/oauth';
	protected $GetAccessTokenURL = 'https://graph.facebook.com/oauth/access_token';
	protected $Authorize = 'scope=email';
	protected $ApiBase = 'https://graph.facebook.com/';

	public function call($api, $param = '', $method = 'GET', $multi = false)
	{
		$params = array('access_token' => $this->Token['access_token']);
		$header = array();
		$data = $this->http($this->url($api), $this->param($params, $param), $method, $header);
		return json_decode($data, true);
	}

	protected function parseToken($result, $extend)
	{
		parse_str($result, $data);
		if (is_array($data) && $data['access_token'] && $data['expires']) {
			$this->Token = $data;
			$data['openid'] = $this->openid();
			return $data;
		} else {
			throw new Exception("获取 facebook ACCESS_TOKEN出错：未知错误");
		}
	}

	public function openid()
	{
		if (isset($this->Token['openid'])) return $this->Token['openid'];
		$data = $this->call('me');
		if (!empty($data['id'])) return $data['id']; else throw new Exception('没有获取到 facebook 用户ID！');
	}
}