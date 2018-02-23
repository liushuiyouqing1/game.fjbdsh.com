<?php
namespace Guzzle\Parser;
class ParserRegistry
{
	protected static $instance;
	protected $instances = array();
	protected $mapping = array('message' => 'Guzzle\\Parser\\Message\\MessageParser', 'cookie' => 'Guzzle\\Parser\\Cookie\\CookieParser', 'url' => 'Guzzle\\Parser\\Url\\UrlParser', 'uri_template' => 'Guzzle\\Parser\\UriTemplate\\UriTemplate',);

	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new static;
		}
		return self::$instance;
	}

	public function __construct()
	{
		if (extension_loaded('uri_template')) {
			$this->mapping['uri_template'] = 'Guzzle\\Parser\\UriTemplate\\PeclUriTemplate';
		}
	}

	public function getParser($name)
	{
		if (!isset($this->instances[$name])) {
			if (!isset($this->mapping[$name])) {
				return null;
			}
			$class = $this->mapping[$name];
			$this->instances[$name] = new $class();
		}
		return $this->instances[$name];
	}

	public function registerParser($name, $parser)
	{
		$this->instances[$name] = $parser;
	}
} 