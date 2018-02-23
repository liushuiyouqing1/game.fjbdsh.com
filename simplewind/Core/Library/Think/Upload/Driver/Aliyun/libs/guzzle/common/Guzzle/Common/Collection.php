<?php
namespace Guzzle\Common;

use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Common\Exception\RuntimeException;

class Collection implements \ArrayAccess, \IteratorAggregate, \Countable, ToArrayInterface
{
	protected $data;

	public function __construct(array $data = array())
	{
		$this->data = $data;
	}

	public static function fromConfig(array $config = array(), array $defaults = array(), array $required = array())
	{
		$data = $config + $defaults;
		if ($missing = array_diff($required, array_keys($data))) {
			throw new InvalidArgumentException('Config is missing the following keys: ' . implode(', ', $missing));
		}
		return new self($data);
	}

	public function count()
	{
		return count($this->data);
	}

	public function getIterator()
	{
		return new \ArrayIterator($this->data);
	}

	public function toArray()
	{
		return $this->data;
	}

	public function clear()
	{
		$this->data = array();
		return $this;
	}

	public function getAll(array $keys = null)
	{
		return $keys ? array_intersect_key($this->data, array_flip($keys)) : $this->data;
	}

	public function get($key)
	{
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

	public function set($key, $value)
	{
		$this->data[$key] = $value;
		return $this;
	}

	public function add($key, $value)
	{
		if (!array_key_exists($key, $this->data)) {
			$this->data[$key] = $value;
		} elseif (is_array($this->data[$key])) {
			$this->data[$key][] = $value;
		} else {
			$this->data[$key] = array($this->data[$key], $value);
		}
		return $this;
	}

	public function remove($key)
	{
		unset($this->data[$key]);
		return $this;
	}

	public function getKeys()
	{
		return array_keys($this->data);
	}

	public function hasKey($key)
	{
		return array_key_exists($key, $this->data);
	}

	public function keySearch($key)
	{
		foreach (array_keys($this->data) as $k) {
			if (!strcasecmp($k, $key)) {
				return $k;
			}
		}
		return false;
	}

	public function hasValue($value)
	{
		return array_search($value, $this->data);
	}

	public function replace(array $data)
	{
		$this->data = $data;
		return $this;
	}

	public function merge($data)
	{
		foreach ($data as $key => $value) {
			$this->add($key, $value);
		}
		return $this;
	}

	public function overwriteWith($data)
	{
		if (is_array($data)) {
			$this->data = $data + $this->data;
		} elseif ($data instanceof Collection) {
			$this->data = $data->toArray() + $this->data;
		} else {
			foreach ($data as $key => $value) {
				$this->data[$key] = $value;
			}
		}
		return $this;
	}

	public function map(\Closure $closure, array $context = array(), $static = true)
	{
		$collection = $static ? new static() : new self();
		foreach ($this as $key => $value) {
			$collection->add($key, $closure($key, $value, $context));
		}
		return $collection;
	}

	public function filter(\Closure $closure, $static = true)
	{
		$collection = ($static) ? new static() : new self();
		foreach ($this->data as $key => $value) {
			if ($closure($key, $value)) {
				$collection->add($key, $value);
			}
		}
		return $collection;
	}

	public function offsetExists($offset)
	{
		return isset($this->data[$offset]);
	}

	public function offsetGet($offset)
	{
		return isset($this->data[$offset]) ? $this->data[$offset] : null;
	}

	public function offsetSet($offset, $value)
	{
		$this->data[$offset] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->data[$offset]);
	}

	public function setPath($path, $value)
	{
		$current =& $this->data;
		$queue = explode('/', $path);
		while (null !== ($key = array_shift($queue))) {
			if (!is_array($current)) {
				throw new RuntimeException("Trying to setPath {$path}, but {$key} is set and is not an array");
			} elseif (!$queue) {
				$current[$key] = $value;
			} elseif (isset($current[$key])) {
				$current =& $current[$key];
			} else {
				$current[$key] = array();
				$current =& $current[$key];
			}
		}
		return $this;
	}

	public function getPath($path, $separator = '/', $data = null)
	{
		if ($data === null) {
			$data =& $this->data;
		}
		$path = is_array($path) ? $path : explode($separator, $path);
		while (null !== ($part = array_shift($path))) {
			if (!is_array($data)) {
				return null;
			} elseif (isset($data[$part])) {
				$data =& $data[$part];
			} elseif ($part != '*') {
				return null;
			} else {
				$result = array();
				foreach ($data as $value) {
					if (!$path) {
						$result = array_merge_recursive($result, (array)$value);
					} elseif (null !== ($test = $this->getPath($path, $separator, $value))) {
						$result = array_merge_recursive($result, (array)$test);
					}
				}
				return $result;
			}
		}
		return $data;
	}

	public function inject($input)
	{
		Version::warn(__METHOD__ . ' is deprecated');
		$replace = array();
		foreach ($this->data as $key => $val) {
			$replace['{' . $key . '}'] = $val;
		}
		return strtr($input, $replace);
	}
} 