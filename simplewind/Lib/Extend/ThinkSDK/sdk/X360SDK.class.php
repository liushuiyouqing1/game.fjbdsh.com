<?php

class X360SDK extends ThinkOauth
{
	protected $GetRequestCodeURL = 'https://openapi.360.cn/oauth2/authorize';
	protected $GetAccessTokenURL = 'https://openapi.360.cn/oauth2/access_token';
	protected $ApiBase = 'https://openapi.360.cn/';

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
		} else throw new Exception("获取360开放平台ACCESS_TOKEN出错：{$data['error']}");
	}

	public function openid()
	{
		if (isset($this->Token['openid'])) return $this->Token['openid'];
		$data = $this->call('user/me');
		if (!empty($data['id'])) return $data['id']; else throw new Exception('没有获取到360开放平台用户ID！');
	}
}