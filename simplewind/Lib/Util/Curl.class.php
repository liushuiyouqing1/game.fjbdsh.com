<?php

class Curl
{
	public function execute($method, $url, $fields = '', $userAgent = '', $httpHeaders = '', $username = '', $password = '')
	{
		$ch = $this->create();
		if (false === $ch) {
			return false;
		}
		if (is_string($url) && strlen($url)) {
			$ret = curl_setopt($ch, CURLOPT_URL, $url);
		} else {
			return false;
		}
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($username != '') {
			curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
		}
		if (stripos($url, "https://") !== FALSE) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		}
		$method = strtolower($method);
		if ('post' == $method) {
			curl_setopt($ch, CURLOPT_POST, true);
			if (is_array($fields)) {
				$sets = array();
				foreach ($fields AS $key => $val) {
					$sets[] = $key . '=' . urlencode($val);
				}
				$fields = implode('&', $sets);
			}
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		} else if ('put' == $method) {
			curl_setopt($ch, CURLOPT_PUT, true);
		}
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		if (strlen($userAgent)) {
			curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
		}
		if (is_array($httpHeaders)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
		}
		$ret = curl_exec($ch);
		if (curl_errno($ch)) {
			curl_close($ch);
			return array(curl_error($ch), curl_errno($ch));
		} else {
			curl_close($ch);
			if (!is_string($ret) || !strlen($ret)) {
				return false;
			}
			return $ret;
		}
	}

	public function post($url, $fields, $userAgent = '', $httpHeaders = '', $username = '', $password = '')
	{
		$ret = $this->execute('POST', $url, $fields, $userAgent, $httpHeaders, $username, $password);
		if (false === $ret) {
			return false;
		}
		if (is_array($ret)) {
			return false;
		}
		return $ret;
	}

	public function get($url, $userAgent = '', $httpHeaders = '', $username = '', $password = '')
	{
		$ret = $this->execute('GET', $url, "", $userAgent, $httpHeaders, $username, $password);
		if (false === $ret) {
			return false;
		}
		if (is_array($ret)) {
			return false;
		}
		return $ret;
	}

	public function create()
	{
		$ch = null;
		if (!function_exists('curl_init')) {
			return false;
		}
		$ch = curl_init();
		if (!is_resource($ch)) {
			return false;
		}
		return $ch;
	}
} 