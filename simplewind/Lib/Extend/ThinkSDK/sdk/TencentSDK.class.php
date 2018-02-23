<?php

class TencentSDK extends ThinkOauth
{
	protected $GetRequestCodeURL = 'https://open.t.qq.com/cgi-bin/oauth2/authorize';
	protected $GetAccessTokenURL = 'https://open.t.qq.com/cgi-bin/oauth2/access_token';
	protected $ApiBase = 'https://open.t.qq.com/api/';

	public function call($api, $param = '', $method = 'GET', $multi = false)
	{
		$params = array('oauth_consumer_key' => $this->AppKey, 'access_token' => $this->Token['access_token'], 'openid' => $this->openid(), 'clientip' => get_client_ip(), 'oauth_version' => '2.a', 'scope' => 'all', 'format' => 'json');
		$vars = $this->param($params, $param);
		$data = $this->http($this->url($api), $vars, $method, array(), $multi);
		return json_decode($data, true);
	}

	protected function parseToken($result, $extend)
	{
		parse_str($result, $data);
		$data = array_merge($data, $extend);
		if ($data['access_token'] && $data['expires_in'] && $data['openid']) return $data; else throw new Exception("获取腾讯微博 ACCESS_TOKEN 出错：{$result}");
	}

	public function openid()
	{
		$data = $this->Token;
		if (isset($data['openid'])) return $data['openid']; else throw new Exception('没有获取到openid！');
	}
}