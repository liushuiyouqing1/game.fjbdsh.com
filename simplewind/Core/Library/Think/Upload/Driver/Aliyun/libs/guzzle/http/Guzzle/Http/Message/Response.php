<?php
namespace Guzzle\Http\Message;

use Guzzle\Common\Version;
use Guzzle\Common\ToArrayInterface;
use Guzzle\Common\Exception\RuntimeException;
use Guzzle\Http\EntityBodyInterface;
use Guzzle\Http\EntityBody;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\RedirectPlugin;
use Guzzle\Parser\ParserRegistry;

class Response extends AbstractMessage implements \Serializable
{
	private static $statusTexts = array(100 => 'Continue', 101 => 'Switching Protocols', 102 => 'Processing', 200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 207 => 'Multi-Status', 208 => 'Already Reported', 226 => 'IM Used', 300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 307 => 'Temporary Redirect', 308 => 'Permanent Redirect', 400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Timeout', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Long', 415 => 'Unsupported Media Type', 416 => 'Requested Range Not Satisfiable', 417 => 'Expectation Failed', 422 => 'Unprocessable Entity', 423 => 'Locked', 424 => 'Failed Dependency', 425 => 'Reserved for WebDAV advanced collections expired proposal', 426 => 'Upgrade required', 428 => 'Precondition Required', 429 => 'Too Many Requests', 431 => 'Request Header Fields Too Large', 500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported', 506 => 'Variant Also Negotiates (Experimental)', 507 => 'Insufficient Storage', 508 => 'Loop Detected', 510 => 'Not Extended', 511 => 'Network Authentication Required',);
	protected $body;
	protected $reasonPhrase;
	protected $statusCode;
	protected $info = array();
	protected $effectiveUrl;
	protected static $cacheResponseCodes = array(200, 203, 206, 300, 301, 410);

	public static function fromMessage($message)
	{
		$data = ParserRegistry::getInstance()->getParser('message')->parseResponse($message);
		if (!$data) {
			return false;
		}
		$response = new static($data['code'], $data['headers'], $data['body']);
		$response->setProtocol($data['protocol'], $data['version'])->setStatus($data['code'], $data['reason_phrase']);
		$contentLength = (string)$response->getHeader('Content-Length');
		$actualLength = strlen($data['body']);
		if (strlen($data['body']) > 0 && $contentLength != $actualLength) {
			$response->setHeader('Content-Length', $actualLength);
		}
		return $response;
	}

	public function __construct($statusCode, $headers = null, $body = null)
	{
		parent::__construct();
		$this->setStatus($statusCode);
		$this->body = EntityBody::factory($body !== null ? $body : '');
		if ($headers) {
			if (is_array($headers)) {
				$this->setHeaders($headers);
			} elseif ($headers instanceof ToArrayInterface) {
				$this->setHeaders($headers->toArray());
			} else {
				throw new BadResponseException('Invalid headers argument received');
			}
		}
	}

	public function __toString()
	{
		return $this->getMessage();
	}

	public function serialize()
	{
		return json_encode(array('status' => $this->statusCode, 'body' => (string)$this->body, 'headers' => $this->headers->toArray()));
	}

	public function unserialize($serialize)
	{
		$data = json_decode($serialize, true);
		$this->__construct($data['status'], $data['headers'], $data['body']);
	}

	public function getBody($asString = false)
	{
		return $asString ? (string)$this->body : $this->body;
	}

	public function setBody($body)
	{
		$this->body = EntityBody::factory($body);
		return $this;
	}

	public function setProtocol($protocol, $version)
	{
		$this->protocol = $protocol;
		$this->protocolVersion = $version;
		return $this;
	}

	public function getProtocol()
	{
		return $this->protocol;
	}

	public function getProtocolVersion()
	{
		return $this->protocolVersion;
	}

	public function getInfo($key = null)
	{
		if ($key === null) {
			return $this->info;
		} elseif (array_key_exists($key, $this->info)) {
			return $this->info[$key];
		} else {
			return null;
		}
	}

	public function setInfo(array $info)
	{
		$this->info = $info;
		return $this;
	}

	public function setStatus($statusCode, $reasonPhrase = '')
	{
		$this->statusCode = (int)$statusCode;
		if (!$reasonPhrase && isset(self::$statusTexts[$this->statusCode])) {
			$this->reasonPhrase = self::$statusTexts[$this->statusCode];
		} else {
			$this->reasonPhrase = $reasonPhrase;
		}
		return $this;
	}

	public function getStatusCode()
	{
		return $this->statusCode;
	}

	public function getMessage()
	{
		$message = $this->getRawHeaders();
		$size = $this->body->getSize();
		if ($size < 2097152) {
			$message .= (string)$this->body;
		}
		return $message;
	}

	public function getRawHeaders()
	{
		$headers = 'HTTP/1.1 ' . $this->statusCode . ' ' . $this->reasonPhrase . "\r\n";
		$lines = $this->getHeaderLines();
		if (!empty($lines)) {
			$headers .= implode("\r\n", $lines) . "\r\n";
		}
		return $headers . "\r\n";
	}

	public function getReasonPhrase()
	{
		return $this->reasonPhrase;
	}

	public function getAcceptRanges()
	{
		return (string)$this->getHeader('Accept-Ranges');
	}

	public function calculateAge()
	{
		$age = $this->getHeader('Age');
		if ($age === null && $this->getDate()) {
			$age = time() - strtotime($this->getDate());
		}
		return $age === null ? null : (int)(string)$age;
	}

	public function getAge()
	{
		return (string)$this->getHeader('Age');
	}

	public function getAllow()
	{
		return (string)$this->getHeader('Allow');
	}

	public function isMethodAllowed($method)
	{
		$allow = $this->getHeader('Allow');
		if ($allow) {
			foreach (explode(',', $allow) as $allowable) {
				if (!strcasecmp(trim($allowable), $method)) {
					return true;
				}
			}
		}
		return false;
	}

	public function getCacheControl()
	{
		return (string)$this->getHeader('Cache-Control');
	}

	public function getConnection()
	{
		return (string)$this->getHeader('Connection');
	}

	public function getContentEncoding()
	{
		return (string)$this->getHeader('Content-Encoding');
	}

	public function getContentLanguage()
	{
		return (string)$this->getHeader('Content-Language');
	}

	public function getContentLength()
	{
		return (int)(string)$this->getHeader('Content-Length');
	}

	public function getContentLocation()
	{
		return (string)$this->getHeader('Content-Location');
	}

	public function getContentDisposition()
	{
		return (string)$this->getHeader('Content-Disposition');
	}

	public function getContentMd5()
	{
		return (string)$this->getHeader('Content-MD5');
	}

	public function getContentRange()
	{
		return (string)$this->getHeader('Content-Range');
	}

	public function getContentType()
	{
		return (string)$this->getHeader('Content-Type');
	}

	public function isContentType($type)
	{
		return stripos($this->getHeader('Content-Type'), $type) !== false;
	}

	public function getDate()
	{
		return (string)$this->getHeader('Date');
	}

	public function getEtag()
	{
		return (string)$this->getHeader('ETag');
	}

	public function getExpires()
	{
		return (string)$this->getHeader('Expires');
	}

	public function getLastModified()
	{
		return (string)$this->getHeader('Last-Modified');
	}

	public function getLocation()
	{
		return (string)$this->getHeader('Location');
	}

	public function getPragma()
	{
		return (string)$this->getHeader('Pragma');
	}

	public function getProxyAuthenticate()
	{
		return (string)$this->getHeader('Proxy-Authenticate');
	}

	public function getRetryAfter()
	{
		return (string)$this->getHeader('Retry-After');
	}

	public function getServer()
	{
		return (string)$this->getHeader('Server');
	}

	public function getSetCookie()
	{
		return (string)$this->getHeader('Set-Cookie');
	}

	public function getTrailer()
	{
		return (string)$this->getHeader('Trailer');
	}

	public function getTransferEncoding()
	{
		return (string)$this->getHeader('Transfer-Encoding');
	}

	public function getVary()
	{
		return (string)$this->getHeader('Vary');
	}

	public function getVia()
	{
		return (string)$this->getHeader('Via');
	}

	public function getWarning()
	{
		return (string)$this->getHeader('Warning');
	}

	public function getWwwAuthenticate()
	{
		return (string)$this->getHeader('WWW-Authenticate');
	}

	public function isClientError()
	{
		return $this->statusCode >= 400 && $this->statusCode < 500;
	}

	public function isError()
	{
		return $this->isClientError() || $this->isServerError();
	}

	public function isInformational()
	{
		return $this->statusCode < 200;
	}

	public function isRedirect()
	{
		return $this->statusCode >= 300 && $this->statusCode < 400;
	}

	public function isServerError()
	{
		return $this->statusCode >= 500 && $this->statusCode < 600;
	}

	public function isSuccessful()
	{
		return ($this->statusCode >= 200 && $this->statusCode < 300) || $this->statusCode == 304;
	}

	public function canCache()
	{
		if (!in_array((int)$this->getStatusCode(), self::$cacheResponseCodes)) {
			return false;
		}
		if ((!$this->getBody()->isReadable() || !$this->getBody()->isSeekable()) && ($this->getContentLength() > 0 || $this->getTransferEncoding() == 'chunked')) {
			return false;
		}
		if ($this->getHeader('Cache-Control') && $this->getHeader('Cache-Control')->hasDirective('no-store')) {
			return false;
		}
		return $this->isFresh() || $this->getFreshness() === null || $this->canValidate();
	}

	public function getMaxAge()
	{
		if ($header = $this->getHeader('Cache-Control')) {
			if ($age = $header->getDirective('s-maxage')) {
				return $age;
			}
			if ($age = $header->getDirective('max-age')) {
				return $age;
			}
		}
		if ($this->getHeader('Expires')) {
			return strtotime($this->getExpires()) - time();
		}
		return null;
	}

	public function isFresh()
	{
		$fresh = $this->getFreshness();
		return $fresh === null ? null : $fresh >= 0;
	}

	public function canValidate()
	{
		return $this->getEtag() || $this->getLastModified();
	}

	public function getFreshness()
	{
		$maxAge = $this->getMaxAge();
		$age = $this->calculateAge();
		return $maxAge && $age ? ($maxAge - $age) : null;
	}

	public function json()
	{
		$data = json_decode((string)$this->body, true);
		if (JSON_ERROR_NONE !== json_last_error()) {
			throw new RuntimeException('Unable to parse response body into JSON: ' . json_last_error());
		}
		return $data === null ? array() : $data;
	}

	public function xml()
	{
		try {
			$xml = new \SimpleXMLElement((string)$this->body ?: '<root />');
		} catch (\Exception $e) {
			throw new RuntimeException('Unable to parse response body into XML: ' . $e->getMessage());
		}
		return $xml;
	}

	public function getRedirectCount()
	{
		return (int)$this->params->get(RedirectPlugin::REDIRECT_COUNT);
	}

	public function setEffectiveUrl($url)
	{
		$this->effectiveUrl = $url;
		return $this;
	}

	public function getEffectiveUrl()
	{
		return $this->effectiveUrl;
	}

	public function getPreviousResponse()
	{
		Version::warn(__METHOD__ . ' is deprecated. Use the HistoryPlugin.');
		return null;
	}

	public function setRequest($request)
	{
		Version::warn(__METHOD__ . ' is deprecated');
		return $this;
	}

	public function getRequest()
	{
		Version::warn(__METHOD__ . ' is deprecated');
		return null;
	}
} 