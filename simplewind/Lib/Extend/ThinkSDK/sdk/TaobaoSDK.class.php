<?php

class TaobaoSDK extends ThinkOauth
{
	protected $GetRequestCodeURL = 'https://oauth.taobao.com/authorize';
	protected $GetAccessTokenURL = 'https://oauth.taobao.com/token';
	protected $ApiBase = 'https://eco.taobao.com/router/rest';

	public function call($api, $param = '', $method = 'GET', $multi = false)
	{
		$params = array('method' => $api, 'access_token' => $this->Token['access_token'], 'format' => 'json', 'v' => '2.0',);
		$data = $this->http($this->url(''), $this->param($params, $param), $method);
		return json_decode($data, true);
	}

	protected function parseToken($result, $extend)
	{
		$data = json_decode($result, true);
		if ($data['access_token'] && $data['expires_in'] && $data['taobao_user_id']) {
			$data['openid'] = $data['taobao_user_id'];
			unset($data['taobao_user_id']);
			return $data;
		} else throw new Exception("获取淘宝网ACCESS_TOKEN出错：{$data['error']}");
	}

	public function openid()
	{
		$data = $this->Token;
		if (isset($data['openid'])) return $data['openid']; else throw new Exception('没有获取到淘宝网用户ID！');
	}
}