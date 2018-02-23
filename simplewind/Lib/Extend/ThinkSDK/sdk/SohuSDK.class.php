<?php

class SohuSDK extends ThinkOauth
{
	protected $GetRequestCodeURL = 'https://api.sohu.com/oauth2/authorize';
	protected $GetAccessTokenURL = 'https://api.sohu.com/oauth2/token';
	protected $ApiBase = 'https://api.sohu.com/rest/';

	public function call($api, $param = '', $method = 'GET', $multi = false)
	{
		$params = array('access_token' => $this->Token['access_token'],);
		$data = $this->http($this->url($api), $this->param($params, $param), $method);
		return json_decode($data, true);
	}

	protected function parseToken($result, $extend)
	{
		$data = json_decode($result, true);
		if ($data['access_token'] && $data['expires_in'] && $data['refresh_token'] && $data['open_id']) {
			$data['openid'] = $data['open_id'];
			unset($data['open_id']);
			return $data;
		} else throw new Exception("获取搜狐ACCESS_TOKEN出错：{$data['error']}");
	}

	public function openid()
	{
		$data = $this->Token;
		if (isset($data['openid'])) return $data['openid']; else throw new Exception('没有获取到搜狐用户ID！');
	}
}