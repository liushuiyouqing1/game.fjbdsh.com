<?php

class T163SDK extends ThinkOauth
{
	protected $GetRequestCodeURL = 'https://api.t.163.com/oauth2/authorize';
	protected $GetAccessTokenURL = 'https://api.t.163.com/oauth2/access_token';
	protected $ApiBase = 'https://api.t.163.com/';

	public function call($api, $param = '', $method = 'GET', $multi = false)
	{
		$params = array('oauth_token' => $this->Token['access_token'],);
		$data = $this->http($this->url($api, '.json'), $this->param($params, $param), $method);
		return json_decode($data, true);
	}

	protected function parseToken($result, $extend)
	{
		$data = json_decode($result, true);
		if ($data['uid'] && $data['access_token'] && $data['expires_in'] && $data['refresh_token']) {
			$data['openid'] = $data['uid'];
			unset($data['uid']);
			return $data;
		} else throw new Exception("获取网易微博ACCESS_TOKEN出错：{$data['error']}");
	}

	public function openid()
	{
		if (isset($this->Token['openid'])) return $this->Token['openid'];
		$data = $this->call('users/show');
		if (!empty($data['id'])) return $data['id']; else throw new Exception('没有获取到网易微博用户ID！');
	}
}