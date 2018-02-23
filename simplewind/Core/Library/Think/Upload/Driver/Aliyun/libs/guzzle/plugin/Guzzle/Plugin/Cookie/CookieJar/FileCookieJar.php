<?php
namespace Guzzle\Plugin\Cookie\CookieJar;

use Guzzle\Common\Exception\RuntimeException;

class FileCookieJar extends ArrayCookieJar
{
	protected $filename;

	public function __construct($cookieFile)
	{
		$this->filename = $cookieFile;
		$this->load();
	}

	public function __destruct()
	{
		$this->persist();
	}

	protected function persist()
	{
		if (false === file_put_contents($this->filename, $this->serialize())) {
			throw new RuntimeException('Unable to open file ' . $this->filename);
		}
	}

	protected function load()
	{
		$json = file_get_contents($this->filename);
		if (false === $json) {
			throw new RuntimeException('Unable to open file ' . $this->filename);
		}
		$this->unserialize($json);
		$this->cookies = $this->cookies ?: array();
	}
} 