<?php
namespace Aliyun\OSS\Exceptions;

use Aliyun\Common\Exceptions\ServiceException;

class OSSException extends ServiceException
{
	public function __construct($code, $message, $requestId, $hostId)
	{
		parent::__construct($code, $message, $requestId, $hostId);
	}
} 