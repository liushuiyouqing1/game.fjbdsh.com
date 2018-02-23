<?php
namespace Aliyun\Common\Communication;

use Aliyun\Common\Utilities\AssertUtils;
use Aliyun\Common\Utilities\HttpMethods;

class HttpRequest extends HttpMessage
{
	protected $endpoint;
	protected $method = HttpMethods::GET;
	protected $resourcePath;
	protected $parameters = array();
	protected $content;
	protected $originalContentPosition = -1;
	protected $response;
	protected $responseBody;

	public function getEndpoint()
	{
		return $this->endpoint;
	}

	public function setEndpoint($endpoint)
	{
		$urlParameters = parse_url($endpoint);
		if ($urlParameters === false) {
			throw new \InvalidArgumentException('Invalid endpoint: ' . $endpoint . '.');
		}
		if (!isset($urlParameters['scheme'])) {
			throw new \InvalidArgumentException('The scheme of endpoint is not set.');
		}
		if (!isset($urlParameters['host'])) {
			throw new \InvalidArgumentException('The host of endpoint is not set.');
		}
		if ($urlParameters['scheme'] !== 'http' && $urlParameters['scheme'] !== 'https') {
			throw new \InvalidArgumentException('The scheme of endpoint must be http or https');
		}
		$this->endpoint = $urlParameters['scheme'] . '://' . $urlParameters['host'];
	}

	public function getResourcePath()
	{
		return $this->resourcePath;
	}

	public function setResourcePath($resourcePath)
	{
		AssertUtils::assertString($resourcePath, 'resourcePath');
		AssertUtils::assertNotEmpty($resourcePath, 'resourcePath');
		if (substr($resourcePath, 0, 1) != '/') {
			throw new \InvalidArgumentException('Resource path must start with /');
		}
		$this->resourcePath = $resourcePath;
	}

	public function getMethod()
	{
		return $this->method;
	}

	public function setMethod($method)
	{
		$allowMethods = array(HttpMethods::GET, HttpMethods::PUT, HttpMethods::POST, HttpMethods::DELETE, HttpMethods::HEAD,);
		if (!in_array($method, $allowMethods)) {
			throw new \InvalidArgumentException("Http method '{$method}' is not allowed.");
		}
		if (in_array($method, $allowMethods)) {
			$this->method = strtoupper($method);
		}
	}

	public function getParameters()
	{
		return $this->parameters;
	}

	public function addParameter($key, $value)
	{
		AssertUtils::assertString($key, 'HttpParameterName');
		if ($value !== null) {
			AssertUtils::assertString($value, 'HttpParameterValue');
		}
		$this->parameters[$key] = $value;
	}

	public function getFullUrl()
	{
		$fullUrl = $this->endpoint . $this->resourcePath;
		$parameterString = $this->getParameterString();
		if (!empty($parameterString)) {
			$fullUrl .= '?' . $parameterString;
		}
		return $fullUrl;
	}

	public function setResponse($response)
	{
		$this->response = $response;
	}

	public function getResponse()
	{
		return $this->response;
	}

	public function getParameterString()
	{
		$sections = array();
		foreach ($this->parameters as $key => $value) {
			$section = rawurlencode($key);
			if ($value !== null) {
				$section .= '=';
				$section .= rawurlencode($value);
			}
			$sections[] = $section;
		}
		return join('&', $sections);
	}

	public function isParameterInUrl()
	{
		return ($this->content !== null) || ($this->method !== HttpMethods::POST);
	}

	public function getResponseBody()
	{
		return $this->responseBody;
	}

	public function setResponseBody($responseBody)
	{
		$this->responseBody = $responseBody;
	}
} 