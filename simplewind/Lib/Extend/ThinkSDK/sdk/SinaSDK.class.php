<?php

class SinaSDK extends ThinkOauth
{
	protected $GetRequestCodeURL = 'https://api.weibo.com/oauth2/authorize';
	protected $GetAccessTokenURL = 'https://api.weibo.com/oauth2/access_token';
	protected $ApiBase = 'https://api.weibo.com/2/';

	public function call($api, $param = '', $method = 'GET', $multi = false)
	{
		$params = array('access_token' => $this->Token['access_token'],);
		$vars = $this->param($params, $param);
		$data = $this->http($this->url($api, '.json'), $vars, $method, array(), $multi);
		return json_decode($data, true);
	}

	protected function parseToken($result, $extend)
	{
		$data = json_decode($result, true);
		if ($data['access_token'] && $data['expires_in'] && $data['remind_in'] && $data['uid']) {
			$data['openid'] = $data['uid'];
			unset($data['uid']);
			return $data;
		} else throw new Exception("获取新浪微博ACCESS_TOKEN出错：{$data['error']}");
	}

	public function openid()
	{
		$data = $this->Token;
		if (isset($data['openid'])) return $data['openid']; else throw new Exception('没有获取到新浪微博用户ID！');
	}
}