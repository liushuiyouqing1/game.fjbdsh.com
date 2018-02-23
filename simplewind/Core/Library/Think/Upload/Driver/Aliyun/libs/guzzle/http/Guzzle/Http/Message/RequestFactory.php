<?php
namespace Guzzle\Http\Message;

use Guzzle\Common\Collection;
use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Http\RedirectPlugin;
use Guzzle\Http\Url;
use Guzzle\Parser\ParserRegistry;
use Guzzle\Plugin\Log\LogPlugin;

class RequestFactory implements RequestFactoryInterface
{
	protected static $instance;
	protected $methods;
	protected $requestClass = 'Guzzle\\Http\\Message\\Request';
	protected $entityEnclosingRequestClass = 'Guzzle\\Http\\Message\\EntityEnclosingRequest';

	public static function getInstance()
	{
		if (!static::$instance) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	public function __construct()
	{
		$this->methods = array_flip(get_class_methods(__CLASS__));
	}

	public function fromMessage($message)
	{
		$parsed = ParserRegistry::getInstance()->getParser('message')->parseRequest($message);
		if (!$parsed) {
			return false;
		}
		$request = $this->fromParts($parsed['method'], $parsed['request_url'], $parsed['headers'], $parsed['body'], $parsed['protocol'], $parsed['version']);
		if (!isset($parsed['headers']['Expect']) && !isset($parsed['headers']['expect'])) {
			$request->removeHeader('Expect');
		}
		return $request;
	}

	public function fromParts($method, array $urlParts, $headers = null, $body = null, $protocol = 'HTTP', $protocolVersion = '1.1')
	{
		return $this->create($method, Url::buildUrl($urlParts), $headers, $body)->setProtocolVersion($protocolVersion);
	}

	public function create($method, $url, $headers = null, $body = null, array $options = array())
	{
		$method = strtoupper($method);
		if ($method == 'GET' || $method == 'HEAD' || $method == 'TRACE' || $method == 'OPTIONS') {
			$request = new $this->requestClass($method, $url, $headers);
			if ($body) {
				$type = gettype($body);
				if ($type == 'string' || $type == 'resource' || $type == 'object') {
					$request->setResponseBody($body);
				}
			}
		} else {
			$request = new $this->entityEnclosingRequestClass($method, $url, $headers);
			if ($body) {
				if (is_array($body) || $body instanceof Collection) {
					foreach ($body as $key => $value) {
						if (is_string($value) && substr($value, 0, 1) == '@') {
							$request->addPostFile($key, $value);
							unset($body[$key]);
						}
					}
					$request->addPostFields($body);
				} else {
					$request->setBody($body, (string)$request->getHeader('Content-Type'));
					if ((string)$request->getHeader('Transfer-Encoding') == 'chunked') {
						$request->removeHeader('Content-Length');
					}
				}
			}
		}
		if ($options) {
			$this->applyOptions($request, $options);
		}
		return $request;
	}

	public function cloneRequestWithMethod(RequestInterface $request, $method)
	{
		if ($client = $request->getClient()) {
			$cloned = $request->getClient()->createRequest($method, $request->getUrl(), $request->getHeaders());
		} else {
			$cloned = $this->create($method, $request->getUrl(), $request->getHeaders());
		}
		$cloned->getCurlOptions()->replace($request->getCurlOptions()->toArray());
		$cloned->setEventDispatcher(clone $request->getEventDispatcher());
		if (!($cloned instanceof EntityEnclosingRequestInterface)) {
			$cloned->removeHeader('Content-Length');
		} elseif ($request instanceof EntityEnclosingRequestInterface) {
			$cloned->setBody($request->getBody());
		}
		$cloned->getParams()->replace($request->getParams()->toArray());
		$cloned->dispatch('request.clone', array('request' => $cloned));
		return $cloned;
	}

	public function applyOptions(RequestInterface $request, array $options = array(), $flags = self::OPTIONS_NONE)
	{
		foreach ($options as $key => $value) {
			$method = "visit_{$key}";
			if (isset($this->methods[$method])) {
				$this->{$method}($request, $value, $flags);
			}
		}
	}

	protected function visit_headers(RequestInterface $request, $value, $flags)
	{
		if (!is_array($value)) {
			throw new InvalidArgumentException('headers value must be an array');
		}
		if ($flags & self::OPTIONS_AS_DEFAULTS) {
			foreach ($value as $key => $header) {
				if (!$request->hasHeader($key)) {
					$request->setHeader($key, $header);
				}
			}
		} else {
			$request->addHeaders($value);
		}
	}

	protected function visit_body(RequestInterface $request, $value, $flags)
	{
		if ($request instanceof EntityEnclosingRequestInterface) {
			$request->setBody($value);
		} else {
			throw new InvalidArgumentException('Attempting to set a body on a non-entity-enclosing request');
		}
	}

	protected function visit_allow_redirects(RequestInterface $request, $value, $flags)
	{
		if ($value === false) {
			$request->getParams()->set(RedirectPlugin::DISABLE, true);
		}
	}

	protected function visit_auth(RequestInterface $request, $value, $flags)
	{
		if (!is_array($value)) {
			throw new InvalidArgumentException('auth value must be an array');
		}
		$request->setAuth($value[0], isset($value[1]) ? $value[1] : null, isset($value[2]) ? $value[2] : 'basic');
	}

	protected function visit_query(RequestInterface $request, $value, $flags)
	{
		if (!is_array($value)) {
			throw new InvalidArgumentException('query value must be an array');
		}
		if ($flags & self::OPTIONS_AS_DEFAULTS) {
			$query = $request->getQuery();
			$query->overwriteWith(array_diff_key($value, $query->toArray()));
		} else {
			$request->getQuery()->overwriteWith($value);
		}
	}

	protected function visit_cookies(RequestInterface $request, $value, $flags)
	{
		if (!is_array($value)) {
			throw new InvalidArgumentException('cookies value must be an array');
		}
		foreach ($value as $name => $v) {
			$request->addCookie($name, $v);
		}
	}

	protected function visit_events(RequestInterface $request, $value, $flags)
	{
		if (!is_array($value)) {
			throw new InvalidArgumentException('events value must be an array');
		}
		foreach ($value as $name => $method) {
			if (is_array($method)) {
				$request->getEventDispatcher()->addListener($name, $method[0], $method[1]);
			} else {
				$request->getEventDispatcher()->addListener($name, $method);
			}
		}
	}

	protected function visit_plugins(RequestInterface $request, $value, $flags)
	{
		if (!is_array($value)) {
			throw new InvalidArgumentException('plugins value must be an array');
		}
		foreach ($value as $plugin) {
			$request->addSubscriber($plugin);
		}
	}

	protected function visit_exceptions(RequestInterface $request, $value, $flags)
	{
		if ($value === false || $value === 0) {
			$dispatcher = $request->getEventDispatcher();
			foreach ($dispatcher->getListeners('request.error') as $listener) {
				if ($listener[0] == 'Guzzle\Http\Message\Request' && $listener[1] = 'onRequestError') {
					$dispatcher->removeListener('request.error', $listener);
					break;
				}
			}
		}
	}

	protected function visit_save_to(RequestInterface $request, $value, $flags)
	{
		$request->setResponseBody($value);
	}

	protected function visit_params(RequestInterface $request, $value, $flags)
	{
		if (!is_array($value)) {
			throw new InvalidArgumentException('params value must be an array');
		}
		$request->getParams()->overwriteWith($value);
	}

	protected function visit_timeout(RequestInterface $request, $value, $flags)
	{
		$request->getCurlOptions()->set(CURLOPT_TIMEOUT_MS, $value * 1000);
	}

	protected function visit_connect_timeout(RequestInterface $request, $value, $flags)
	{
		$request->getCurlOptions()->set(CURLOPT_CONNECTTIMEOUT_MS, $value * 1000);
	}

	protected function visit_debug(RequestInterface $request, $value, $flags)
	{
		if (class_exists('Guzzle\Plugin\Log\LogPlugin')) {
			$request->addSubscriber(LogPlugin::getDebugPlugin());
		} else {
			$request->getCurlOptions()->set(CURLOPT_VERBOSE, true);
		}
	}

	protected function visit_verify(RequestInterface $request, $value, $flags)
	{
		$curl = $request->getCurlOptions();
		if ($value === true || is_string($value)) {
			$curl[CURLOPT_SSL_VERIFYHOST] = 2;
			$curl[CURLOPT_SSL_VERIFYPEER] = true;
			if ($value !== true) {
				$curl[CURLOPT_CAINFO] = $value;
			}
		} elseif ($value === false) {
			unset($curl[CURLOPT_CAINFO]);
			$curl[CURLOPT_SSL_VERIFYHOST] = 0;
			$curl[CURLOPT_SSL_VERIFYPEER] = false;
		}
	}

	protected function visit_proxy(RequestInterface $request, $value, $flags)
	{
		$request->getCurlOptions()->set(CURLOPT_PROXY, $value, $flags);
	}

	protected function visit_cert(RequestInterface $request, $value, $flags)
	{
		if (is_array($value)) {
			$request->getCurlOptions()->set(CURLOPT_SSLCERT, $value[0]);
			$request->getCurlOptions()->set(CURLOPT_SSLCERTPASSWD, $value[1]);
		} else {
			$request->getCurlOptions()->set(CURLOPT_SSLCERT, $value);
		}
	}

	protected function visit_ssl_key(RequestInterface $request, $value, $flags)
	{
		if (is_array($value)) {
			$request->getCurlOptions()->set(CURLOPT_SSLKEY, $value[0]);
			$request->getCurlOptions()->set(CURLOPT_SSLKEYPASSWD, $value[1]);
		} else {
			$request->getCurlOptions()->set(CURLOPT_SSLKEY, $value);
		}
	}
} 