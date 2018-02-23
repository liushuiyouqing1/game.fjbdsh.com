<?php
namespace Guzzle\Http\Message\Header;

use Guzzle\Http\Message\Header;

class HeaderFactory implements HeaderFactoryInterface
{
	protected $mapping = array('cache-control' => 'Guzzle\Http\Message\Header\CacheControl', 'link' => 'Guzzle\Http\Message\Header\Link',);

	public function createHeader($header, $value = null)
	{
		$lowercase = strtolower($header);
		return isset($this->mapping[$lowercase]) ? new $this->mapping[$lowercase]($header, $value) : new Header($header, $value);
	}
} 