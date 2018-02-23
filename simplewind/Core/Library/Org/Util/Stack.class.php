<?php
namespace Org\Util;
class Stack extends ArrayList
{
	public function __construct($values = array())
	{
		parent::__construct($values);
	}

	public function peek()
	{
		return reset($this->toArray());
	}

	public function push($value)
	{
		$this->add($value);
		return $value;
	}
} 