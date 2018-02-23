<?php

class WeixinSDK extends ThinkOauth
{
	protected $GetRequestCodeURL = 'https://open.weixin.qq.com/connect/qrconnect';
	protected $GetAccessTokenURL = 'https://api.weixin.qq.com/sns/oauth2/access_token';
	protected $ApiBase = 'https://api.weixin.qq.com/';

	public function getRequestCodeURL()
	{
		$this->config();
		$params = array('appid' => $this->AppKey, 'redirect_uri' => $this->Callback, 'response_type' => 'code', 'scope' => 'snsapi_login');
		return $this->GetRequestCodeURL . '?' . http_build_query($params);
	}

	public function getAccessToken($code, $extend = null)
	{
		$this->config();
		$params = array('appid' => $this->AppKey, 'secret' => $this->AppSecret, 'grant_type' => $this->GrantType, 'code' => $code,);
		$data = $this->http($this->GetAccessTokenURL, $params, 'POST');
		$this->Token = $this->parseToken($data, $extend);
		return $this->Token;
	}

	public function call($api, $param = '', $method = 'GET', $multi = false)
	{
		$params = array('access_token' => $this->Token['access_token'], 'openid' => $this->openid(),);
		$vars = $this->param($params, $param);
		$data = $this->http($this->url($api), $vars, $method, array(), $multi);
		return json_decode($data, true);
	}

	protected function parseToken($result, $extend)
	{
		$data = json_decode($result, true);
		if ($data['access_token'] && $data['expires_in']) {
			$this->Token = $data;
			$data['openid'] = $this->openid();
			return $data;
		} else throw new Exception("获取微信 ACCESS_TOKEN 出错：{$result}");
	}

	public function openid()
	{
		$data = $this->Token;
		if (!empty($data['openid'])) return $data['openid']; else exit('没有获取到微信用户ID！');
	}
} 