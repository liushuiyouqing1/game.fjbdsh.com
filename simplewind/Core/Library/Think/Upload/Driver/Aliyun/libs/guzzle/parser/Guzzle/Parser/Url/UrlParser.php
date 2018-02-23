<?php
namespace Guzzle\Parser\Url;

use Guzzle\Common\Version;

class UrlParser implements UrlParserInterface
{
	protected $utf8 = false;

	public function setUtf8Support($utf8)
	{
		$this->utf8 = $utf8;
	}

	public function parseUrl($url)
	{
		Version::warn(__CLASS__ . ' is deprecated. Just use parse_url()');
		static $defaults = array('scheme' => null, 'host' => null, 'path' => null, 'port' => null, 'query' => null, 'user' => null, 'pass' => null, 'fragment' => null);
		$parts = parse_url($url);
		if ($this->utf8 && isset($parts['query'])) {
			$queryPos = strpos($url, '?');
			if (isset($parts['fragment'])) {
				$parts['query'] = substr($url, $queryPos + 1, strpos($url, '#') - $queryPos - 1);
			} else {
				$parts['query'] = substr($url, $queryPos + 1);
			}
		}
		return $parts + $defaults;
	}
} 