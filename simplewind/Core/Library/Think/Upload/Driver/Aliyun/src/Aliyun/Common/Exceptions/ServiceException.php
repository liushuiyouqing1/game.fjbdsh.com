<?php
namespace Aliyun\Common\Exceptions;
class ServiceException extends \RuntimeException
{
	protected $requestId;
	protected $hostId;
	protected $errorCode;

	public function __construct($errorCode, $message, $requestId, $hostId)
	{
		parent::__construct($message);
		$this->requestId = $requestId;
		$this->hostId = $hostId;
		$this->errorCode = $errorCode;
	}

	public function getErrorCode()
	{
		return $this->errorCode;
	}

	public function getRequestId()
	{
		return $this->requestId;
	}

	public function getHostId()
	{
		return $this->hostId;
	}
} 