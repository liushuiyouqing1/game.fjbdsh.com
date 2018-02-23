<?php
namespace Org\Util;
class ArrayList implements \IteratorAggregate
{
	protected $_elements = array();

	public function __construct($elements = array())
	{
		if (!empty($elements)) {
			$this->_elements = $elements;
		}
	}

	public function getIterator()
	{
		return new ArrayObject($this->_elements);
	}

	public function add($element)
	{
		return (array_push($this->_elements, $element)) ? true : false;
	}

	public function unshift($element)
	{
		return (array_unshift($this->_elements, $element)) ? true : false;
	}

	public function pop()
	{
		return array_pop($this->_elements);
	}

	public function addAll($list)
	{
		$before = $this->size();
		foreach ($list as $element) {
			$this->add($element);
		}
		$after = $this->size();
		return ($before < $after);
	}

	public function clear()
	{
		$this->_elements = array();
	}

	public function contains($element)
	{
		return (array_search($element, $this->_elements) !== false);
	}

	public function get($index)
	{
		return $this->_elements[$index];
	}

	public function indexOf($element)
	{
		return array_search($element, $this->_elements);
	}

	public function isEmpty()
	{
		return empty($this->_elements);
	}

	public function lastIndexOf($element)
	{
		for ($i = (count($this->_elements) - 1); $i > 0; $i--) {
			if ($element == $this->get($i)) {
				return $i;
			}
		}
	}

	public function toJson()
	{
		return json_encode($this->_elements);
	}

	public function remove($index)
	{
		$element = $this->get($index);
		if (!is_null($element)) {
			array_splice($this->_elements, $index, 1);
		}
		return $element;
	}

	public function removeRange($offset, $length)
	{
		array_splice($this->_elements, $offset, $length);
	}

	public function unique()
	{
		$this->_elements = array_unique($this->_elements);
	}

	public function range($offset, $length = null)
	{
		return array_slice($this->_elements, $offset, $length);
	}

	public function set($index, $element)
	{
		$previous = $this->get($index);
		$this->_elements[$index] = $element;
		return $previous;
	}

	public function size()
	{
		return count($this->_elements);
	}

	public function toArray()
	{
		return $this->_elements;
	}

	public function ksort()
	{
		ksort($this->_elements);
	}

	public function asort()
	{
		asort($this->_elements);
	}

	public function rsort()
	{
		rsort($this->_elements);
	}

	public function natsort()
	{
		natsort($this->_elements);
	}
} 