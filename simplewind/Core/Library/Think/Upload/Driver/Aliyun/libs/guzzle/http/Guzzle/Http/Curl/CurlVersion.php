<?php
namespace Guzzle\Http\Curl;
class CurlVersion
{
	protected $version;
	protected static $instance;
	protected $userAgent;

	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function getAll()
	{
		if (!$this->version) {
			$this->version = curl_version();
		}
		return $this->version;
	}

	public function get($type)
	{
		$version = $this->getAll();
		return isset($version[$type]) ? $version[$type] : false;
	}
} 