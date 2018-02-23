<?php
namespace Guzzle\Http\Message;

use Guzzle\Common\Collection;
use Guzzle\Common\HasDispatcherInterface;
use Guzzle\Http\Exception\RequestException;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\EntityBodyInterface;
use Guzzle\Http\Url;
use Guzzle\Http\QueryString;

interface RequestInterface extends MessageInterface, HasDispatcherInterface
{
	const STATE_NEW = 'new';
	const STATE_COMPLETE = 'complete';
	const STATE_TRANSFER = 'transfer';
	const STATE_ERROR = 'error';
	const GET = 'GET';
	const PUT = 'PUT';
	const POST = 'POST';
	const DELETE = 'DELETE';
	const HEAD = 'HEAD';
	const CONNECT = 'CONNECT';
	const OPTIONS = 'OPTIONS';
	const TRACE = 'TRACE';
	const PATCH = 'PATCH';

	public function __toString();

	public function send();

	public function setClient(ClientInterface $client);

	public function getClient();

	public function setUrl($url);

	public function getUrl($asObject = false);

	public function getResource();

	public function getQuery();

	public function getMethod();

	public function getScheme();

	public function setScheme($scheme);

	public function getHost();

	public function setHost($host);

	public function getPath();

	public function setPath($path);

	public function getPort();

	public function setPort($port);

	public function getUsername();

	public function getPassword();

	public function setAuth($user, $password = '', $scheme = 'Basic');

	public function getProtocolVersion();

	public function setProtocolVersion($protocol);

	public function getResponse();

	public function setResponse(Response $response, $queued = false);

	public function startResponse(Response $response);

	public function setResponseBody($body);

	public function getResponseBody();

	public function getState();

	public function setState($state, array $context = array());

	public function getCurlOptions();

	public function getCookies();

	public function getCookie($name);

	public function addCookie($name, $value);

	public function removeCookie($name);
} 