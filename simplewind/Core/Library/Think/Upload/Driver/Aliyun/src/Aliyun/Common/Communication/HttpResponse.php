<?php
namespace Aliyun\Common\Communication;

use Aliyun\Common\Utilities\AssertUtils;
use Aliyun\Common\Utilities\HttpHeaders;

class HttpResponse extends HttpMessage
{
	private $request;
	private $uri;
	private $statusCode;

	public function __construct(HttpRequest $request)
	{
		$this->request = $request;
	}

	public function getRequest()
	{
		return $this->request;
	}

	public function getUri()
	{
		return $this->uri;
	}

	public function setUri($uri)
	{
		$this->uri = $uri;
	}

	public function getContentLength()
	{
		if (isset($this->headers[HttpHeaders::CONTENT_LENGTH])) {
			return (int)$this->headers[HttpHeaders::CONTENT_LENGTH];
		}
		return null;
	}

	public function getStatusCode()
	{
		return $this->statusCode;
	}

	public function setStatusCode($statusCode)
	{
		$this->statusCode = $statusCode;
	}

	public function addHeader($key, $value)
	{
		AssertUtils::assertString($key, 'HttpHeaderName');
		AssertUtils::assertString($value, 'HttpHeaderValue');
		$this->headers[$key] = $value;
	}

	public function isSuccess()
	{
		return ($this->statusCode >= 200 && $this->statusCode < 300) || $this->statusCode == 304;
	}
} 