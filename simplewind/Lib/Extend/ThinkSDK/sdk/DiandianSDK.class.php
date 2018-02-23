<?php

class DiandianSDK extends ThinkOauth
{
	protected $GetRequestCodeURL = 'https://api.diandian.com/oauth/authorize';
	protected $GetAccessTokenURL = 'https://api.diandian.com/oauth/token';
	protected $ApiBase = 'https://api.diandian.com/v1/';

	public function call($api, $param = '', $method = 'GET', $multi = false)
	{
		$params = array('access_token' => $this->Token['access_token'],);
		$data = $this->http($this->url($api, '.json'), $this->param($params, $param), $method);
		return json_decode($data, true);
	}

	protected function parseToken($result, $extend)
	{
		$data = json_decode($result, true);
		if ($data['access_token'] && $data['expires_in'] && $data['token_type'] && $data['uid']) {
			$data['openid'] = $data['uid'];
			unset($data['uid']);
			return $data;
		} else throw new Exception("获取点点网ACCESS_TOKEN出错：{$data['error']}");
	}

	public function openid()
	{
		$data = $this->Token;
		if (isset($data['openid'])) return $data['openid']; else throw new Exception('没有获取到点点网用户ID！');
	}
}