<?php
namespace Guzzle\Plugin\Cookie;

use Guzzle\Common\ToArrayInterface;

class Cookie implements ToArrayInterface
{
	protected $data;
	protected static $invalidCharString;

	protected static function getInvalidCharacters()
	{
		if (!self::$invalidCharString) {
			self::$invalidCharString = implode('', array_map('chr', array_merge(range(0, 32), array(34, 40, 41, 44, 47), array(58, 59, 60, 61, 62, 63, 64, 91, 92, 93, 123, 125, 127))));
		}
		return self::$invalidCharString;
	}

	public function __construct(array $data = array())
	{
		static $defaults = array('name' => '', 'value' => '', 'domain' => '', 'path' => '/', 'expires' => null, 'max_age' => 0, 'comment' => null, 'comment_url' => null, 'port' => array(), 'version' => null, 'secure' => false, 'discard' => false, 'http_only' => false);
		$this->data = array_merge($defaults, $data);
		if (!$this->getExpires() && $this->getMaxAge()) {
			$this->setExpires(time() + (int)$this->getMaxAge());
		} elseif ($this->getExpires() && !is_numeric($this->getExpires())) {
			$this->setExpires(strtotime($this->getExpires()));
		}
	}

	public function toArray()
	{
		return $this->data;
	}

	public function getName()
	{
		return $this->data['name'];
	}

	public function setName($name)
	{
		return $this->setData('name', $name);
	}

	public function getValue()
	{
		return $this->data['value'];
	}

	public function setValue($value)
	{
		return $this->setData('value', $value);
	}

	public function getDomain()
	{
		return $this->data['domain'];
	}

	public function setDomain($domain)
	{
		return $this->setData('domain', $domain);
	}

	public function getPath()
	{
		return $this->data['path'];
	}

	public function setPath($path)
	{
		return $this->setData('path', $path);
	}

	public function getMaxAge()
	{
		return $this->data['max_age'];
	}

	public function setMaxAge($maxAge)
	{
		return $this->setData('max_age', $maxAge);
	}

	public function getExpires()
	{
		return $this->data['expires'];
	}

	public function setExpires($timestamp)
	{
		return $this->setData('expires', $timestamp);
	}

	public function getVersion()
	{
		return $this->data['version'];
	}

	public function setVersion($version)
	{
		return $this->setData('version', $version);
	}

	public function getSecure()
	{
		return $this->data['secure'];
	}

	public function setSecure($secure)
	{
		return $this->setData('secure', (bool)$secure);
	}

	public function getDiscard()
	{
		return $this->data['discard'];
	}

	public function setDiscard($discard)
	{
		return $this->setData('discard', $discard);
	}

	public function getComment()
	{
		return $this->data['comment'];
	}

	public function setComment($comment)
	{
		return $this->setData('comment', $comment);
	}

	public function getCommentUrl()
	{
		return $this->data['comment_url'];
	}

	public function setCommentUrl($commentUrl)
	{
		return $this->setData('comment_url', $commentUrl);
	}

	public function getPorts()
	{
		return $this->data['port'];
	}

	public function setPorts(array $ports)
	{
		return $this->setData('port', $ports);
	}

	public function getHttpOnly()
	{
		return $this->data['http_only'];
	}

	public function setHttpOnly($httpOnly)
	{
		return $this->setData('http_only', $httpOnly);
	}

	public function getAttributes()
	{
		return $this->data['data'];
	}

	public function getAttribute($name)
	{
		return array_key_exists($name, $this->data['data']) ? $this->data['data'][$name] : null;
	}

	public function setAttribute($name, $value)
	{
		$this->data['data'][$name] = $value;
		return $this;
	}

	public function matchesPath($path)
	{
		return !$this->getPath() || 0 === stripos($path, $this->getPath());
	}

	public function matchesDomain($domain)
	{
		$cookieDomain = $this->getDomain();
		if (!$cookieDomain || !strcasecmp($domain, $cookieDomain)) {
			return true;
		}
		if (strpos($cookieDomain, '.') === 0) {
			$realDomain = substr($cookieDomain, 1);
			if (!substr_count($realDomain, '.') && strcasecmp($realDomain, 'local')) {
				return false;
			}
			if (filter_var($domain, FILTER_VALIDATE_IP)) {
				return false;
			}
			if (substr($domain, -strlen($realDomain)) === $realDomain) {
				return !strcasecmp($domain, $realDomain) || substr_count(substr($domain, 0, -strlen($realDomain)), '.') === 1;
			}
		}
		return false;
	}

	public function matchesPort($port)
	{
		return count($this->getPorts()) == 0 || in_array($port, $this->getPorts());
	}

	public function isExpired()
	{
		return $this->getExpires() && time() > $this->getExpires();
	}

	public function validate()
	{
		$name = $this->getName();
		if (empty($name) && !is_numeric($name)) {
			return 'The cookie name must not be empty';
		}
		if (strpbrk($name, self::getInvalidCharacters()) !== false) {
			return 'The cookie name must not contain invalid characters: ' . $name;
		}
		$value = $this->getValue();
		if (empty($value) && !is_numeric($value)) {
			return 'The cookie value must not be empty';
		}
		$domain = $this->getDomain();
		if (empty($domain) && !is_numeric($domain)) {
			return 'The cookie domain must not be empty';
		}
		return true;
	}

	private function setData($key, $value)
	{
		$this->data[$key] = $value;
		return $this;
	}
} 