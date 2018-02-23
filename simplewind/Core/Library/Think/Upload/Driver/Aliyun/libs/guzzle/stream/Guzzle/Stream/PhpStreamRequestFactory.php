<?php
namespace Guzzle\Stream;

use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Common\Exception\RuntimeException;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Url;

class PhpStreamRequestFactory implements StreamRequestFactoryInterface
{
	protected $context;
	protected $contextOptions;
	protected $url;
	protected $lastResponseHeaders;

	public function fromRequest(RequestInterface $request, $context = array(), array $params = array())
	{
		if (is_resource($context)) {
			$this->contextOptions = stream_context_get_options($context);
			$this->context = $context;
		} elseif (is_array($context) || !$context) {
			$this->contextOptions = $context;
			$this->createContext($params);
		} elseif ($context) {
			throw new InvalidArgumentException('$context must be an array or resource');
		}
		$request->dispatch('request.before_send', array('request' => $request, 'context' => $this->context, 'context_options' => $this->contextOptions));
		$this->setUrl($request);
		$this->addDefaultContextOptions($request);
		$this->addSslOptions($request);
		$this->addBodyOptions($request);
		$this->addProxyOptions($request);
		return $this->createStream($params)->setCustomData('request', $request)->setCustomData('response_headers', $this->getLastResponseHeaders());
	}

	protected function setContextValue($wrapper, $name, $value, $overwrite = false)
	{
		if (!isset($this->contextOptions[$wrapper])) {
			$this->contextOptions[$wrapper] = array($name => $value);
		} elseif (!$overwrite && isset($this->contextOptions[$wrapper][$name])) {
			return;
		}
		$this->contextOptions[$wrapper][$name] = $value;
		stream_context_set_option($this->context, $wrapper, $name, $value);
	}

	protected function createContext(array $params)
	{
		$options = $this->contextOptions;
		$this->context = $this->createResource(function () use ($params, $options) {
			return stream_context_create($options, $params);
		});
	}

	public function getLastResponseHeaders()
	{
		return $this->lastResponseHeaders;
	}

	protected function addDefaultContextOptions(RequestInterface $request)
	{
		$this->setContextValue('http', 'method', $request->getMethod());
		$this->setContextValue('http', 'header', $request->getHeaderLines());
		$this->setContextValue('http', 'protocol_version', '1.0');
		$this->setContextValue('http', 'ignore_errors', true);
	}

	protected function setUrl(RequestInterface $request)
	{
		$this->url = $request->getUrl(true);
		if ($request->getUsername()) {
			$this->url->setUsername($request->getUsername());
		}
		if ($request->getPassword()) {
			$this->url->setPassword($request->getPassword());
		}
	}

	protected function addSslOptions(RequestInterface $request)
	{
		if ($verify = $request->getCurlOptions()->get(CURLOPT_SSL_VERIFYPEER)) {
			$this->setContextValue('ssl', 'verify_peer', true, true);
			if ($cafile = $request->getCurlOptions()->get(CURLOPT_CAINFO)) {
				$this->setContextValue('ssl', 'cafile', $cafile, true);
			}
		} else {
			$this->setContextValue('ssl', 'verify_peer', false, true);
		}
	}

	protected function addBodyOptions(RequestInterface $request)
	{
		if (!($request instanceof EntityEnclosingRequestInterface)) {
			return;
		}
		if (count($request->getPostFields())) {
			$this->setContextValue('http', 'content', (string)$request->getPostFields(), true);
		} elseif ($request->getBody()) {
			$this->setContextValue('http', 'content', (string)$request->getBody(), true);
		}
		if (isset($this->contextOptions['http']['content'])) {
			$headers = isset($this->contextOptions['http']['header']) ? $this->contextOptions['http']['header'] : array();
			$headers[] = 'Content-Length: ' . strlen($this->contextOptions['http']['content']);
			$this->setContextValue('http', 'header', $headers, true);
		}
	}

	protected function addProxyOptions(RequestInterface $request)
	{
		if ($proxy = $request->getCurlOptions()->get(CURLOPT_PROXY)) {
			$this->setContextValue('http', 'proxy', $proxy);
		}
	}

	protected function createStream(array $params)
	{
		$http_response_header = null;
		$url = $this->url;
		$context = $this->context;
		$fp = $this->createResource(function () use ($context, $url, &$http_response_header) {
			return fopen((string)$url, 'r', false, $context);
		});
		$className = isset($params['stream_class']) ? $params['stream_class'] : __NAMESPACE__ . '\\Stream';
		$stream = new $className($fp);
		if (isset($http_response_header)) {
			$this->lastResponseHeaders = $http_response_header;
			$this->processResponseHeaders($stream);
		}
		return $stream;
	}

	protected function processResponseHeaders(StreamInterface $stream)
	{
		foreach ($this->lastResponseHeaders as $header) {
			if (($pos = stripos($header, 'Content-Length:')) === 0) {
				$stream->setSize(trim(substr($header, 15)));
			}
		}
	}

	protected function createResource($callback)
	{
		$level = error_reporting(0);
		$resource = call_user_func($callback);
		error_reporting($level);
		if (false === $resource) {
			$message = 'Error creating resource. ';
			foreach (error_get_last() as $key => $value) {
				$message .= "[{$key}] {$value} ";
			}
			throw new RuntimeException(trim($message));
		}
		return $resource;
	}
} 