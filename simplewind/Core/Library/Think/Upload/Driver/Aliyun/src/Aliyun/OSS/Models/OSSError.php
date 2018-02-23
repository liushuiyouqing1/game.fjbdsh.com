<?php
namespace Aliyun\OSS\Models;
class OSSError
{
	private $code;
	private $requestId;
	private $message;
	private $hostId;

	public function getCode()
	{
		return $this->code;
	}

	public function setCode($code)
	{
		$this->code = $code;
	}

	public function getRequestId()
	{
		return $this->requestId;
	}

	public function setRequestId($requestId)
	{
		$this->requestId = $requestId;
	}

	public function getHostId()
	{
		return $this->hostId;
	}

	public function setHostId($hostId)
	{
		$this->hostId = $hostId;
	}

	public function getMessage()
	{
		return $this->message;
	}

	public function setMessage($message)
	{
		$this->message = $message;
	}
} 