<?php

class DoubanSDK extends ThinkOauth
{
	protected $GetRequestCodeURL = 'https://www.douban.com/service/auth2/auth';
	protected $GetAccessTokenURL = 'https://www.douban.com/service/auth2/token';
	protected $ApiBase = 'https://api.douban.com/v2/';

	public function call($api, $param = '', $method = 'GET', $multi = false)
	{
		$params = array();
		$header = array("Authorization: Bearer {$this->Token['access_token']}");
		$data = $this->http($this->url($api), $this->param($params, $param), $method, $header);
		return json_decode($data, true);
	}

	protected function parseToken($result, $extend)
	{
		$data = json_decode($result, true);
		if ($data['access_token'] && $data['expires_in'] && $data['refresh_token'] && $data['douban_user_id']) {
			$data['openid'] = $data['douban_user_id'];
			unset($data['douban_user_id']);
			return $data;
		} else throw new Exception("获取豆瓣ACCESS_TOKEN出错：{$data['msg']}");
	}

	public function openid()
	{
		$data = $this->Token;
		if (isset($data['douban_user_id'])) return $data['douban_user_id']; else throw new Exception('没有获取到豆瓣用户ID！');
	}
}