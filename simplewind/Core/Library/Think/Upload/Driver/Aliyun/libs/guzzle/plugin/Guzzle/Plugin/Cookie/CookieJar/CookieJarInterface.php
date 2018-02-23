<?php
namespace Guzzle\Plugin\Cookie\CookieJar;

use Guzzle\Plugin\Cookie\Cookie;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;

interface CookieJarInterface extends \Countable, \IteratorAggregate
{
	public function remove($domain = null, $path = null, $name = null);

	public function removeTemporary();

	public function removeExpired();

	public function add(Cookie $cookie);

	public function addCookiesFromResponse(Response $response, RequestInterface $request = null);

	public function getMatchingCookies(RequestInterface $request);

	public function all($domain = null, $path = null, $name = null, $skipDiscardable = false, $skipExpired = true);
} 