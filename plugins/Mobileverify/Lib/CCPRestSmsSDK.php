<?php

class YunTongXunREST
{
	private $AccountSid;
	private $AccountToken;
	private $AppId;
	private $ServerIP;
	private $ServerPort;
	private $SoftVersion;
	private $Batch;
	private $BodyType = "xml";
	private $enabeLog = true;
	private $Filename = "./log.txt";
	private $Handle;

	function __construct($ServerIP, $ServerPort, $SoftVersion)
	{
		$this->Batch = date("YmdHis");
		$this->ServerIP = $ServerIP;
		$this->ServerPort = $ServerPort;
		$this->SoftVersion = $SoftVersion;
		$this->Handle = fopen($this->Filename, 'a');
	}

	function setAccount($AccountSid, $AccountToken)
	{
		$this->AccountSid = $AccountSid;
		$this->AccountToken = $AccountToken;
	}

	function setAppId($AppId)
	{
		$this->AppId = $AppId;
	}

	function showlog($log)
	{
		if ($this->enabeLog) {
			fwrite($this->Handle, $log . "\n");
		}
	}

	function curl_post($url, $data, $header, $post = 1)
	{
		$ch = curl_init();
		$res = curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, $post);
		if ($post) curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		$result = curl_exec($ch);
		if ($result == FALSE) {
			if ($this->BodyType == 'json') {
				$result = "{\"statusCode\":\"172001\",\"statusMsg\":\"网络错误\"}";
			} else {
				$result = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?><Response><statusCode>172001</statusCode><statusMsg>网络错误</statusMsg></Response>";
			}
		}
		curl_close($ch);
		return $result;
	}

	function sendTemplateSMS($to, $datas, $tempId)
	{
		$auth = $this->accAuth();
		if ($auth != "") {
			return $auth;
		}
		if ($this->BodyType == "json") {
			$data = "";
			for ($i = 0; $i < count($datas); $i++) {
				$data = $data . "'" . $datas[$i] . "',";
			}
			$body = "{'to':'$to','templateId':'$tempId','appId':'$this->AppId','datas':[" . $data . "]}";
		} else {
			$data = "";
			for ($i = 0; $i < count($datas); $i++) {
				$data = $data . "<data>" . $datas[$i] . "</data>";
			}
			$body = "<TemplateSMS>
                    <to>$to</to> 
                    <appId>$this->AppId</appId>
                    <templateId>$tempId</templateId>
                    <datas>" . $data . "</datas>
                  </TemplateSMS>";
		}
		$this->showlog("request body = " . $body);
		$sig = strtoupper(md5($this->AccountSid . $this->AccountToken . $this->Batch));
		$url = "https://$this->ServerIP:$this->ServerPort/$this->SoftVersion/Accounts/$this->AccountSid/SMS/TemplateSMS?sig=$sig";
		$this->showlog("request url = " . $url);
		$authen = base64_encode($this->AccountSid . ":" . $this->Batch);
		$header = array("Accept:application/$this->BodyType", "Content-Type:application/$this->BodyType;charset=utf-8", "Authorization:$authen");
		$result = $this->curl_post($url, $body, $header);
		$this->showlog("response body = " . $result);
		if ($this->BodyType == "json") {
			$datas = json_decode($result);
		} else {
			$datas = simplexml_load_string(trim($result, " \t\n\r"));
		}
		if ($datas->statusCode == 0) {
			if ($this->BodyType == "json") {
				$datas->TemplateSMS = $datas->templateSMS;
				unset($datas->templateSMS);
			}
		}
		return $datas;
	}

	function accAuth()
	{
		if ($this->ServerIP == "") {
			$data = new stdClass();
			$data->statusCode = '172004';
			$data->statusMsg = 'IP为空';
			return $data;
		}
		if ($this->ServerPort <= 0) {
			$data = new stdClass();
			$data->statusCode = '172005';
			$data->statusMsg = '端口错误（小于等于0）';
			return $data;
		}
		if ($this->SoftVersion == "") {
			$data = new stdClass();
			$data->statusCode = '172013';
			$data->statusMsg = '版本号为空';
			return $data;
		}
		if ($this->AccountSid == "") {
			$data = new stdClass();
			$data->statusCode = '172006';
			$data->statusMsg = '主帐号为空';
			return $data;
		}
		if ($this->AccountToken == "") {
			$data = new stdClass();
			$data->statusCode = '172007';
			$data->statusMsg = '主帐号令牌为空';
			return $data;
		}
		if ($this->AppId == "") {
			$data = new stdClass();
			$data->statusCode = '172012';
			$data->statusMsg = '应用ID为空';
			return $data;
		}
	}
} 