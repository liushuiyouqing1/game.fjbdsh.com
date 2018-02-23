<?php
namespace Aliyun\Common\Exceptions;
class ClientException extends \RuntimeException
{
	public function __construct($message, \Exception $previous = null)
	{
		parent::__construct($message, 0, $previous);
	}
} 