<?php
namespace Guzzle\Http\Curl;

use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Common\Exception\RuntimeException;
use Guzzle\Common\Collection;
use Guzzle\Http\Message\EntityEnclosingRequest;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Parser\ParserRegistry;
use Guzzle\Http\Url;

class CurlHandle
{
	const BODY_AS_STRING = 'body_as_string';
	const PROGRESS = 'progress';
	const DEBUG = 'debug';
	protected $options;
	protected $handle;
	protected $errorNo = CURLE_OK;

	public static function factory(RequestInterface $request)
	{
		$requestCurlOptions = $request->getCurlOptions();
		$mediator = new RequestMediator($request, $requestCurlOptions->get('emit_io'));
		$tempContentLength = null;
		$method = $request->getMethod();
		$bodyAsString = $requestCurlOptions->get(self::BODY_AS_STRING);
		$curlOptions = array(CURLOPT_URL => $request->getUrl(), CURLOPT_CONNECTTIMEOUT => 150, CURLOPT_RETURNTRANSFER => false, CURLOPT_HEADER => false, CURLOPT_PORT => $request->getPort(), CURLOPT_HTTPHEADER => array(), CURLOPT_WRITEFUNCTION => array($mediator, 'writeResponseBody'), CURLOPT_HEADERFUNCTION => array($mediator, 'receiveResponseHeader'), CURLOPT_HTTP_VERSION => $request->getProtocolVersion() === '1.0' ? CURL_HTTP_VERSION_1_0 : CURL_HTTP_VERSION_1_1, CURLOPT_SSL_VERIFYPEER => 1, CURLOPT_SSL_VERIFYHOST => 2);
		if (defined('CURLOPT_PROTOCOLS')) {
			$curlOptions[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
		}
		if ($acceptEncodingHeader = $request->getHeader('Accept-Encoding')) {
			$curlOptions[CURLOPT_ENCODING] = (string)$acceptEncodingHeader;
			$request->removeHeader('Accept-Encoding');
		}
		if ($requestCurlOptions->get('debug')) {
			$curlOptions[CURLOPT_STDERR] = fopen('php://temp', 'r+');
			if (false === $curlOptions[CURLOPT_STDERR]) {
				throw new RuntimeException('Unable to create a stream for CURLOPT_STDERR');
			}
			$curlOptions[CURLOPT_VERBOSE] = true;
		}
		if ($method == 'GET') {
			$curlOptions[CURLOPT_HTTPGET] = true;
		} elseif ($method == 'HEAD') {
			$curlOptions[CURLOPT_NOBODY] = true;
			unset($curlOptions[CURLOPT_WRITEFUNCTION]);
		} elseif (!($request instanceof EntityEnclosingRequest)) {
			$curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
		} else {
			$curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
			if ($request->getBody()) {
				if ($bodyAsString) {
					$curlOptions[CURLOPT_POSTFIELDS] = (string)$request->getBody();
					if ($tempContentLength = $request->getHeader('Content-Length')) {
						$tempContentLength = (int)(string)$tempContentLength;
					}
					if (!$request->hasHeader('Content-Type')) {
						$curlOptions[CURLOPT_HTTPHEADER][] = 'Content-Type:';
					}
				} else {
					$curlOptions[CURLOPT_UPLOAD] = true;
					if ($tempContentLength = $request->getHeader('Content-Length')) {
						$tempContentLength = (int)(string)$tempContentLength;
						$curlOptions[CURLOPT_INFILESIZE] = $tempContentLength;
					}
					$curlOptions[CURLOPT_READFUNCTION] = array($mediator, 'readRequestBody');
					$request->getBody()->seek(0);
				}
			} else {
				$postFields = false;
				if (count($request->getPostFiles())) {
					$postFields = $request->getPostFields()->useUrlEncoding(false)->urlEncode();
					foreach ($request->getPostFiles() as $key => $data) {
						$prefixKeys = count($data) > 1;
						foreach ($data as $index => $file) {
							$fieldKey = $prefixKeys ? "{$key}[{$index}]" : $key;
							$postFields[$fieldKey] = $file->getCurlValue();
						}
					}
				} elseif (count($request->getPostFields())) {
					$postFields = (string)$request->getPostFields()->useUrlEncoding(true);
				}
				if ($postFields !== false) {
					if ($method == 'POST') {
						unset($curlOptions[CURLOPT_CUSTOMREQUEST]);
						$curlOptions[CURLOPT_POST] = true;
					}
					$curlOptions[CURLOPT_POSTFIELDS] = $postFields;
					$request->removeHeader('Content-Length');
				}
			}
			if (!$request->hasHeader('Expect')) {
				$curlOptions[CURLOPT_HTTPHEADER][] = 'Expect:';
			}
		}
		if (null !== $tempContentLength) {
			$request->removeHeader('Content-Length');
		}
		foreach ($requestCurlOptions->toArray() as $key => $value) {
			if (is_numeric($key)) {
				$curlOptions[$key] = $value;
			}
		}
		if (!isset($curlOptions[CURLOPT_ENCODING])) {
			$curlOptions[CURLOPT_HTTPHEADER][] = 'Accept:';
		}
		foreach ($request->getHeaderLines() as $line) {
			$curlOptions[CURLOPT_HTTPHEADER][] = $line;
		}
		if ($tempContentLength) {
			$request->setHeader('Content-Length', $tempContentLength);
		}
		$handle = curl_init();
		if ($requestCurlOptions->get('progress')) {
			$curlOptions[CURLOPT_PROGRESSFUNCTION] = function () use ($mediator, $handle) {
				$args = func_get_args();
				$args[] = $handle;
				call_user_func_array(array($mediator, 'progress'), $args);
			};
			$curlOptions[CURLOPT_NOPROGRESS] = false;
		}
		curl_setopt_array($handle, $curlOptions);
		return new static($handle, $curlOptions);
	}

	public function __construct($handle, $options)
	{
		if (!is_resource($handle)) {
			throw new InvalidArgumentException('Invalid handle provided');
		}
		if (is_array($options)) {
			$this->options = new Collection($options);
		} elseif ($options instanceof Collection) {
			$this->options = $options;
		} else {
			throw new InvalidArgumentException('Expected array or Collection');
		}
		$this->handle = $handle;
	}

	public function __destruct()
	{
		$this->close();
	}

	public function close()
	{
		if (is_resource($this->handle)) {
			curl_close($this->handle);
		}
		$this->handle = null;
	}

	public function isAvailable()
	{
		return is_resource($this->handle);
	}

	public function getError()
	{
		return $this->isAvailable() ? curl_error($this->handle) : '';
	}

	public function getErrorNo()
	{
		if ($this->errorNo) {
			return $this->errorNo;
		}
		return $this->isAvailable() ? curl_errno($this->handle) : CURLE_OK;
	}

	public function setErrorNo($error)
	{
		$this->errorNo = $error;
		return $this;
	}

	public function getInfo($option = null)
	{
		if (!is_resource($this->handle)) {
			return null;
		}
		if (null !== $option) {
			return curl_getinfo($this->handle, $option) ?: null;
		}
		return curl_getinfo($this->handle) ?: array();
	}

	public function getStderr($asResource = false)
	{
		$stderr = $this->getOptions()->get(CURLOPT_STDERR);
		if (!$stderr) {
			return null;
		}
		if ($asResource) {
			return $stderr;
		}
		fseek($stderr, 0);
		$e = stream_get_contents($stderr);
		fseek($stderr, 0, SEEK_END);
		return $e;
	}

	public function getUrl()
	{
		return Url::factory($this->options->get(CURLOPT_URL));
	}

	public function getHandle()
	{
		return $this->isAvailable() ? $this->handle : null;
	}

	public function getOptions()
	{
		return $this->options;
	}

	public function updateRequestFromTransfer(RequestInterface $request)
	{
		if (!$request->getResponse()) {
			return;
		}
		$request->getResponse()->setInfo($this->getInfo());
		if (!$log = $this->getStderr(true)) {
			return;
		}
		$headers = '';
		fseek($log, 0);
		while (($line = fgets($log)) !== false) {
			if ($line && $line[0] == '>') {
				$headers = substr(trim($line), 2) . "\r\n";
				while (($line = fgets($log)) !== false) {
					if ($line[0] == '*' || $line[0] == '<') {
						break;
					} else {
						$headers .= trim($line) . "\r\n";
					}
				}
			}
		}
		if ($headers) {
			$parsed = ParserRegistry::getInstance()->getParser('message')->parseRequest($headers);
			if (!empty($parsed['headers'])) {
				$request->setHeaders(array());
				foreach ($parsed['headers'] as $name => $value) {
					$request->setHeader($name, $value);
				}
			}
			if (!empty($parsed['version'])) {
				$request->setProtocolVersion($parsed['version']);
			}
		}
	}

	public static function parseCurlConfig($config)
	{
		$curlOptions = array();
		foreach ($config as $key => $value) {
			if (is_string($key) && defined($key)) {
				$key = constant($key);
			}
			if (is_string($value) && defined($value)) {
				$value = constant($value);
			}
			$curlOptions[$key] = $value;
		}
		return $curlOptions;
	}
} 