<?php
namespace Guzzle\Http;

use Guzzle\Common\Collection;
use Guzzle\Common\AbstractHasDispatcher;
use Guzzle\Common\Exception\ExceptionCollection;
use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Common\Exception\RuntimeException;
use Guzzle\Common\Version;
use Guzzle\Parser\ParserRegistry;
use Guzzle\Parser\UriTemplate\UriTemplateInterface;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\RequestFactory;
use Guzzle\Http\Message\RequestFactoryInterface;
use Guzzle\Http\Curl\CurlMultiInterface;
use Guzzle\Http\Curl\CurlMultiProxy;
use Guzzle\Http\Curl\CurlHandle;
use Guzzle\Http\Curl\CurlVersion;

class Client extends AbstractHasDispatcher implements ClientInterface
{
	const REQUEST_PARAMS = 'request.params';
	const REQUEST_OPTIONS = 'request.options';
	const CURL_OPTIONS = 'curl.options';
	const SSL_CERT_AUTHORITY = 'ssl.certificate_authority';
	const DISABLE_REDIRECTS = RedirectPlugin::DISABLE;
	protected $defaultHeaders;
	protected $userAgent;
	private $config;
	private $baseUrl;
	private $curlMulti;
	private $uriTemplate;
	protected $requestFactory;

	public static function getAllEvents()
	{
		return array(self::CREATE_REQUEST);
	}

	public function __construct($baseUrl = '', $config = null)
	{
		if (!extension_loaded('curl')) {
			throw new RuntimeException('The PHP cURL extension must be installed to use Guzzle.');
		}
		$this->setConfig($config ?: new Collection());
		$this->initSsl();
		$this->setBaseUrl($baseUrl);
		$this->defaultHeaders = new Collection();
		$this->setRequestFactory(RequestFactory::getInstance());
		$this->userAgent = $this->getDefaultUserAgent();
		if (!$this->config[self::DISABLE_REDIRECTS]) {
			$this->addSubscriber(new RedirectPlugin());
		}
	}

	final public function setConfig($config)
	{
		if ($config instanceof Collection) {
			$this->config = $config;
		} elseif (is_array($config)) {
			$this->config = new Collection($config);
		} else {
			throw new InvalidArgumentException('Config must be an array or Collection');
		}
		return $this;
	}

	final public function getConfig($key = false)
	{
		return $key ? $this->config[$key] : $this->config;
	}

	public function setDefaultOption($keyOrPath, $value)
	{
		$keyOrPath = self::REQUEST_OPTIONS . '/' . $keyOrPath;
		$this->config->setPath($keyOrPath, $value);
		return $this;
	}

	public function getDefaultOption($keyOrPath)
	{
		$keyOrPath = self::REQUEST_OPTIONS . '/' . $keyOrPath;
		return $this->config->getPath($keyOrPath);
	}

	final public function setSslVerification($certificateAuthority = true, $verifyPeer = true, $verifyHost = 2)
	{
		$opts = $this->config[self::CURL_OPTIONS] ?: array();
		if ($certificateAuthority === true) {
			$opts[CURLOPT_CAINFO] = __DIR__ . '/Resources/cacert.pem';
			$opts[CURLOPT_SSL_VERIFYPEER] = true;
			$opts[CURLOPT_SSL_VERIFYHOST] = 2;
		} elseif ($certificateAuthority === false) {
			unset($opts[CURLOPT_CAINFO]);
			$opts[CURLOPT_SSL_VERIFYPEER] = false;
			$opts[CURLOPT_SSL_VERIFYHOST] = 2;
		} elseif ($verifyPeer !== true && $verifyPeer !== false && $verifyPeer !== 1 && $verifyPeer !== 0) {
			throw new InvalidArgumentException('verifyPeer must be 1, 0 or boolean');
		} elseif ($verifyHost !== 0 && $verifyHost !== 1 && $verifyHost !== 2) {
			throw new InvalidArgumentException('verifyHost must be 0, 1 or 2');
		} else {
			$opts[CURLOPT_SSL_VERIFYPEER] = $verifyPeer;
			$opts[CURLOPT_SSL_VERIFYHOST] = $verifyHost;
			if (is_file($certificateAuthority)) {
				unset($opts[CURLOPT_CAPATH]);
				$opts[CURLOPT_CAINFO] = $certificateAuthority;
			} elseif (is_dir($certificateAuthority)) {
				unset($opts[CURLOPT_CAINFO]);
				$opts[CURLOPT_CAPATH] = $certificateAuthority;
			} else {
				throw new RuntimeException('Invalid option passed to ' . self::SSL_CERT_AUTHORITY . ': ' . $certificateAuthority);
			}
		}
		$this->config->set(self::CURL_OPTIONS, $opts);
		return $this;
	}

	public function createRequest($method = 'GET', $uri = null, $headers = null, $body = null, array $options = array())
	{
		if (!$uri) {
			$url = $this->getBaseUrl();
		} else {
			if (!is_array($uri)) {
				$templateVars = null;
			} else {
				list($uri, $templateVars) = $uri;
			}
			if (substr($uri, 0, 4) === 'http') {
				$url = $this->expandTemplate($uri, $templateVars);
			} else {
				$url = Url::factory($this->getBaseUrl())->combine($this->expandTemplate($uri, $templateVars));
			}
		}
		if (count($this->defaultHeaders)) {
			if (!$headers) {
				$headers = $this->defaultHeaders->toArray();
			} elseif (is_array($headers)) {
				$headers += $this->defaultHeaders->toArray();
			} elseif ($headers instanceof Collection) {
				$headers = $headers->toArray() + $this->defaultHeaders->toArray();
			}
		}
		return $this->prepareRequest($this->requestFactory->create($method, (string)$url, $headers, $body), $options);
	}

	public function getBaseUrl($expand = true)
	{
		return $expand ? $this->expandTemplate($this->baseUrl) : $this->baseUrl;
	}

	public function setBaseUrl($url)
	{
		$this->baseUrl = $url;
		return $this;
	}

	public function setUserAgent($userAgent, $includeDefault = false)
	{
		if ($includeDefault) {
			$userAgent .= ' ' . $this->getDefaultUserAgent();
		}
		$this->userAgent = $userAgent;
		return $this;
	}

	public function getDefaultUserAgent()
	{
		return 'Guzzle/' . Version::VERSION . ' curl/' . CurlVersion::getInstance()->get('version') . ' PHP/' . PHP_VERSION;
	}

	public function get($uri = null, $headers = null, $options = array())
	{
		return is_array($options) ? $this->createRequest('GET', $uri, $headers, null, $options) : $this->createRequest('GET', $uri, $headers, $options);
	}

	public function head($uri = null, $headers = null, array $options = array())
	{
		return $this->createRequest('HEAD', $uri, $headers, null, $options);
	}

	public function delete($uri = null, $headers = null, $body = null, array $options = array())
	{
		return $this->createRequest('DELETE', $uri, $headers, $body, $options);
	}

	public function put($uri = null, $headers = null, $body = null, array $options = array())
	{
		return $this->createRequest('PUT', $uri, $headers, $body, $options);
	}

	public function patch($uri = null, $headers = null, $body = null, array $options = array())
	{
		return $this->createRequest('PATCH', $uri, $headers, $body, $options);
	}

	public function post($uri = null, $headers = null, $postBody = null, array $options = array())
	{
		return $this->createRequest('POST', $uri, $headers, $postBody, $options);
	}

	public function options($uri = null, array $options = array())
	{
		return $this->createRequest('OPTIONS', $uri, $options);
	}

	public function send($requests)
	{
		if (!($requests instanceof RequestInterface)) {
			return $this->sendMultiple($requests);
		}
		try {
			$this->getCurlMulti()->add($requests)->send();
			return $requests->getResponse();
		} catch (ExceptionCollection $e) {
			throw $e->getFirst();
		}
	}

	public function setCurlMulti(CurlMultiInterface $curlMulti)
	{
		$this->curlMulti = $curlMulti;
		return $this;
	}

	public function getCurlMulti()
	{
		if (!$this->curlMulti) {
			$this->curlMulti = new CurlMultiProxy();
		}
		return $this->curlMulti;
	}

	public function setRequestFactory(RequestFactoryInterface $factory)
	{
		$this->requestFactory = $factory;
		return $this;
	}

	public function setUriTemplate(UriTemplateInterface $uriTemplate)
	{
		$this->uriTemplate = $uriTemplate;
		return $this;
	}

	public function preparePharCacert($md5Check = true)
	{
		$from = __DIR__ . '/Resources/cacert.pem';
		$certFile = sys_get_temp_dir() . '/guzzle-cacert.pem';
		if (!file_exists($certFile) && !copy($from, $certFile)) {
			throw new RuntimeException("Could not copy {$from} to {$certFile}: " . var_export(error_get_last(), true));
		} elseif ($md5Check) {
			$actualMd5 = md5_file($certFile);
			$expectedMd5 = trim(file_get_contents("{$from}.md5"));
			if ($actualMd5 != $expectedMd5) {
				throw new RuntimeException("{$certFile} MD5 mismatch: expected {$expectedMd5} but got {$actualMd5}");
			}
		}
		return $certFile;
	}

	protected function expandTemplate($template, array $variables = null)
	{
		$expansionVars = $this->getConfig()->toArray();
		if ($variables) {
			$expansionVars = $variables + $expansionVars;
		}
		return $this->getUriTemplate()->expand($template, $expansionVars);
	}

	protected function getUriTemplate()
	{
		if (!$this->uriTemplate) {
			$this->uriTemplate = ParserRegistry::getInstance()->getParser('uri_template');
		}
		return $this->uriTemplate;
	}

	protected function sendMultiple(array $requests)
	{
		$curlMulti = $this->getCurlMulti();
		foreach ($requests as $request) {
			$curlMulti->add($request);
		}
		$curlMulti->send();
		$result = array();
		foreach ($requests as $request) {
			$result[] = $request->getResponse();
		}
		return $result;
	}

	protected function prepareRequest(RequestInterface $request, array $options = array())
	{
		$request->setClient($this)->setEventDispatcher(clone $this->getEventDispatcher());
		if ($curl = $this->config[self::CURL_OPTIONS]) {
			$request->getCurlOptions()->overwriteWith(CurlHandle::parseCurlConfig($curl));
		}
		if ($params = $this->config[self::REQUEST_PARAMS]) {
			Version::warn('request.params is deprecated. Use request.options to add default request options.');
			$request->getParams()->overwriteWith($params);
		}
		if ($this->userAgent && !$request->hasHeader('User-Agent')) {
			$request->setHeader('User-Agent', $this->userAgent);
		}
		if ($defaults = $this->config[self::REQUEST_OPTIONS]) {
			$this->requestFactory->applyOptions($request, $defaults, RequestFactoryInterface::OPTIONS_AS_DEFAULTS);
		}
		if ($options) {
			$this->requestFactory->applyOptions($request, $options);
		}
		$this->dispatch('client.create_request', array('client' => $this, 'request' => $request));
		return $request;
	}

	protected function initSsl()
	{
		if ('system' == ($authority = $this->config[self::SSL_CERT_AUTHORITY])) {
			return;
		}
		if ($authority === null) {
			$authority = true;
		}
		if ($authority === true && substr(__FILE__, 0, 7) == 'phar://') {
			$authority = $this->preparePharCacert();
			$that = $this;
			$this->getEventDispatcher()->addListener('request.before_send', function ($event) use ($authority, $that) {
				if ($authority == $event['request']->getCurlOptions()->get(CURLOPT_CAINFO)) {
					$that->preparePharCacert(false);
				}
			});
		}
		$this->setSslVerification($authority);
	}

	public function getDefaultHeaders()
	{
		Version::warn(__METHOD__ . ' is deprecated. Use the request.options array to retrieve default request options');
		return $this->defaultHeaders;
	}

	public function setDefaultHeaders($headers)
	{
		Version::warn(__METHOD__ . ' is deprecated. Use the request.options array to specify default request options');
		if ($headers instanceof Collection) {
			$this->defaultHeaders = $headers;
		} elseif (is_array($headers)) {
			$this->defaultHeaders = new Collection($headers);
		} else {
			throw new InvalidArgumentException('Headers must be an array or Collection');
		}
		return $this;
	}
} 