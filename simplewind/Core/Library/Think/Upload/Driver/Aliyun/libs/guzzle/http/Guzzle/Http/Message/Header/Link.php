<?php
namespace Guzzle\Http\Message\Header;

use Guzzle\Http\Message\Header;

class Link extends Header
{
	public function addLink($url, $rel, array $params = array())
	{
		$values = array("<{$url}>", "rel=\"{$rel}\"");
		foreach ($params as $k => $v) {
			$values[] = "{$k}=\"{$v}\"";
		}
		return $this->add(implode('; ', $values));
	}

	public function hasLink($rel)
	{
		return $this->getLink($rel) !== null;
	}

	public function getLink($rel)
	{
		foreach ($this->getLinks() as $link) {
			if (isset($link['rel']) && $link['rel'] == $rel) {
				return $link;
			}
		}
		return null;
	}

	public function getLinks()
	{
		$links = $this->parseParams();
		foreach ($links as &$link) {
			$key = key($link);
			unset($link[$key]);
			$link['url'] = trim($key, '<> ');
		}
		return $links;
	}
} 