<?php
namespace Guzzle\Http\Message\Header;

use Guzzle\Common\Collection;
use Guzzle\Common\ToArrayInterface;

class HeaderCollection implements \IteratorAggregate, \Countable, \ArrayAccess, ToArrayInterface
{
	protected $headers;

	public function __construct($headers = array())
	{
		$this->headers = $headers;
	}

	public function __clone()
	{
		foreach ($this->headers as &$header) {
			$header = clone $header;
		}
	}

	public function clear()
	{
		$this->headers = array();
	}

	public function add(HeaderInterface $header)
	{
		$this->headers[strtolower($header->getName())] = $header;
		return $this;
	}

	public function getAll()
	{
		return $this->headers;
	}

	public function get($key)
	{
		return $this->offsetGet($key);
	}

	public function count()
	{
		return count($this->headers);
	}

	public function offsetExists($offset)
	{
		return isset($this->headers[strtolower($offset)]);
	}

	public function offsetGet($offset)
	{
		$l = strtolower($offset);
		return isset($this->headers[$l]) ? $this->headers[$l] : null;
	}

	public function offsetSet($offset, $value)
	{
		$this->add($value);
	}

	public function offsetUnset($offset)
	{
		unset($this->headers[strtolower($offset)]);
	}

	public function getIterator()
	{
		return new \ArrayIterator($this->headers);
	}

	public function toArray()
	{
		$result = array();
		foreach ($this->headers as $header) {
			$result[$header->getName()] = $header->toArray();
		}
		return $result;
	}
} 