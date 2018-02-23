<?php

abstract class ThinkOauth
{
	protected $Version = '2.0';
	protected $AppKey = '';
	protected $AppSecret = '';
	protected $ResponseType = 'code';
	protected $GrantType = 'authorization_code';
	protected $Callback = '';
	protected $Authorize = '';
	protected $GetRequestCodeURL = '';
	protected $GetAccessTokenURL = '';
	protected $ApiBase = '';
	protected $Token = null;
	private $Type = '';

	public function __construct($token = null)
	{
		$class = get_class($this);
		$this->Type = strtoupper(substr($class, 0, strlen($class) - 3));
		$config = C("THINK_SDK_{$this->Type}");
		if (empty($config['APP_KEY']) || empty($config['APP_SECRET'])) {
			throw new Exception('请配置您申请的APP_KEY和APP_SECRET');
		} else {
			$this->AppKey = $config['APP_KEY'];
			$this->AppSecret = $config['APP_SECRET'];
			$this->Token = $token;
		}
	}

	public static function getInstance($type, $token = null)
	{
		$name = ucfirst(strtolower($type)) . 'SDK';
		require_once "sdk/{$name}.class.php";
		if (class_exists($name)) {
			return new $name($token);
		} else {
			E(L('_CLASS_NOT_EXIST_') . ':' . $name);
		}
	}

	private function config()
	{
		$config = C("THINK_SDK_{$this->Type}");
		if (!empty($config['AUTHORIZE'])) $this->Authorize = $config['AUTHORIZE'];
		if (!empty($config['CALLBACK'])) $this->Callback = $config['CALLBACK']; else throw new Exception('请配置回调页面地址');
	}

	public function getRequestCodeURL()
	{
		$this->config();
		$params = array('client_id' => $this->AppKey, 'redirect_uri' => $this->Callback, 'response_type' => $this->ResponseType,);
		if ($this->Authorize) {
			parse_str($this->Authorize, $_param);
			if (is_array($_param)) {
				$params = array_merge($params, $_param);
			} else {
				throw new Exception('AUTHORIZE配置不正确！');
			}
		}
		return $this->GetRequestCodeURL . '?' . http_build_query($params);
	}

	public function getAccessToken($code, $extend = null)
	{
		$this->config();
		$params = array('client_id' => $this->AppKey, 'client_secret' => $this->AppSecret, 'grant_type' => $this->GrantType, 'code' => $code, 'redirect_uri' => $this->Callback,);
		$data = $this->http($this->GetAccessTokenURL, $params, 'POST');
		$this->Token = $this->parseToken($data, $extend);
		return $this->Token;
	}

	protected function param($params, $param)
	{
		if (is_string($param)) parse_str($param, $param);
		return array_merge($params, $param);
	}

	protected function url($api, $fix = '')
	{
		return $this->ApiBase . $api . $fix;
	}

	protected function http($url, $params, $method = 'GET', $header = array(), $multi = false)
	{
		$opts = array(CURLOPT_TIMEOUT => 30, CURLOPT_RETURNTRANSFER => 1, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_HTTPHEADER => $header);
		switch (strtoupper($method)) {
			case 'GET':
				$opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
				break;
			case 'POST':
				$params = $multi ? $params : http_build_query($params);
				$opts[CURLOPT_URL] = $url;
				$opts[CURLOPT_POST] = 1;
				$opts[CURLOPT_POSTFIELDS] = $params;
				break;
			default:
				throw new Exception('不支持的请求方式！');
		}
		$ch = curl_init();
		curl_setopt_array($ch, $opts);
		$data = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);
		if ($error) throw new Exception('请求发生错误：' . $error);
		return $data;
	}

	abstract protected function call($api, $param = '', $method = 'GET', $multi = false);

	abstract protected function parseToken($result, $extend);

	abstract public function openid();
}