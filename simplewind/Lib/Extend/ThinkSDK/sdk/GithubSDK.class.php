<?php

class GithubSDK extends ThinkOauth
{
	protected $GetRequestCodeURL = 'https://github.com/login/oauth/authorize';
	protected $GetAccessTokenURL = 'https://github.com/login/oauth/access_token';
	protected $ApiBase = 'https://api.github.com/';

	public function call($api, $param = '', $method = 'GET', $multi = false)
	{
		$params = array();
		$header = array("Authorization: bearer {$this->Token['access_token']}");
		$data = $this->http($this->url($api), $this->param($params, $param), $method, $header);
		return json_decode($data, true);
	}

	protected function parseToken($result, $extend)
	{
		parse_str($result, $data);
		if ($data['access_token'] && $data['token_type']) {
			$this->Token = $data;
			$data['openid'] = $this->openid();
			return $data;
		} else throw new Exception("获取 Github ACCESS_TOKEN出错：未知错误");
	}

	public function openid()
	{
		if (isset($this->Token['openid'])) return $this->Token['openid'];
		$data = $this->call('user');
		if (!empty($data['id'])) return $data['id']; else throw new Exception('没有获取到 Github 用户ID！');
	}
}