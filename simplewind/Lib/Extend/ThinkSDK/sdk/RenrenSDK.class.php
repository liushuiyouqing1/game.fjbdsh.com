<?php

class RenrenSDK extends ThinkOauth
{
	protected $GetRequestCodeURL = 'https://graph.renren.com/oauth/authorize';
	protected $GetAccessTokenURL = 'https://graph.renren.com/oauth/token';
	protected $ApiBase = 'http://api.renren.com/restserver.do';

	public function call($api, $param = '', $method = 'POST', $multi = false)
	{
		$params = array('method' => $api, 'access_token' => $this->Token['access_token'], 'v' => '1.0', 'format' => 'json',);
		$data = $this->http($this->url(''), $this->param($params, $param), $method);
		return json_decode($data, true);
	}

	protected function param($params, $param)
	{
		$params = parent::param($params, $param);
		ksort($params);
		$param = array();
		foreach ($params as $key => $value) {
			$param[] = "{$key}={$value}";
		}
		$sign = implode('', $param) . $this->AppSecret;
		$params['sig'] = md5($sign);
		return $params;
	}

	protected function parseToken($result, $extend)
	{
		$data = json_decode($result, true);
		if ($data['access_token'] && $data['expires_in'] && $data['refresh_token'] && $data['user']['id']) {
			$data['openid'] = $data['user']['id'];
			unset($data['user']);
			return $data;
		} else throw new Exception("获取人人网ACCESS_TOKEN出错：{$data['error_description']}");
	}

	public function openid()
	{
		$data = $this->Token;
		if (!empty($data['openid'])) return $data['openid']; else throw new Exception('没有获取到人人网用户ID！');
	}
}