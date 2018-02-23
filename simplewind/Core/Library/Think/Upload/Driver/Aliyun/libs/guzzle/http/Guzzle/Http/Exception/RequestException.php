<?php
namespace Guzzle\Http\Exception;

use Guzzle\Common\Exception\RuntimeException;
use Guzzle\Http\Message\RequestInterface;

class RequestException extends RuntimeException implements HttpException
{
	protected $request;

	public function setRequest(RequestInterface $request)
	{
		$this->request = $request;
		return $this;
	}

	public function getRequest()
	{
		return $this->request;
	}
} 