<?php

class MsnSDK extends ThinkOauth
{
	protected $GetRequestCodeURL = 'https://login.live.com/oauth20_authorize.srf';
	protected $GetAccessTokenURL = 'https://login.live.com/oauth20_token.srf';
	protected $Authorize = 'scope=wl.basic wl.offline_access wl.signin wl.emails wl.photos';
	protected $ApiBase = 'https://apis.live.net/v5.0/';

	public function call($api, $param = '', $method = 'GET', $multi = false)
	{
		$params = array('access_token' => $this->Token['access_token'],);
		$data = $this->http($this->url($api), $this->param($params, $param), $method);
		return json_decode($data, true);
	}

	protected function parseToken($result, $extend)
	{
		$data = json_decode($result, true);
		if ($data['access_token'] && $data['token_type'] && $data['expires_in']) {
			$this->Token = $data;
			$data['openid'] = $this->openid();
			return $data;
		} else throw new Exception("获取 MSN ACCESS_TOKEN出错：未知错误");
	}

	public function openid()
	{
		if (isset($this->Token['openid'])) return $this->Token['openid'];
		$data = $this->call('me');
		if (!empty($data['id'])) return $data['id']; else throw new Exception('没有获取到 MSN 用户ID！');
	}
}