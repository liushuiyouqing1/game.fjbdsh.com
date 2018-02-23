<?php
namespace Guzzle\Http\Exception;

use Guzzle\Http\Curl\CurlHandle;

class CurlException extends RequestException
{
	private $curlError;
	private $curlErrorNo;
	private $handle;
	private $curlInfo = array();

	public function setError($error, $number)
	{
		$this->curlError = $error;
		$this->curlErrorNo = $number;
		return $this;
	}

	public function setCurlHandle(CurlHandle $handle)
	{
		$this->handle = $handle;
		return $this;
	}

	public function getCurlHandle()
	{
		return $this->handle;
	}

	public function getError()
	{
		return $this->curlError;
	}

	public function getErrorNo()
	{
		return $this->curlErrorNo;
	}

	public function getCurlInfo()
	{
		return $this->curlInfo;
	}

	public function setCurlInfo(array $info)
	{
		$this->curlInfo = $info;
		return $this;
	}
} 