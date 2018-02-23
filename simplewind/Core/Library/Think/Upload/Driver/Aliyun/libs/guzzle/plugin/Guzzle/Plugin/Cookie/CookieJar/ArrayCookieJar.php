<?php
namespace Guzzle\Plugin\Cookie\CookieJar;

use Guzzle\Plugin\Cookie\Cookie;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Parser\ParserRegistry;
use Guzzle\Plugin\Cookie\Exception\InvalidCookieException;

class ArrayCookieJar implements CookieJarInterface, \Serializable
{
	protected $cookies = array();
	protected $strictMode;

	public function __construct($strictMode = false)
	{
		$this->strictMode = $strictMode;
	}

	public function setStrictMode($strictMode)
	{
		$this->strictMode = $strictMode;
	}

	public function remove($domain = null, $path = null, $name = null)
	{
		$cookies = $this->all($domain, $path, $name, false, false);
		$this->cookies = array_filter($this->cookies, function (Cookie $cookie) use ($cookies) {
			return !in_array($cookie, $cookies, true);
		});
		return $this;
	}

	public function removeTemporary()
	{
		$this->cookies = array_filter($this->cookies, function (Cookie $cookie) {
			return !$cookie->getDiscard() && $cookie->getExpires();
		});
		return $this;
	}

	public function removeExpired()
	{
		$currentTime = time();
		$this->cookies = array_filter($this->cookies, function (Cookie $cookie) use ($currentTime) {
			return !$cookie->getExpires() || $currentTime < $cookie->getExpires();
		});
		return $this;
	}

	public function all($domain = null, $path = null, $name = null, $skipDiscardable = false, $skipExpired = true)
	{
		return array_values(array_filter($this->cookies, function (Cookie $cookie) use ($domain, $path, $name, $skipDiscardable, $skipExpired) {
			return false === (($name && $cookie->getName() != $name) || ($skipExpired && $cookie->isExpired()) || ($skipDiscardable && ($cookie->getDiscard() || !$cookie->getExpires())) || ($path && !$cookie->matchesPath($path)) || ($domain && !$cookie->matchesDomain($domain)));
		}));
	}

	public function add(Cookie $cookie)
	{
		$result = $cookie->validate();
		if ($result !== true) {
			if ($this->strictMode) {
				throw new InvalidCookieException($result);
			} else {
				return false;
			}
		}
		foreach ($this->cookies as $i => $c) {
			if ($c->getPath() != $cookie->getPath() || $c->getDomain() != $cookie->getDomain() || $c->getPorts() != $cookie->getPorts() || $c->getName() != $cookie->getName()) {
				continue;
			}
			if (!$cookie->getDiscard() && $c->getDiscard()) {
				unset($this->cookies[$i]);
				continue;
			}
			if ($cookie->getExpires() > $c->getExpires()) {
				unset($this->cookies[$i]);
				continue;
			}
			if ($cookie->getValue() !== $c->getValue()) {
				unset($this->cookies[$i]);
				continue;
			}
			return false;
		}
		$this->cookies[] = $cookie;
		return true;
	}

	public function serialize()
	{
		return json_encode(array_map(function (Cookie $cookie) {
			return $cookie->toArray();
		}, $this->all(null, null, null, true, true)));
	}

	public function unserialize($data)
	{
		$data = json_decode($data, true);
		if (empty($data)) {
			$this->cookies = array();
		} else {
			$this->cookies = array_map(function (array $cookie) {
				return new Cookie($cookie);
			}, $data);
		}
	}

	public function count()
	{
		return count($this->cookies);
	}

	public function getIterator()
	{
		return new \ArrayIterator($this->cookies);
	}

	public function addCookiesFromResponse(Response $response, RequestInterface $request = null)
	{
		if ($cookieHeader = $response->getHeader('Set-Cookie')) {
			$parser = ParserRegistry::getInstance()->getParser('cookie');
			foreach ($cookieHeader as $cookie) {
				if ($parsed = $request ? $parser->parseCookie($cookie, $request->getHost(), $request->getPath()) : $parser->parseCookie($cookie)) {
					foreach ($parsed['cookies'] as $key => $value) {
						$row = $parsed;
						$row['name'] = $key;
						$row['value'] = $value;
						unset($row['cookies']);
						$this->add(new Cookie($row));
					}
				}
			}
		}
	}

	public function getMatchingCookies(RequestInterface $request)
	{
		$cookies = $this->all($request->getHost(), $request->getPath());
		foreach ($cookies as $index => $cookie) {
			if (!$cookie->matchesPort($request->getPort()) || ($cookie->getSecure() && $request->getScheme() != 'https')) {
				unset($cookies[$index]);
			}
		};
		return $cookies;
	}
} 