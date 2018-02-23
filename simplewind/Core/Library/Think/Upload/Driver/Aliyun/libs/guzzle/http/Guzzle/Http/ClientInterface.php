<?php
namespace Guzzle\Http;

use Guzzle\Common\HasDispatcherInterface;
use Guzzle\Common\Collection;
use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Message\RequestInterface;

interface ClientInterface extends HasDispatcherInterface
{
	const CREATE_REQUEST = 'client.create_request';
	const HTTP_DATE = 'D, d M Y H:i:s \G\M\T';

	public function setConfig($config);

	public function getConfig($key = false);

	public function createRequest($method = RequestInterface::GET, $uri = null, $headers = null, $body = null, array $options = array());

	public function get($uri = null, $headers = null, $options = array());

	public function head($uri = null, $headers = null, array $options = array());

	public function delete($uri = null, $headers = null, $body = null, array $options = array());

	public function put($uri = null, $headers = null, $body = null, array $options = array());

	public function patch($uri = null, $headers = null, $body = null, array $options = array());

	public function post($uri = null, $headers = null, $postBody = null, array $options = array());

	public function options($uri = null, array $options = array());

	public function send($requests);

	public function getBaseUrl($expand = true);

	public function setBaseUrl($url);

	public function setUserAgent($userAgent, $includeDefault = false);

	public function setSslVerification($certificateAuthority = true, $verifyPeer = true, $verifyHost = 2);
} 