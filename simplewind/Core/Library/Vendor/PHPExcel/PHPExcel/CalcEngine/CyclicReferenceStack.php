<?php

class PHPExcel_CalcEngine_CyclicReferenceStack
{
	private $_stack = array();

	public function count()
	{
		return count($this->_stack);
	}

	public function push($value)
	{
		$this->_stack[] = $value;
	}

	public function pop()
	{
		return array_pop($this->_stack);
	}

	public function onStack($value)
	{
		return in_array($value, $this->_stack);
	}

	public function clear()
	{
		$this->_stack = array();
	}

	public function showStack()
	{
		return $this->_stack;
	}
} 